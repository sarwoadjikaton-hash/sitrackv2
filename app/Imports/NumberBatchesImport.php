<?php

namespace App\Imports;

use App\Models\LetterNumber;
use App\Models\LetterNumberAvailabilityBatch;
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
use Throwable;

/**
 * Expected Excel columns (header row):
 * type_code | number_year | purpose | start_number | end_number | period_date |
 * unit_name | pic_name | notes
 *
 * type_code : must match an existing letter_number_types.type_code
 * purpose   : available | preorder | reservation   (default: available)
 * for "reservation" rows, start_number == end_number (single slot)
 */
class NumberBatchesImport implements ToCollection, WithHeadingRow, SkipsOnError, SkipsOnFailure, SkipsEmptyRows
{
    use Importable;

    public int $imported = 0;
    public int $slotsCreated = 0;

    /** @var array<int, array{row:int, errors:array}> */
    public array $failures = [];

    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2; // account for heading row

            try {
                $this->importRow($row, $rowNumber);
            } catch (Throwable $e) {
                $this->failures[] = ['row' => $rowNumber, 'errors' => [$e->getMessage()]];
            }
        }
    }

    private function importRow(Collection $row, int $rowNumber): void
    {
        $typeCode = trim((string) $row->get('type_code', ''));
        $type = LetterNumberType::where('type_code', strtoupper($typeCode))->where('is_active', true)->first();

        if (!$type) {
            throw new \RuntimeException("type_code '{$typeCode}' tidak ditemukan atau nonaktif.");
        }

        $year = (int) $row->get('number_year', now()->year);
        $purposeRaw = $row->get('purpose');
        $purpose = in_array($purposeRaw, ['available', 'preorder', 'reservation'], true)
            ? $purposeRaw : 'available';

        $start = (int) $row->get('start_number', 0);
        $end = (int) $row->get('end_number', $start);

        if ($start <= 0 || $end < $start) {
            throw new \RuntimeException('start_number / end_number tidak valid.');
        }

        $unitName = trim((string) $row->get('unit_name', ''));
        $unitId = $unitName !== '' ? Unit::where('unit_name', $unitName)->value('id') : null;

        $periodDate = $this->parseDate($row->get('period_date')) ?? now()->toDateString();
        $signer = $type->default_signer_code ?: '1';
        $userId = Auth::id();

        DB::transaction(function () use ($type, $year, $purpose, $start, $end, $unitId, $unitName, $row, $periodDate, $signer, $userId) {
            $clash = LetterNumber::where('type_id', $type->id)
                ->where('number_year', $year)
                ->whereBetween('sequence_number', [$start, $end])
                ->exists();

            if ($clash && $purpose === 'available') {
                throw new \RuntimeException("Sebagian nomor {$start}-{$end} sudah terdaftar untuk {$type->type_code}/{$year}.");
            }

            LetterNumberAvailabilityBatch::create([
                'type_id' => $type->id,
                'number_year' => $year,
                'purpose' => $purpose,
                'start_sequence' => $start,
                'end_sequence' => $end,
                'period_date' => $periodDate,
                'unit_id' => $unitId,
                'unit_text' => $unitName ?: null,
                'pic_name' => $row->get('pic_name'),
                'notes' => $row->get('notes'),
                'created_by' => $userId,
            ]);

            if ($purpose === 'available') {
                $rows = [];
                for ($n = $start; $n <= $end; $n++) {
                    $rows[] = [
                        'type_id' => $type->id,
                        'number_year' => $year,
                        'sequence_number' => $n,
                        'status' => 'available',
                        'signer_code' => $signer,
                        'created_by' => $userId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
                foreach (array_chunk($rows, 500) as $chunk) {
                    LetterNumber::insert($chunk);
                }
                $this->slotsCreated += count($rows);
            } else {
                $updated = LetterNumber::where('type_id', $type->id)
                    ->where('number_year', $year)
                    ->whereBetween('sequence_number', [$start, $end])
                    ->where('status', 'available')
                    ->update([
                        'status' => 'reserved',
                        'unit_id' => $unitId,
                        'processing_unit_text' => $unitName ?: null,
                        'reserved_for' => $row->get('pic_name'),
                        'reserved_at' => now(),
                    ]);

                if ($updated === 0) {
                    throw new \RuntimeException("Tidak ada nomor tersedia di rentang {$start}-{$end} untuk direservasi.");
                }
                $this->slotsCreated += $updated;
            }

            $this->imported++;
        });
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

    private function parseDate(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        try {
            return \Carbon\Carbon::parse($value)->toDateString();
        } catch (Throwable) {
            return null;
        }
    }
}