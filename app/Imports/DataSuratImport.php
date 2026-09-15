<?php

namespace App\Imports;

use App\Models\Letter;
use App\Models\LetterNumber;
use App\Models\LetterNumberAvailabilityBatch;
use App\Models\LetterNumberType;
use App\Models\LetterStatusLog;
use App\Models\Unit;
use App\Services\LetterNumberService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Throwable;

/**
 * Imports the legacy "REKAP NOMOR" workbook: one Excel sheet per Jenis Naskah,
 * sheet name matched against LetterNumberType::workbook_name. Header row is
 * auto-detected (not assumed to be row 1), and placeholder/未-filled rows are
 * silently skipped.
 */
class DataSuratImport
{
    public int $imported = 0;

    /** @var array<int, array{sheet:string, row:int, errors:array}> */
    public array $failures = [];

    /** @var string[] sheet titles that didn't match any Jenis Naskah */
    public array $skippedSheets = [];


    /** @var array<string, array{type_id:int, year:int, min:int, max:int, count:int}> */
    private array $touchedRanges = [];

    private const MONTHS = [
        'januari' => 1,
        'februari' => 2,
        'maret' => 3,
        'april' => 4,
        'mei' => 5,
        'juni' => 6,
        'juli' => 7,
        'agustus' => 8,
        'september' => 9,
        'oktober' => 10,
        'november' => 11,
        'desember' => 12,
        'jan' => 1,
        'feb' => 2,
        'mar' => 3,
        'apr' => 4,
        'jun' => 6,
        'jul' => 7,
        'agu' => 8,
        'sep' => 9,
        'okt' => 10,
        'nov' => 11,
        'des' => 12,
    ];

    /** normalized legacy header label => internal key */
    private const HEADER_MAP = [
        'no' => 'no',
        'tanggal masuk' => 'tanggal_masuk',
        'unit pengolah arsip' => 'unit_pengolah_arsip',
        'penandatangan surat' => 'penandatangan_surat',
        'permohonan' => 'permohonan',
        'tujuan surat' => 'tujuan_surat',
        'tanggal surat' => 'tanggal_surat',
        'keamanan akses' => 'keamanan_akses',
        'nomor urut' => 'nomor_urut',
        'kode klas. arsip' => 'kode_klas_arsip',
        'bulan' => 'bulan',
        'nomor surat' => 'nomor_surat',
        'perihal surat' => 'perihal_surat',
        'petugas unit teknis' => 'petugas_unit_teknis',
        'nd pengantar' => 'nd_pengantar',
        'hasil pindai' => 'scan_result',
    ];

