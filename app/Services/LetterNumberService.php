<?php

namespace App\Services;

use App\Models\LetterNumber;
use App\Models\LetterNumberType;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class LetterNumberService
{
    public static function romanMonth(int $month): string
    {
        return match ($month) {
            1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV',
            5 => 'V', 6 => 'VI', 7 => 'VII', 8 => 'VIII',
            9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII',
            default => '',
        };
    }

    public static function buildNumberText(LetterNumberType $type, array $data): string
    {
        $pattern = trim($type->number_pattern ?? '');
        $padding = (int) ($type->sequence_padding ?? 4);

        $seq = str_pad((string) ($data['sequence_number'] ?? 0), $padding, '0', STR_PAD_LEFT);
        $year = (string) ($data['number_year'] ?? date('Y'));
        $monthNum = (int) ($data['month_number'] ?? date('n'));
        $monthRoman = self::romanMonth($monthNum);
        $monthPadded = str_pad((string) $monthNum, 2, '0', STR_PAD_LEFT);
        $signer = (string) ($data['signer_code'] ?? ($type->default_signer_code ?? '1'));
        $security = (string) ($data['security_access'] ?? '');
        $classification = (string) ($data['classification_code'] ?? '');

        $replacements = [
            '{sequence}' => $seq,
            '{year}' => $year,
            '{month_roman}' => $monthRoman,
            '{month}' => $monthPadded,
            '{signer}' => $signer,
            '{security}' => $security,
            '{classification}' => $classification,
        ];

        $result = strtr($pattern, $replacements);
        $result = preg_replace('/\{[a-zA-Z0-9_]+\}/', '', $result);
        $result = preg_replace('/^-+/', '', $result);
        $result = preg_replace('/\/+/', '/', $result);

        return trim($result);
    }

    /**
     * Generate unique tracking code: {PREFIX}-YYYYMMDD-XXX
     *
     * @param string|null $prefixCode Kode jenis naskah (mis. type_code dari LetterNumberType).
     *                                 Kalau null/kosong, pakai prefix fallback generik.
     *
     * WAJIB dipanggil di dalam DB::beginTransaction()/DB::transaction() —
     * advisory lock ini transaction-scoped, cuma menahan request lain
     * selama transaksi pemanggil belum commit/rollback.
     */
    public static function generateTrackingCode(?string $prefixCode = null): string
    {
        $code = strtoupper(trim((string) $prefixCode));
        $code = preg_replace('/[^A-Z0-9_]/', '', $code);
        if ($code === '') {
            $code = 'DSP'; // fallback generik untuk surat tanpa jenis naskah spesifik
        }

        $today = date('Ymd');

        $lockKey = crc32('tracking_code_' . $code . '_' . $today);
        DB::select('SELECT pg_advisory_xact_lock(?)', [$lockKey]);

        $prefix = $code . '-' . $today . '-';

        $maxNumber = DB::table('letters')
            ->where('tracking_code', 'LIKE', $prefix . '%')
            ->selectRaw("MAX(CAST(substr(tracking_code, ?) AS INTEGER)) as max_num", [strlen($prefix) + 1])
            ->value('max_num');

        $nextNumber = ((int) $maxNumber) + 1;

        return $prefix . str_pad((string) $nextNumber, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Generate agenda number: AG-K-YYYY-0001 (out) or AG-M-YYYY-0001 (in)
     *
     * WAJIB dipanggil di dalam DB::beginTransaction()/DB::transaction() —
     * lihat catatan di generateTrackingCode().
     */
    public static function nextAgendaNumber(string $type = 'in'): string
    {
        $prefix = ($type === 'out') ? 'K' : 'M';
        $year = date('Y');
        $fixedPrefix = "AG-{$prefix}-{$year}-";

        $lockKey = crc32('agenda_number_' . $prefix . '_' . $year);
        DB::select('SELECT pg_advisory_xact_lock(?)', [$lockKey]);

        $maxNumber = DB::table('letters')
            ->where('agenda_number', 'LIKE', $fixedPrefix . '%')
            ->selectRaw("MAX(CAST(substr(agenda_number, ?) AS INTEGER)) as max_num", [strlen($fixedPrefix) + 1])
            ->value('max_num');

        $nextNumber = ((int) $maxNumber) + 1;

        return sprintf('AG-%s-%s-%04d', $prefix, $year, $nextNumber);
    }
}