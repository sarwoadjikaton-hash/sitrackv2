<?php

namespace App\Imports;

use App\Models\LetterNumber;
use App\Models\LetterNumberType;
use App\Models\Unit;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Validators\Failure;
use App\Models\Letter;
use App\Services\LetterNumberService;
use Throwable;

/**
 * Imports the legacy "Data Surat" workbook format:
 *
 * NO | TANGGAL MASUK | UNIT PENGOLAH ARSIP | PENANDATANGAN SURAT | PERMOHONAN |
 * TUJUAN SURAT | TANGGAL SURAT | KEAMANAN AKSES | NOMOR URUT | KODE KLAS. ARSIP |
 * BULAN | NOMOR SURAT | PERIHAL SURAT | PETUGAS UNIT TEKNIS | ND Pengantar
 *
 * One file = one Jenis Naskah (type_id chosen in the upload form), because the
 * sheet itself has no "jenis naskah" column — same convention as the legacy
 * per-workbook Excel books.
 *
 * Existing "available"/"reserved" slots (from Ketersediaan Nomor) are upgraded
 * to "used". If a NOMOR URUT has no matching slot yet (pure historical import),
 * the row is created directly as "used".
 */
class DataSuratImport implements ToCollection, WithHeadingRow, SkipsOnError, SkipsOnFailure, SkipsEmptyRows
{
    use Importable;

    public function __construct(private readonly int $typeId)
    {
    }

    public int $imported = 0;

    /** @var array<int, array{row:int, errors:array}> */
    public array $failures = [];

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
    ];

    public function collection(Collection $rows): void
    {
        $type = LetterNumberType::findOrFail($this->typeId);

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;

            try {
                $this->importRow($type, $row, $rowNumber);
            } catch (Throwable $e) {
                $this->failures[] = ['row' => $rowNumber, 'errors' => [$e->getMessage()]];
            }
        }
    }

    private function importRow(LetterNumberType $type, Collection $row, int $rowNumber): void
    {
        $sequence = (int) $this->val($row, ['nomor_urut']);
        if ($sequence <= 0) {
            throw new \RuntimeException('NOMOR URUT kosong / tidak valid.');
        }

        $letterDate = $this->parseDate($this->val($row, ['tanggal_surat']));
        $incomingDate = $this->parseDate($this->val($row, ['tanggal_masuk'])) ?? $letterDate ?? now()->toDateString();
        $year = $letterDate ? (int) date('Y', strtotime($letterDate)) : (int) date('Y', strtotime($incomingDate));

        $unitName = trim((string) $this->val($row, ['unit_pengolah_arsip']));
        $unitId = $unitName !== '' ? Unit::where('unit_name', $unitName)->value('id') : null;

        $monthRaw = trim((string) $this->val($row, ['bulan']));
        $monthNumber = is_numeric($monthRaw)
            ? (int) $monthRaw
            : (self::MONTHS[mb_strtolower($monthRaw)] ?? ($letterDate ? (int) date('n', strtotime($letterDate)) : now()->month));

        $payload = [
            'incoming_date' => $incomingDate,
            'unit_id' => $unitId,
            'processing_unit_text' => $unitName ?: null,
            'signatory' => $this->val($row, ['penandatangan_surat']),
            'request_type' => $this->val($row, ['permohonan']),
            'destination' => $this->val($row, ['tujuan_surat']),
            'letter_date' => $letterDate,
            'security_access' => $this->nullableUpper($this->val($row, ['keamanan_akses'])),
            'classification_code' => $this->nullableUpper($this->val($row, ['kode_klas_arsip', 'kode_klas', 'kode_klasifikasi_arsip'])),
            'month_number' => $monthNumber,
            'number_text' => $this->val($row, ['nomor_surat']),
            'subject' => $this->val($row, ['perihal_surat']),
            'technical_officer' => $this->val($row, ['petugas_unit_teknis', 'petugas_unit__teknis']),
            'nd_pengantar' => $type->extra_field === 'nd_pengantar' ? $this->val($row, ['nd_pengantar']) : null,
            'scan_result' => $type->extra_field === 'scan_result' ? $this->val($row, ['nd_pengantar']) : null,
            'status' => 'used',
            'used_at' => now(),
            'created_by' => Auth::id(),
        ];

        DB::transaction(function () use ($type, $year, $sequence, $payload) {
            $letterNumber = LetterNumber::updateOrCreate(
                ['type_id' => $type->id, 'number_year' => $year, 'sequence_number' => $sequence],
                array_merge($payload, [
                    'signer_code' => $type->default_signer_code ?: '1',
                ])
            );

            // Only create a Letter (signature-lane record) once per LetterNumber,
            // so re-importing the same row doesn't create duplicates.
            if (!$letterNumber->linked_letter_id) {
                $letter = Letter::create([
                    'tracking_code' => LetterNumberService::generateTrackingCode(),
                    'agenda_number' => LetterNumberService::nextAgendaNumber('out'),
                    'letter_number' => $letterNumber->number_text,
                    'letter_type' => 'out',
                    'process_lane' => 'signature',
                    'sender_unit' => $letterNumber->processing_unit_text,
                    'sender_name' => $letterNumber->signatory ?: '-',
                    'subject' => $letterNumber->subject,
                    'letter_date' => $letterNumber->letter_date,
                    'received_date' => $letterNumber->incoming_date,
                    'priority' => 'normal',
                    'security_level' => $letterNumber->security_access ?: 'B',
                    'status' => 'Dokumen Diterima dan Diinput',
                    'current_position' => 'Arsiparis',
                    'archive_classification_code' => $letterNumber->classification_code,
                    'signatory_name' => $letterNumber->signatory,
                    'technical_officer' => $letterNumber->technical_officer,
                    'letter_source' => 'Manual',
                    'created_by' => Auth::id(),
                ]);

                $letterNumber->update(['linked_letter_id' => $letter->id]);
            }
        });

        $this->imported++;
    }

    /** Fetch a value trying several possible normalized header-key variants. */
    private function val(Collection $row, array $candidates): ?string
    {
        foreach ($candidates as $key) {
            if ($row->has($key) && trim((string) $row->get($key)) !== '') {
                return trim((string) $row->get($key));
            }
        }

        return null;
    }

    private function nullableUpper(?string $value): ?string
    {
        return $value ? strtoupper($value) : null;
    }

    private function parseDate(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        try {
            // handles both Excel serial dates (already converted by the reader) and text dates
            return \Carbon\Carbon::parse($value)->toDateString();
        } catch (Throwable) {
            return null;
        }
    }

    public function onError(Throwable $e): void
    {
        $this->failures[] = ['row' => 0, 'errors' => [$e->getMessage()]];
    }

    public function onFailure(Failure ...$failures): void
    {
        foreach ($failures as $failure) {
            $this->failures[] = ['row' => $failure->row(), 'errors' => $failure->errors()];
        }
    }
}