    public function import(string $filePath, ?int $fallbackTypeId = null): void
    {
        @ini_set('memory_limit', '1024M');
        @set_time_limit(300);

        $reader = IOFactory::createReaderForFile($filePath);
        $reader->setReadDataOnly(true);
        if (method_exists($reader, 'setReadEmptyCells')) {
            $reader->setReadEmptyCells(false);
        }
        $spreadsheet = $reader->load($filePath);

        $typesByName = LetterNumberType::all()->keyBy(fn($t) => mb_strtolower(trim($t->workbook_name)));

        // Kalau user memilih target Jenis Naskah spesifik (bukan "Semua Jenis Naskah"),
// import HANYA memproses sheet yang namanya cocok dengan Jenis Naskah itu.
// Sheet lain di file yang sama sengaja dilewati (bukan error, jadi tidak dicatat sebagai skipped).
        $targetType = $fallbackTypeId ? $typesByName->firstWhere('id', $fallbackTypeId) : null;

        foreach ($spreadsheet->getAllSheets() as $sheet) {
            $title = trim($sheet->getTitle());
            $matchedType = $typesByName->get(mb_strtolower($title));

            if ($targetType) {
                if (!$matchedType || $matchedType->id !== $targetType->id) {
                    continue; // bukan sheet target, lewati diam-diam
                }
                $type = $targetType;
            } else {
                $type = $matchedType;
            }

            if (!$type) {
                $this->skippedSheets[] = $title;
                continue;
            }

            $rows = $sheet->toArray(null, true, false, false);

            $headerRowIndex = $this->findHeaderRowIndex($rows);
            if ($headerRowIndex === null) {
                $this->skippedSheets[] = "{$title} (baris header tidak ditemukan)";
                continue;
            }

            $columnMap = $this->buildColumnMap($rows[$headerRowIndex]);

            for ($r = $headerRowIndex + 1; $r < count($rows); $r++) {
                $data = $this->mapRow($rows[$r], $columnMap);

                $sequenceRaw = $data['nomor_urut'] ?? null;
                if ($sequenceRaw === null || $sequenceRaw === '' || (int) $sequenceRaw <= 0) {
                    continue;
                }

                $rowNumber = $r + 1;

                try {
                    $this->importRow($type, $data);
                } catch (Throwable $e) {
                    $this->failures[] = ['sheet' => $title, 'row' => $rowNumber, 'errors' => [$e->getMessage()]];
                }
            }
        }

        foreach ($this->touchedRanges as $range) {
            LetterNumberAvailabilityBatch::create([
                'type_id' => $range['type_id'],
                'number_year' => $range['year'],
                'purpose' => 'available',
                'start_sequence' => $range['min'],
                'end_sequence' => $range['max'],
                'period_month' => now()->toDateString(),
                'status' => 'active',
                'notes' => "Hasil import Excel: {$range['count']} nomor (rentang {$range['min']}–{$range['max']}).",
                'created_by' => Auth::id(),
            ]);
        }
    }

    private function findHeaderRowIndex(array $rows): ?int
    {
        foreach ($rows as $i => $row) {
            $normalized = array_map(fn($c) => mb_strtolower(trim((string) $c)), $row);
            if (in_array('no', $normalized, true) && in_array('nomor urut', $normalized, true)) {
                return $i;
            }
        }

        return null;
    }

    private function buildColumnMap(array $headerRow): array
    {
        $map = [];

        foreach ($headerRow as $colIndex => $label) {
            $norm = mb_strtolower(trim(preg_replace('/\s+/', ' ', str_replace("\n", ' ', (string) $label))));
            if ($norm === '') {
                continue;
            }

            if (isset(self::HEADER_MAP[$norm])) {
                $map[self::HEADER_MAP[$norm]] = $colIndex;
            }
        }

        return $map;
    }

    private function mapRow(array $row, array $map): array
    {
        $data = [];
        foreach ($map as $key => $colIndex) {
            $value = $row[$colIndex] ?? null;
            $data[$key] = is_string($value) ? trim($value) : $value;
        }

        return $data;
    }

    private function hasMeaningfulData(array $data): bool
    {
        foreach (['unit_pengolah_arsip', 'penandatangan_surat', 'tanggal_surat', 'nomor_surat'] as $field) {
            if ($this->clean($data[$field] ?? null) !== null) {
                return true;
            }
        }

        return false;
    }

