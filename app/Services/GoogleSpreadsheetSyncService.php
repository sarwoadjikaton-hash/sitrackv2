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

    /**
     * Kirim (Push) data surat dari SiTrack ke Google Spreadsheet via Webhook / Apps Script
     */
    public function push(string $webhookUrl, ?int $targetTypeId = null, int $year = 2026, ?string $secretToken = null): array
    {
        $trimmedUrl = trim($webhookUrl);
        if (!filter_var($trimmedUrl, FILTER_VALIDATE_URL)) {
            throw new \RuntimeException('URL Webhook / Google Apps Script tidak valid.');
        }

        $types = LetterNumberType::query()
            ->where('is_active', true)
            ->when($targetTypeId > 0, fn($q) => $q->where('id', $targetTypeId))
            ->orderBy('display_order')
            ->get();

        $groupedByWorkbook = $types->groupBy('workbook_name');
        $sheetsPayload = [];
        $totalRecords = 0;

        foreach ($groupedByWorkbook as $workbookName => $typesInWorkbook) {
            $typeIds = $typesInWorkbook->pluck('id')->all();

            $numbers = LetterNumber::with(['type', 'unit'])
                ->whereIn('type_id', $typeIds)
                ->where('number_year', $year)
                ->whereIn('status', ['used', 'reserved', 'preorder'])
                ->orderBy('sequence_number', 'asc')
                ->get();

            $rows = [];
            $rowCounter = 0;

            foreach ($numbers as $num) {
                $rowCounter++;
                $padding = $num->type?->sequence_padding ?? 4;
                $seqText = $num->sequence_number ? str_pad((string) $num->sequence_number, $padding, '0', STR_PAD_LEFT) : '-';

                $rows[] = [
                    'no_urut' => $rowCounter,
                    'tanggal_masuk' => $num->incoming_date ? \Carbon\Carbon::parse($num->incoming_date)->format('d/m/Y') : '',
                    'unit_pengolah_arsip' => $num->processing_unit_text ?: '-',
                    'penandatangan_surat' => $num->signatory ?: '-',
                    'permohonan' => $num->request_type ?: '-',
                    'tujuan_surat' => $num->destination ?: '-',
                    'tanggal_surat' => $num->letter_date ? \Carbon\Carbon::parse($num->letter_date)->format('d M Y') : '',
                    'keamanan_akses' => $num->security_access ?: 'B',
                    'nomor_urut' => $seqText,
                    'kode_klas_arsip' => $num->classification_code ?: 'UM.01',
                    'bulan' => $num->month_number ?: '',
                    'nomor_surat' => $num->number_text ?: ($num->status === 'reserved' ? '(Reservasi)' : '(Belum Ada Nomor)'),
                    'perihal_surat' => $num->subject ?: '-',
                    'petugas_unit_teknis' => $num->technical_officer ?: '-',
                    'status' => $num->status,
                ];
            }

            if (!empty($rows)) {
                $sheetsPayload[] = [
                    'sheet_name' => $workbookName,
                    'rows' => $rows,
                ];
                $totalRecords += count($rows);
            }
        }

        if (empty($sheetsPayload)) {
            return [
                'success' => true,
                'message' => 'Tidak ada data nomor untuk tahun ' . $year,
                'total_sent' => 0,
            ];
        }

        $postData = [
            'token' => $secretToken ?: 'SITRACK_SECRET_2026',
            'year' => $year,
            'sheets' => $sheetsPayload,
            'sent_at' => now()->toIso8601String(),
        ];

        try {
            $response = Http::timeout(45)
                ->withOptions(['allow_redirects' => true])
                ->post($trimmedUrl, $postData);

            if (!$response->successful()) {
                throw new \RuntimeException('Gagal mengirim ke Webhook: HTTP ' . $response->status());
            }

            $resJson = $response->json();
            if (isset($resJson['status']) && $resJson['status'] === 'error') {
                throw new \RuntimeException('Google Spreadsheet Error: ' . ($resJson['message'] ?? 'Gagal memproses data'));
            }

            return [
                'success' => true,
                'message' => $resJson['message'] ?? "Berhasil mengirim {$totalRecords} data ke Google Spreadsheet!",
                'total_sent' => $totalRecords,
                'response' => $resJson,
            ];
        } catch (Throwable $e) {
            Log::error("Error pushing data to Google Spreadsheet Webhook: " . $e->getMessage());
            throw new \RuntimeException($e->getMessage());
        }
    }

    /**
     * Dapatkan kode Google Apps Script siap pakai untuk dipasang di Spreadsheet
     */
    public static function getAppsScriptCode(?string $secretToken = 'SITRACK_SECRET_2026'): string
    {
        $tokenVal = json_encode($secretToken ?: 'SITRACK_SECRET_2026');
        return <<<JS
/**
 * ==========================================================================
 * SiTrack - Google Spreadsheet Two-Way Webhook Sync
 * ==========================================================================
 * CARA PASANG:
 * 1. Di Google Spreadsheet Anda, buka menu: Ekstensi (Extensions) > Apps Script
 * 2. Hapus semua kode yang ada di editor, lalu TEMPEL (Paste) seluruh kode ini.
 * 3. Klik tombol "Deploy" (di kanan atas) > "New deployment" (Deployment baru)
 * 4. Pilih tipe: "Web app" (ikon bola dunia)
 * 5. Isi Description: SiTrack Webhook Sync
 * 6. Execute as: "Me" (Email Google Anda)
 * 7. Who has access: "Anyone" (Siapa saja)
 * 8. Klik "Deploy", beri izin akses Google jika diminta, lalu SALIN Web app URL.
 * 9. Tempelkan URL tersebut ke pengaturan SiTrack!
 */

const SECRET_TOKEN = {$tokenVal};

function doPost(e) {
  try {
    if (!e || !e.postData || !e.postData.contents) {
      return ContentService.createTextOutput(JSON.stringify({ status: "error", message: "No data payload received" }))
        .setMimeType(ContentService.MimeType.JSON);
    }

    const payload = JSON.parse(e.postData.contents);

    // Validasi token keamanan
    if (SECRET_TOKEN && payload.token && payload.token !== SECRET_TOKEN) {
      return ContentService.createTextOutput(JSON.stringify({ status: "error", message: "Token keamanan tidak valid" }))
        .setMimeType(ContentService.MimeType.JSON);
    }

    const ss = SpreadsheetApp.getActiveSpreadsheet();
    const sheetsData = payload.sheets || [];
    let totalUpdated = 0;
    let totalInserted = 0;

    sheetsData.forEach(function (sheetItem) {
      const sheetName = sheetItem.sheet_name || "Data Surat";
      let sheet = ss.getSheetByName(sheetName);

      // Buat sheet baru jika belum ada
      if (!sheet) {
        sheet = ss.insertSheet(sheetName);
        initSheetHeaders(sheet, sheetName, payload.year || new Date().getFullYear());
      }

      const rows = sheetItem.rows || [];
      const res = upsertRows(sheet, rows);
      totalUpdated += res.updated;
      totalInserted += res.inserted;
    });

    return ContentService.createTextOutput(JSON.stringify({
      status: "success",
      message: "Sync berhasil! " + totalInserted + " baris baru ditambahkan, " + totalUpdated + " baris diperbarui.",
      total_inserted: totalInserted,
      total_updated: totalUpdated
    })).setMimeType(ContentService.MimeType.JSON);

  } catch (err) {
    return ContentService.createTextOutput(JSON.stringify({ status: "error", message: err.toString() }))
      .setMimeType(ContentService.MimeType.JSON);
  }
}

function initSheetHeaders(sheet, sheetName, year) {
  sheet.getRange("A1").setValue("REKAP NOMOR SURAT KELUAR (" + sheetName + ")");
  sheet.getRange("A1").setFontWeight("bold").setFontSize(14);
  
  sheet.getRange("A3").setValue("TAHUN " + year);
  sheet.getRange("A3").setFontWeight("bold").setFontColor("#1E40AF");

  const headers = [
    "No Urut", "Tanggal Masuk", "Unit Pengolah Arsip", "Penandatangan Surat",
    "Permohonan", "Tujuan Surat", "Tanggal Surat", "Keamanan Akses",
    "Nomor Urut", "Kode Klas. Arsip", "Bulan", "Nomor Surat",
    "Perihal Surat", "Petugas Unit Teknis"
  ];

  sheet.getRange(5, 1, 1, headers.length).setValues([headers]);
  const headerRange = sheet.getRange(5, 1, 1, headers.length);
  headerRange.setBackground("#1C386F").setFontColor("#FFFFFF").setFontWeight("bold");
  headerRange.setHorizontalAlignment("center");
}

function upsertRows(sheet, records) {
  if (!records || records.length === 0) return { updated: 0, inserted: 0 };

  const lastRow = Math.max(sheet.getLastRow(), 5);
  let updated = 0;
  let inserted = 0;

  // Baca baris yang sudah ada berdasarkan Nomor Urut (Kolom I) atau Nomor Surat (Kolom L)
  const existingMap = {};
  if (lastRow > 5) {
    const values = sheet.getRange(6, 1, lastRow - 5, 14).getValues();
    values.forEach(function (row, idx) {
      const seq = String(row[8] || "").trim();
      const numText = String(row[11] || "").trim();
      const rowNum = 6 + idx;
      if (seq && seq !== "-" && seq !== "") existingMap["seq_" + seq] = rowNum;
      if (numText && numText !== "-" && numText !== "(Belum Diberi Nomor)" && numText !== "") {
        existingMap["num_" + numText] = rowNum;
      }
    });
  }

  const newRows = [];

  records.forEach(function (rec, index) {
    const rowValues = [
      rec.no_urut || (index + 1),
      rec.tanggal_masuk || "",
      rec.unit_pengolah_arsip || "-",
      rec.penandatangan_surat || "-",
      rec.permohonan || "-",
      rec.tujuan_surat || "-",
      rec.tanggal_surat || "",
      rec.keamanan_akses || "B",
      rec.nomor_urut || "-",
      rec.kode_klas_arsip || "UM.01",
      rec.bulan || "",
      rec.nomor_surat || "(Belum Diberi Nomor)",
      rec.perihal_surat || "-",
      rec.petugas_unit_teknis || "-"
    ];

    const seqKey = rec.nomor_urut ? "seq_" + String(rec.nomor_urut).trim() : null;
    const numKey = (rec.nomor_surat && rec.nomor_surat !== "(Belum Diberi Nomor)") ? "num_" + String(rec.nomor_surat).trim() : null;

    let targetRowNum = null;
    if (seqKey && existingMap[seqKey]) {
      targetRowNum = existingMap[seqKey];
    } else if (numKey && existingMap[numKey]) {
      targetRowNum = existingMap[numKey];
    }

    if (targetRowNum) {
      sheet.getRange(targetRowNum, 1, 1, 14).setValues([rowValues]);
      updated++;
    } else {
      newRows.push(rowValues);
      inserted++;
    }
  });

  if (newRows.length > 0) {
    const startInsertRow = sheet.getLastRow() + 1;
    sheet.getRange(startInsertRow, 1, newRows.length, 14).setValues(newRows);
  }

  return { updated: updated, inserted: inserted };
}
JS;
    }
}

