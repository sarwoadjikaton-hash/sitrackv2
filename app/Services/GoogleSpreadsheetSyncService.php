<?php

namespace App\Services;

use App\Models\Letter;
use App\Models\LetterNumber;
use App\Models\LetterNumberAvailabilityBatch;
use App\Models\LetterNumberType;
use App\Models\LetterStatusLog;
use App\Models\Unit;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class GoogleSpreadsheetSyncService
{
    public const DEFAULT_SPREADSHEET_ID = '1Qv27GijtAHpEAUu-Vz0YfiLactdUCAt_owmIONpXeyc';

    private const MONTHS = [
        'januari' => 1, 'jan' => 1, 'i' => 1,
        'februari' => 2, 'feb' => 2, 'ii' => 2,
        'maret' => 3, 'mar' => 3, 'iii' => 3,
        'april' => 4, 'apr' => 4, 'iv' => 4,
        'mei' => 5, 'may' => 5, 'v' => 5,
        'juni' => 6, 'jun' => 6, 'vi' => 6,
        'juli' => 7, 'jul' => 7, 'vii' => 7,
        'agustus' => 8, 'agu' => 8, 'agt' => 8, 'aug' => 8, 'viii' => 8,
        'september' => 9, 'sep' => 9, 'ix' => 9,
        'oktober' => 10, 'okt' => 10, 'oct' => 10, 'x' => 10,
        'november' => 11, 'nov' => 11, 'xi' => 11,
        'desember' => 12, 'des' => 12, 'dec' => 12, 'xii' => 12,
    ];

    private const HEADER_MAP = [
        'no' => 'no',
        'no.' => 'no',
        'nomor' => 'no',
        'tanggal masuk' => 'tanggal_masuk',
        'tgl masuk' => 'tanggal_masuk',
        'unit pengolah arsip' => 'unit_pengolah_arsip',
        'unit pengolah' => 'unit_pengolah_arsip',
        'unit kerja' => 'unit_pengolah_arsip',
        'unit' => 'unit_pengolah_arsip',
        'penandatangan surat' => 'penandatangan_surat',
        'penandatangan' => 'penandatangan_surat',
        'permohonan' => 'permohonan',
        'tujuan surat' => 'tujuan_surat',
        'tujuan' => 'tujuan_surat',
        'tanggal surat' => 'tanggal_surat',
        'tgl surat' => 'tanggal_surat',
        'keamanan akses' => 'keamanan_akses',
        'akses' => 'keamanan_akses',
        'nomor urut' => 'nomor_urut',
        'no urut' => 'nomor_urut',
        'no. urut' => 'nomor_urut',
        'kode klas. arsip' => 'kode_klas_arsip',
        'kode klasifikasi' => 'kode_klas_arsip',
        'kode klas' => 'kode_klas_arsip',
        'klasifikasi' => 'kode_klas_arsip',
        'bulan' => 'bulan',
        'nomor surat' => 'nomor_surat',
        'no surat' => 'nomor_surat',
        'no. surat' => 'nomor_surat',
        'perihal surat' => 'perihal_surat',
        'perihal' => 'perihal_surat',
        'hal' => 'perihal_surat',
        'petugas unit teknis' => 'petugas_unit_teknis',
        'petugas' => 'petugas_unit_teknis',
        'nd pengantar' => 'nd_pengantar',
        'hasil scan' => 'scan_result',
        'scan' => 'scan_result',
    ];

    /**
     * Extract spreadsheet ID from link URL or raw ID
     */
    public static function extractSpreadsheetId(string $urlOrId): string
    {
        $trimmed = trim($urlOrId);
        if (preg_match('/\/spreadsheets\/d\/([a-zA-Z0-9-_]+)/', $trimmed, $matches)) {
            return $matches[1];
        }
        return $trimmed ?: self::DEFAULT_SPREADSHEET_ID;
    }

    /**
     * Extract GID from link URL if present
     */
    public static function extractGid(string $url): ?string
    {
        if (preg_match('/[?&#]gid=(\d+)/', $url, $matches)) {
            return $matches[1];
        }
        return null;
    }

    /**
     * Discover all sheet names and GIDs from Google Spreadsheet HTML view
     */
    public function discoverSheets(string $spreadsheetId): array
    {
        $htmlUrl = "https://docs.google.com/spreadsheets/d/{$spreadsheetId}/htmlview";

        try {
            $response = Http::timeout(15)->get($htmlUrl);
            if (!$response->successful()) {
                Log::warning("Failed to fetch Google Spreadsheet htmlview: HTTP " . $response->status());
                return [];
            }

            $html = $response->body();
            $sheets = [];

            // Match sheet names & GIDs
            if (preg_match_all('/name:\s*"([^"]+)",\s*pageUrl:\s*"[^"]*gid=(\d+)"/i', $html, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $m) {
                    $sheets[] = [
                        'name' => trim($m[1]),
                        'gid' => $m[2],
                    ];
                }
            }

            return $sheets;
        } catch (Throwable $e) {
            Log::error("Error discovering Google Sheets tabs: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Sync data from Google Spreadsheet for a specific LetterNumberType or all types
     */
    public function sync(string $spreadsheetUrlOrId, ?int $targetTypeId = null, int $year = 2026): array
    {
        $spreadsheetId = self::extractSpreadsheetId($spreadsheetUrlOrId);
        $discoveredSheets = $this->discoverSheets($spreadsheetId);

        $types = LetterNumberType::where('is_active', true)->get();
        $typesByName = $types->keyBy(fn($t) => mb_strtolower(trim($t->workbook_name)));

        $sheetMap = [];
        foreach ($discoveredSheets as $sheet) {
            $sheetMap[mb_strtolower(trim($sheet['name']))] = $sheet['gid'];
        }

        $targetType = $targetTypeId ? $types->firstWhere('id', $targetTypeId) : null;
        $syncTypes = $targetType ? collect([$targetType]) : $types;

        $results = [
            'total_synced' => 0,
            'synced_types' => [],
            'errors' => [],
            'skipped' => [],
        ];

        foreach ($syncTypes as $type) {
            $workbookKey = mb_strtolower(trim($type->workbook_name));
            $gid = $sheetMap[$workbookKey] ?? null;

            // If GID not found via discovered sheets and this is single type with GID in URL
            if (!$gid && $targetType && self::extractGid($spreadsheetUrlOrId)) {
                $gid = self::extractGid($spreadsheetUrlOrId);
            }

            if (!$gid) {
                $results['skipped'][] = "Sheet untuk '{$type->workbook_name}' tidak ditemukan di Google Spreadsheet.";
                continue;
            }

            try {
                $count = $this->syncSheetByType($spreadsheetId, $gid, $type, $year);
                $results['total_synced'] += $count;
                $results['synced_types'][] = [
                    'type_name' => $type->type_name,
                    'workbook_name' => $type->workbook_name,
                    'count' => $count,
                ];
            } catch (Throwable $e) {
                $results['errors'][] = "Gagal memproses sheet '{$type->workbook_name}': " . $e->getMessage();
                Log::error("Google Spreadsheet sync error for {$type->workbook_name}: " . $e->getMessage());
            }
        }

        return $results;
    }

    /**
     * Fetch CSV and sync a single sheet into database
     */
    public function syncSheetByType(string $spreadsheetId, string $gid, LetterNumberType $type, int $year): int
    {
        $csvUrl = "https://docs.google.com/spreadsheets/d/{$spreadsheetId}/export?format=csv&gid={$gid}";

        $response = Http::timeout(25)->get($csvUrl);
        if (!$response->successful()) {
            throw new \RuntimeException("Gagal mengunduh CSV dari Google Spreadsheet (HTTP {$response->status()}). Pastikan sheet diset 'Anyone with link can view'.");
        }

        $csvContent = $response->body();
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $csvContent);
        rewind($stream);

        $rows = [];
        while (($data = fgetcsv($stream, 0, ',')) !== false) {
            $rows[] = $data;
        }
        fclose($stream);

        if (empty($rows)) {
            return 0;
        }

        $headerRowIndex = $this->findHeaderRowIndex($rows);
        if ($headerRowIndex === null) {
            throw new \RuntimeException("Baris header tabel tidak ditemukan pada sheet '{$type->workbook_name}'.");
        }

        $columnMap = $this->buildColumnMap($rows[$headerRowIndex]);
        $syncedCount = 0;
        $minSequence = null;
        $maxSequence = null;

        DB::beginTransaction();
        try {
            for ($r = $headerRowIndex + 1; $r < count($rows); $r++) {
                $row = $rows[$r];
                if (empty(array_filter($row, fn($v) => trim((string) $v) !== ''))) {
                    continue;
                }

                $data = $this->mapRow($row, $columnMap);
                $sequenceRaw = $data['nomor_urut'] ?? null;
                if ($sequenceRaw === null || $sequenceRaw === '' || (int) $sequenceRaw <= 0) {
                    continue;
                }

                $this->upsertRow($type, $data, $year);
                $syncedCount++;

                $seq = (int) $sequenceRaw;
                $minSequence = $minSequence === null ? $seq : min($minSequence, $seq);
                $maxSequence = $maxSequence === null ? $seq : max($maxSequence, $seq);
            }

            if ($minSequence !== null && $maxSequence !== null) {
                // Upsert availability batch
                LetterNumberAvailabilityBatch::updateOrCreate(
                    [
                        'type_id' => $type->id,
                        'number_year' => $year,
                        'start_sequence' => $minSequence,
                        'end_sequence' => $maxSequence,
                    ],
                    [
                        'purpose' => 'available',
                        'period_month' => now()->toDateString(),
                        'status' => 'active',
                        'notes' => "Tarik otomatis Google Spreadsheet: {$syncedCount} nomor (rentang {$minSequence}–{$maxSequence}).",
                        'created_by' => Auth::id() ?? 1,
                    ]
                );
            }

            DB::commit();
            return $syncedCount;
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function findHeaderRowIndex(array $rows): ?int
    {
        foreach ($rows as $i => $row) {
            $normalized = array_map(fn($c) => mb_strtolower(trim(preg_replace('/\s+/', ' ', (string) $c))), $row);
            $hasNo = in_array('no', $normalized, true) || in_array('no.', $normalized, true);
            $hasUrut = in_array('nomor urut', $normalized, true) || in_array('no urut', $normalized, true) || in_array('no. urut', $normalized, true);
            $hasPerihal = in_array('perihal', $normalized, true) || in_array('perihal surat', $normalized, true) || in_array('hal', $normalized, true);

            if (($hasNo && $hasUrut) || ($hasUrut && $hasPerihal) || ($hasNo && $hasPerihal)) {
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

    private function upsertRow(LetterNumberType $type, array $data, int $defaultYear): void
    {
        $sequence = (int) ($data['nomor_urut'] ?? 0);
        if ($sequence <= 0) {
            return;
        }

        $letterDate = $this->parseDate($data['tanggal_surat'] ?? null);
        $incomingDate = $this->parseDate($data['tanggal_masuk'] ?? null) ?? $letterDate ?? now()->toDateString();
        $year = $letterDate ? (int) date('Y', strtotime($letterDate)) : ($defaultYear ?: (int) date('Y'));

        $unitName = $this->clean($data['unit_pengolah_arsip'] ?? null);
        $unitId = $unitName ? Unit::where('unit_name', $unitName)->value('id') : null;

        $monthRaw = trim((string) ($data['bulan'] ?? ''));
        $monthNumber = is_numeric($monthRaw)
            ? (int) $monthRaw
            : (self::MONTHS[mb_strtolower($monthRaw)] ?? ($letterDate ? (int) date('n', strtotime($letterDate)) : now()->month));

        $rawNumberText = $this->clean($data['nomor_surat'] ?? null);
        $signatory = $this->clean($data['penandatangan_surat'] ?? null);
        $destination = $this->clean($data['tujuan_surat'] ?? null);
        $requestType = $this->clean($data['permohonan'] ?? null);
        $subject = $this->clean($data['perihal_surat'] ?? null);
        $technicalOfficer = $this->clean($data['petugas_unit_teknis'] ?? null);

        // Filter #N/A or empty values
        if ($rawNumberText && (stripos($rawNumberText, '#N/A') !== false || stripos($rawNumberText, '#REF') !== false)) {
            $rawNumberText = null;
        }
        if ($subject && (stripos($subject, '#N/A') !== false || stripos($subject, '#REF') !== false)) {
            $subject = null;
        }

        // Booking / Reservation detection
        $reservedFor = null;
        $isBooking = false;
        if ($technicalOfficer !== null && stripos($technicalOfficer, 'booking') !== false) {
            $isBooking = true;
            if (preg_match('/booking\s+(.+)/i', $technicalOfficer, $m)) {
                $reservedFor = trim($m[1]);
            }
        }
        if ($subject !== null && stripos($subject, 'booking') !== false) {
            $isBooking = true;
            if (preg_match('/booking\s+(.+)/i', $subject, $m)) {
                $reservedFor = trim($m[1]);
            }
        }

        $isPreorder = ($requestType !== null && (stripos($requestType, 'preorder') !== false || stripos($requestType, 'pre order') !== false))
            || ($technicalOfficer !== null && (stripos($technicalOfficer, 'preorder') !== false || stripos($technicalOfficer, 'pre order') !== false))
            || ($subject !== null && (stripos($subject, 'preorder') !== false || stripos($subject, 'pre order') !== false));

        $isReservation = $isBooking
            || ($requestType !== null && (stripos($requestType, 'reservasi') !== false || stripos($requestType, 'reserve') !== false))
            || ($technicalOfficer !== null && stripos($technicalOfficer, 'reservasi') !== false);

        $hasLetterContent = ($subject !== null || $unitName !== null || $letterDate !== null || $signatory !== null || $rawNumberText !== null);

        $status = match (true) {
            $isReservation => 'reserved',
            $isPreorder => 'preorder',
            $hasLetterContent => 'used',
            default => 'available',
        };
        $isUsed = ($status === 'used');

        $securityAccess = ($v = $this->clean($data['keamanan_akses'] ?? null)) ? strtoupper($v) : null;
        $classificationCode = ($v = $this->clean($data['kode_klas_arsip'] ?? null)) ? strtoupper($v) : null;

        $numberText = $rawNumberText;
        if ($status !== 'available' && !$numberText) {
            $numberText = LetterNumberService::buildNumberText($type, [
                'sequence_number' => $sequence,
                'number_year' => $year,
                'signer_code' => $type->default_signer_code ?: '1',
                'month_number' => $monthNumber,
                'security_access' => $securityAccess ?? '',
                'classification_code' => $classificationCode ?? '',
            ]);
        }
        if ($status === 'available') {
            $numberText = null;
        }

        $payload = [
            'incoming_date' => $status !== 'available' ? $incomingDate : null,
            'unit_id' => $status !== 'available' ? $unitId : null,
            'processing_unit_text' => $status !== 'available' ? $unitName : null,
            'signatory' => $status !== 'available' ? $signatory : null,
            'request_type' => $status !== 'available' ? $requestType : null,
            'destination' => $status !== 'available' ? $destination : null,
            'letter_date' => $status !== 'available' ? $letterDate : null,
            'security_access' => $status !== 'available' ? $securityAccess : null,
            'classification_code' => $status !== 'available' ? $classificationCode : null,
            'month_number' => $status !== 'available' ? $monthNumber : null,
            'number_text' => $numberText,
            'subject' => $status !== 'available' ? $subject : null,
            'technical_officer' => $status !== 'available' ? $technicalOfficer : null,
            'nd_pengantar' => ($status !== 'available' && $type->extra_field === 'nd_pengantar') ? $this->clean($data['nd_pengantar'] ?? null) : null,
            'scan_result' => ($status !== 'available' && $type->extra_field === 'scan_result')
                ? ($this->clean($data['scan_result'] ?? null) ?? $this->clean($data['nd_pengantar'] ?? null))
                : null,
            'status' => $status,
            'reserved_for' => $status === 'reserved' ? $reservedFor : null,
            'reserved_at' => in_array($status, ['reserved', 'preorder'], true) ? now() : null,
            'used_at' => $isUsed ? now() : null,
            'created_by' => Auth::id() ?? 1,
        ];

        $letterNumber = LetterNumber::updateOrCreate(
            [
                'type_id' => $type->id,
                'number_year' => $year,
                'sequence_number' => $sequence,
            ],
            $payload
        );

        // Sync with Letter model for tracking & tindak lanjut
        if ($status !== 'available' && ($numberText || $subject || $letterDate)) {
            $senderName = $technicalOfficer ?: ($reservedFor ?: ($signatory ?: 'Petugas Unit'));
            $letterSubject = $subject ?: ($status === 'reserved' ? ('[Reservasi] ' . ($reservedFor ?: 'Nomor Surat')) : ($status === 'preorder' ? '[Pre-Order] Alokasi Nomor' : 'Nomor Surat Terpakai'));
            $initialStatus = in_array($status, ['preorder', 'reserved'], true) ? 'Diregistrasi' : 'Diterima';

            $letterData = [
                'letter_number' => $numberText ?: sprintf('%04d', $sequence),
                'letter_number_type_id' => $type->id,
                'letter_type' => 'in',
                'process_lane' => 'signature',
                'sender_unit' => $unitName ?: 'Unit Pengolah Arsip',
                'sender_name' => $senderName,
                'recipient_unit_id' => $unitId,
                'subject' => $letterSubject,
                'letter_date' => $letterDate ?: now()->toDateString(),
                'received_date' => $incomingDate ?: now()->toDateString(),
                'priority' => 'Biasa',
                'security_level' => $securityAccess ?: 'Biasa',
                'status' => $initialStatus,
                'current_position' => 'Tata Usaha Sekjen',
                'requested_actions' => ($type->type_code === 'ND_MEMO' ? 'Mohon Paraf' : 'Mohon Tanda Tangan'),
                'archive_classification_code' => $classificationCode,
                'signatory_name' => $signatory,
                'technical_officer' => $technicalOfficer,
                'destination' => $destination,
                'letter_source' => 'Google Spreadsheet',
                'created_by' => Auth::id() ?? 1,
            ];

            if ($letterNumber->linked_letter_id) {
                $letter = Letter::find($letterNumber->linked_letter_id);
                if ($letter) {
                    $letter->update($letterData);
                } else {
                    $letterData['tracking_code'] = LetterNumberService::generateTrackingCode($type->type_code);
                    $letterData['agenda_number'] = LetterNumberService::nextAgendaNumber('in');
                    $newLetter = Letter::create($letterData);
                    $letterNumber->update(['linked_letter_id' => $newLetter->id]);

                    LetterStatusLog::create([
                        'letter_id' => $newLetter->id,
                        'status' => $initialStatus,
                        'position' => 'Tata Usaha Sekjen',
                        'note' => 'Data surat ditarik otomatis dari Google Spreadsheet.',
                        'changed_by' => Auth::user()?->name ?: 'Admin',
                        'changed_at' => now(),
                    ]);
                }
            } else {
                $letterData['tracking_code'] = LetterNumberService::generateTrackingCode($type->type_code);
                $letterData['agenda_number'] = LetterNumberService::nextAgendaNumber('in');
                $newLetter = Letter::create($letterData);
                $letterNumber->update(['linked_letter_id' => $newLetter->id]);

                LetterStatusLog::create([
                    'letter_id' => $newLetter->id,
                    'status' => $initialStatus,
                    'position' => 'Tata Usaha Sekjen',
                    'note' => 'Data surat ditarik otomatis dari Google Spreadsheet.',
                    'changed_by' => Auth::user()?->name ?: 'Admin',
                    'changed_at' => now(),
                ]);
            }
        }
    }

    private function clean(?string $val): ?string
    {
        if ($val === null) {
            return null;
        }
        $val = trim($val);
        return $val === '' ? null : $val;
    }

    private function parseDate($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        $str = trim((string) $value);

        // Format: 02 Januari 2026 / 2 Jan 2026
        if (preg_match('/^(\d{1,2})\s+([a-zA-Z]+)\s+(\d{4})$/u', $str, $m)) {
            $day = (int) $m[1];
            $monthName = mb_strtolower($m[2]);
            $year = (int) $m[3];
            $month = self::MONTHS[$monthName] ?? null;
            if ($month) {
                return sprintf('%04d-%02d-%02d', $year, $month, $day);
            }
        }

        // Format: 6-Jan-26 or 06-Jan-2026
        if (preg_match('/^(\d{1,2})-([a-zA-Z]+)-(\d{2,4})$/u', $str, $m)) {
            $day = (int) $m[1];
            $monthName = mb_strtolower($m[2]);
            $year = (int) $m[3];
            if ($year < 100) {
                $year += 2000;
            }
            $month = self::MONTHS[$monthName] ?? null;
            if ($month) {
                return sprintf('%04d-%02d-%02d', $year, $month, $day);
            }
        }

        // Format: M/D/Y (e.g. 1/2/2026 or 1/5/2026)
        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $str, $m)) {
            $mOrD1 = (int) $m[1];
            $mOrD2 = (int) $m[2];
            $year = (int) $m[3];

            // In Google Sheets Indonesian / US locale: 1/2/2026 usually means M/D/Y or D/M/Y
            // If first number > 12, it must be day
            if ($mOrD1 > 12) {
                return sprintf('%04d-%02d-%02d', $year, $mOrD2, $mOrD1);
            }
            // By default Google Sheets export uses Month/Day/Year
            return sprintf('%04d-%02d-%02d', $year, $mOrD1, $mOrD2);
        }

        $time = strtotime($str);
        return $time !== false ? date('Y-m-d', $time) : null;
    }
}