    private function importRow(LetterNumberType $type, array $data): void
    {
        $sequence = (int) ($data['nomor_urut'] ?? 0);
        if ($sequence <= 0) {
            throw new \RuntimeException('NOMOR URUT kosong / tidak valid.');
        }

        $letterDate = $this->parseDate($data['tanggal_surat'] ?? null);
        $incomingDate = $this->parseDate($data['tanggal_masuk'] ?? null) ?? $letterDate ?? now()->toDateString();
        $year = $letterDate ? (int) date('Y', strtotime($letterDate)) : (int) date('Y', strtotime($incomingDate));

        $unitName = $this->clean($data['unit_pengolah_arsip'] ?? null);
        $unitId = $unitName ? Unit::where('unit_name', $unitName)->value('id') : null;

        $monthRaw = trim((string) ($data['bulan'] ?? ''));
        $monthNumber = is_numeric($monthRaw)
            ? (int) $monthRaw
            : (self::MONTHS[mb_strtolower($monthRaw)] ?? ($letterDate ? (int) date('n', strtotime($letterDate)) : now()->month));

        $numberText = $this->clean($data['nomor_surat'] ?? null);
        $signatory = $this->clean($data['penandatangan_surat'] ?? null);
        $destination = $this->clean($data['tujuan_surat'] ?? null);
        $requestType = $this->clean($data['permohonan'] ?? null);
        $subject = $this->clean($data['perihal_surat'] ?? null);
        $technicalOfficer = $this->clean($data['petugas_unit_teknis'] ?? null);

        // TAMBAHAN: deteksi keyword "booking [nama]" -> selalu reserved, apa pun kondisi lain
        $reservedFor = null;
        $isBooking = false;
        if ($technicalOfficer !== null && stripos($technicalOfficer, 'booking') !== false) {
            $isBooking = true;
            if (preg_match('/booking\s+(.+)/i', $technicalOfficer, $m)) {
                $reservedFor = trim($m[1]);
            }
        }

        $hasOtherMeta = $unitName !== null || $signatory !== null || $letterDate !== null;

        // TAMBAHAN: logika 5 tingkat prioritas
        $status = match (true) {
            $isBooking => 'reserved',
            $numberText !== null => 'used',
            $destination !== null || $subject !== null => 'preorder',
            $hasOtherMeta => 'reserved',
            default => 'available',
        };
        $isUsed = $status === 'used';

        $payload = [
            'incoming_date' => $incomingDate,
            'unit_id' => $unitId,
            'processing_unit_text' => $unitName,
            'signatory' => $signatory,
            'request_type' => $requestType,
            'destination' => $destination,
            'letter_date' => $letterDate,
            'security_access' => ($v = $this->clean($data['keamanan_akses'] ?? null)) ? strtoupper($v) : null,
            'classification_code' => ($v = $this->clean($data['kode_klas_arsip'] ?? null)) ? strtoupper($v) : null,
            'month_number' => $monthNumber,
            'number_text' => $numberText,
            'subject' => $subject,
            'technical_officer' => $technicalOfficer,
            'nd_pengantar' => $type->extra_field === 'nd_pengantar' ? $this->clean($data['nd_pengantar'] ?? null) : null,
            'scan_result' => $type->extra_field === 'scan_result'
                ? ($this->clean($data['scan_result'] ?? null) ?? $this->clean($data['nd_pengantar'] ?? null))
                : null,
            'status' => $status,
            'reserved_for' => $status === 'reserved' ? $reservedFor : null,
            'reserved_at' => $status === 'reserved' ? now() : null,
            'used_at' => $isUsed ? now() : null,
            'created_by' => Auth::id(),
        ];

        $isCurrentMonth = $letterDate
            && \Carbon\Carbon::parse($letterDate)->format('Y-m') === now()->format('Y-m');

        DB::transaction(function () use ($type, $year, $sequence, $payload, $isCurrentMonth, $isUsed) {
            if ($isCurrentMonth && $isUsed) {
                $existing = LetterNumber::where('type_id', $type->id)
                    ->where('number_year', $year)
                    ->where('sequence_number', $sequence)
                    ->first();

                if (!$existing || !in_array($existing->status, ['available', 'reserved'])) {
                    throw new \RuntimeException("Nomor urut {$sequence} untuk bulan berjalan belum tersedia di stok Ketersediaan Nomor.");
                }

                $letterNumber = tap($existing)->update($payload);
            } else {
                $letterNumber = LetterNumber::updateOrCreate(
                    ['type_id' => $type->id, 'number_year' => $year, 'sequence_number' => $sequence],
                    array_merge($payload, ['signer_code' => $type->default_signer_code ?: '1'])
                );
            }

            if ($status !== 'available' && !$letterNumber->linked_letter_id) {
                $letterNum = $letterNumber->number_text ?: LetterNumberService::buildNumberText($type, [
                    'sequence_number' => $sequence,
                    'number_year' => $year,
                    'signer_code' => $type->default_signer_code ?: '1',
                    'month_number' => $monthNumber,
                ]);

                $letterSubject = $letterNumber->subject ?: ($status === 'preorder' ? "Pre-Order Naskah ({$type->type_name})" : ($status === 'reserved' ? "Reservasi Naskah ({$type->type_name})" : '-'));

                $letter = Letter::create([
                    'tracking_code' => LetterNumberService::generateTrackingCode($type->type_code),
                    'agenda_number' => LetterNumberService::nextAgendaNumber('out'),
                    'letter_number' => $letterNum,
                    'letter_number_type_id' => $type->id,
                    'letter_type' => 'out',
                    'process_lane' => 'signature',
                    'sender_unit' => $letterNumber->processing_unit_text,
                    'sender_name' => $letterNumber->signatory ?: $letterNumber->reserved_for ?: 'Arsiparis',
                    'subject' => $letterSubject,
                    'letter_date' => $letterNumber->letter_date,
                    'received_date' => $letterNumber->incoming_date,
                    'priority' => 'Biasa',
                    'security_level' => $letterNumber->security_access ?: 'Biasa',
                    'status' => 'Dokumen Diterima dan Diinput',
                    'current_position' => 'Arsiparis',
                    'archive_classification_code' => $letterNumber->classification_code,
                    'signatory_name' => $letterNumber->signatory,
                    'technical_officer' => $letterNumber->technical_officer,
                    'destination' => $letterNumber->destination,
                    'letter_source' => 'Import Excel',
                    'created_by' => Auth::id(),
                ]);

                LetterStatusLog::create([
                    'letter_id' => $letter->id,
                    'status' => 'Dokumen Diterima dan Diinput',
                    'position' => 'Arsiparis',
                    'note' => 'Data surat diimpor dari file Excel.',
                    'changed_by' => Auth::user()?->name ?: 'Admin',
                    'changed_at' => now(),
                ]);

                $letterNumber->update(['linked_letter_id' => $letter->id]);
            }
        });

        $this->imported++;

        $key = $type->id . '|' . $year;
        if (!isset($this->touchedRanges[$key])) {
            $this->touchedRanges[$key] = [
                'type_id' => $type->id,
                'year' => $year,
                'min' => $sequence,
                'max' => $sequence,
                'count' => 0,
            ];
        }
        $this->touchedRanges[$key]['min'] = min($this->touchedRanges[$key]['min'], $sequence);
        $this->touchedRanges[$key]['max'] = max($this->touchedRanges[$key]['max'], $sequence);
        $this->touchedRanges[$key]['count']++;
    }

