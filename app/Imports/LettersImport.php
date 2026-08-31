<?php

namespace App\Imports;

use App\Models\Letter;
use App\Models\LetterCategory;
use App\Models\LetterStatusLog;
use App\Models\Unit;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithChunkReading; // Tambahan untuk efisiensi
use Maatwebsite\Excel\Validators\Failure;
use Throwable;

class LettersImport implements ToCollection, WithHeadingRow, WithValidation, SkipsOnError, SkipsOnFailure, SkipsEmptyRows, WithChunkReading
{
    use Importable;

    public int $imported = 0;
    public array $failures = [];

    /**
     * Proses data setelah Excel dibaca menjadi koleksi
     */
    public function collection(\Illuminate\Support\Collection $rows): void
    {
        DB::transaction(function () use ($rows) {
            foreach ($rows as $row) {
                // Mencari ID Kategori
                $categoryName = trim((string) $row->get('category', ''));
                $categoryId = $categoryName !== ''
                    ? LetterCategory::where('category_name', 'ILIKE', $categoryName)->value('id')
                    : null;

                // Mencari ID Unit Tujuan
                $recipientUnitName = trim((string) $row->get('recipient_unit', ''));
                $recipientUnitId = $recipientUnitName !== ''
                    ? Unit::where('unit_name', 'ILIKE', $recipientUnitName)->value('id')
                    : null;

                $letterSource = $row->get('letter_source');
                $priority = $row->get('priority');

                // Buat record Surat
                $letter = Letter::create([
                    'tracking_code' => $this->generateTrackingCode(),
                    'agenda_number' => $this->nextAgendaNumber(),
                    'process_lane' => 'disposition',
                    'letter_source' => in_array($letterSource, ['Manual', 'SRIKANDI'], true) ? $letterSource : 'Manual',
                    'letter_type' => 'in',
                    'sender_unit' => $row->get('sender_unit'),
                    'category_id' => $categoryId,
                    'sender_name' => $row->get('sender_name'),
                    'sender_phone' => $row->get('sender_phone'),
                    'recipient_unit_id' => $recipientUnitId,
                    'subject' => (string) ($row->get('subject') ?: '(tanpa perihal)'),
                    'letter_date' => $this->parseDate($row->get('letter_date')),
                    'received_date' => $this->parseDate($row->get('received_date')) ?? now()->toDateString(),
                    'priority' => in_array($priority, ['urgent', 'high', 'normal', 'low'], true) ? $priority : 'normal',
                    'security_level' => $row->get('security_level', 'Biasa'),
                    'status' => 'Surat Diterima TU',
                    'current_position' => 'TU Sekjen',
                    'notes' => $row->get('notes'),
                    'created_by' => Auth::id(),
                ]);

                // Buat Log Status awal
                LetterStatusLog::create([
                    'letter_id' => $letter->id,
                    'status' => 'Surat Diterima TU',
                    'position' => 'TU Sekjen',
                    'note' => 'Diimpor secara massal dari Excel.',
                    'changed_by' => Auth::user()?->username ?? 'system',
                    'changed_at' => now(),
                ]);

                $this->imported++;
            }
        });
    } // <--- PASTIKAN KURUNG INI ADA. Jika hilang, 'private' di bawah akan error.

    public function rules(): array
    {
        return [
            '*.subject' => ['required', 'string', 'max:255'],
            '*.priority' => ['nullable', 'in:urgent,high,normal,low'],
            '*.letter_source' => ['nullable', 'in:Manual,SRIKANDI'],
        ];
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

    /* 
    |--------------------------------------------------------------------------
    | HELPER METHODS (Private)
    |--------------------------------------------------------------------------
    */

    private function parseDate($value): ?string
    {
        if (!$value)
            return null;

        try {
            // Menangani jika Excel mengirim format angka (serial date) atau string
            if (is_numeric($value)) {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value)->format('Y-m-d');
            }
            return \Carbon\Carbon::parse($value)->toDateString();
        } catch (Throwable) {
            return null;
        }
    }

    private function generateTrackingCode(): string
    {
        $today = now()->format('Ymd');
        $prefix = "TUS-{$today}-";
        $last = Letter::where('tracking_code', 'LIKE', "{$prefix}%")
            ->orderByDesc('tracking_code')
            ->first();

        $next = 1;
        if ($last) {
            $lastNum = (int) Str::afterLast($last->tracking_code, '-');
            $next = $lastNum + 1;
        }

        return $prefix . str_pad((string) $next, 3, '0', STR_PAD_LEFT);
    }

    private function nextAgendaNumber(): string
    {
        $year = now()->year;
        $pattern = "AG-M-{$year}-";
        $last = Letter::where('agenda_number', 'LIKE', "{$pattern}%")
            ->orderByDesc('agenda_number')
            ->first();

        $next = 1;
        if ($last) {
            $lastNum = (int) Str::afterLast($last->agenda_number, '-');
            $next = $lastNum + 1;
        }

        return sprintf('AG-M-%s-%04d', $year, $next);
    }

    public function chunkSize(): int
    {
        return 1000;
    }
}