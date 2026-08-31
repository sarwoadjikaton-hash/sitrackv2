<?php

namespace App\Services;

use App\Models\LetterNumber;
use App\Models\LetterNumberType;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class LetterNumberService
{
    /**
     * Convert integer month to Roman numeral
     */
    public static function romanMonth(int $month): string
    {
        return match ($month) {
            1 => 'I',
            2 => 'II',
            3 => 'III',
            4 => 'IV',
            5 => 'V',
            6 => 'VI',
            7 => 'VII',
            8 => 'VIII',
            9 => 'IX',
            10 => 'X',
            11 => 'XI',
            12 => 'XII',
            default => '',
        };
    }

    /**
     * Build formatted letter number string from pattern and variables
     */
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

        // Remove any unused placeholders like {something_else}
        $result = preg_replace('/\{[a-zA-Z0-9_]+\}/', '', $result);

        // Clean up double dashes or slashes resulting from empty security code
        $result = preg_replace('/^-+/', '', $result);
        $result = preg_replace('/\/+/', '/', $result);

        return trim($result);
    }

    /**
     * Generate unique tracking code: tus-YYYYMMDD-XXX
     */
    public static function generateTrackingCode(): string
    {
        $today = date('Ymd');
        $prefix = 'TUS-' . $today . '-';

        $lastCode = DB::table('letters')
            ->where('tracking_code', 'LIKE', $prefix . '%')
            ->orderBy('tracking_code', 'desc')
            ->value('tracking_code');

        $nextNumber = 1;
        if ($lastCode) {
            $parts = explode('-', $lastCode);
            $nextNumber = ((int) end($parts)) + 1;
        }

        return $prefix . str_pad((string) $nextNumber, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Generate agenda number: AG-K-YYYY-0001 (out) or AG-M-YYYY-0001 (in)
     */
    public static function nextAgendaNumber(string $type = 'in'): string
    {
        $prefix = ($type === 'out') ? 'K' : 'M';
        $year = date('Y');
        $pattern = "AG-$prefix-$year-%";

        $lastAgenda = DB::table('letters')
            ->where('agenda_number', 'LIKE', $pattern)
            ->orderBy('agenda_number', 'desc')
            ->value('agenda_number');

        $nextNumber = 1;
        if ($lastAgenda) {
            $parts = explode('-', $lastAgenda);
            $nextNumber = ((int) end($parts)) + 1;
        }

        return sprintf('AG-%s-%s-%04d', $prefix, $year, $nextNumber);
    }
}