    private const EXCEL_ERROR_TOKENS = ['#N/A', '#REF!', '#DIV/0!', '#VALUE!', '#NAME?', '#NULL!', '#NUM!'];

    private function clean(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $value = trim((string) $value);

        if ($value === '' || $value === '…') {
            return null;
        }

        // Exact match: seluruh sel cuma berisi token error
        if (in_array($value, self::EXCEL_ERROR_TOKENS, true)) {
            return null;
        }

        // Substring match: token error "menempel" di dalam string gabungan
        // (kasus formula lama pakai IFERROR(...,"#N/A") sebagai placeholder teks)
        foreach (self::EXCEL_ERROR_TOKENS as $token) {
            if (str_contains($value, $token)) {
                return null;
            }
        }

        return $value;
    }

    private function parseDate(mixed $value): ?string
    {
        $value = $this->clean($value);
        if ($value === null) {
            return null;
        }

        if (is_numeric($value)) {
            try {
                return ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d');
            } catch (Throwable) {
                return null;
            }
        }

        try {
            return \Carbon\Carbon::parse($value)->toDateString();
        } catch (Throwable) {
            // fallback: "02 Januari 2026" (nama bulan Indonesia, Carbon default locale tidak paham)
            if (preg_match('/(\d{1,2})\s+([A-Za-z]+)\s+(\d{4})/u', $value, $m)) {
                $month = self::MONTHS[mb_strtolower($m[2])] ?? null;
                if ($month) {
                    return sprintf('%04d-%02d-%02d', (int) $m[3], $month, (int) $m[1]);
                }
            }

            return null;
        }
    }
}