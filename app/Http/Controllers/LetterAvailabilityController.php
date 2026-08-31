<?php

namespace App\Http\Controllers;

use App\Models\LetterNumber;
use App\Models\LetterNumberAvailabilityBatch;
use App\Models\LetterNumberType;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class LetterAvailabilityController extends Controller
{
    /**
     * Display letter number availability view
     */
    public function index(Request $request): Response
    {
        $year = (int) ($request->input('year', date('Y')));
        if ($year < 2000 || $year > 2200) {
            $year = (int) date('Y');
        }

        $types = LetterNumberType::where('is_active', true)
            ->orderBy('display_order', 'asc')
            ->orderBy('type_name', 'asc')
            ->get();

        $units = Unit::where('is_active', true)
            ->orderBy('unit_name', 'asc')
            ->get();

        // Get available sequence numbers by type
        $availableSlots = LetterNumber::where('number_year', $year)
            ->where('status', 'available')
            ->orderBy('sequence_number', 'asc')
            ->get(['type_id', 'sequence_number'])
            ->groupBy('type_id')
            ->map(fn ($group) => $group->pluck('sequence_number')->all());

        // Get all batches with slot counts
        $batches = LetterNumberAvailabilityBatch::with(['type', 'unit', 'creator'])
            ->where('number_year', $year)
            ->orderBy('id', 'desc')
            ->get()
            ->map(function ($batch) {
                $counts = DB::table('letter_numbers')
                    ->where('type_id', $batch->type_id)
                    ->where('number_year', $batch->number_year)
                    ->whereBetween('sequence_number', [$batch->start_sequence, $batch->end_sequence])
                    ->selectRaw("
                        COUNT(*) as total,
                        SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) as available,
                        SUM(CASE WHEN status = 'reserved' THEN 1 ELSE 0 END) as reserved,
                        SUM(CASE WHEN status = 'used' THEN 1 ELSE 0 END) as used
                    ")
                    ->first();

                $batch->slot_total = (int) ($counts->total ?? 0);
                $batch->slot_available = (int) ($counts->available ?? 0);
                $batch->slot_reserved = (int) ($counts->reserved ?? 0);
                $batch->slot_used = (int) ($counts->used ?? 0);

                return $batch;
            })
            ->groupBy('type_id');

        $openTypeId = (int) ($request->input('open_type', $types->first()?->id ?? 0));

        return Inertia::render('KetersediaanNomor/Index', [
            'year' => $year,
            'types' => $types,
            'units' => $units,
            'availableSlots' => $availableSlots,
            'batchesByType' => $batches,
            'openTypeId' => $openTypeId,
        ]);
    }

    /**
     * Store a new availability batch (Tersedia, Pre-Order, Reservasi)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'type_id' => ['required', 'exists:letter_number_types,id'],
            'number_year' => ['required', 'integer', 'min:2000', 'max:2200'],
            'purpose' => ['required', 'in:available,preorder,reservation'],
            'start_number' => ['nullable', 'integer', 'min:1'],
            'end_number' => ['nullable', 'integer', 'min:1'],
            'preorder_start_number' => ['nullable', 'integer', 'min:1'],
            'preorder_end_number' => ['nullable', 'integer', 'min:1'],
            'reservation_sequence_number' => ['nullable', 'integer', 'min:1'],
            'unit_id' => ['nullable', 'exists:units,id'],
            'unit_text' => ['nullable', 'string', 'max:150'],
            'pic_name' => ['nullable', 'string', 'max:150'],
            'period_date' => ['nullable', 'date'],
            'letter_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $typeId = (int) $validated['type_id'];
        $year = (int) $validated['number_year'];
        $purpose = $validated['purpose'];
        $unitId = !empty($validated['unit_id']) ? (int) $validated['unit_id'] : null;
        $unitText = $validated['unit_text'] ?? null;
        $picName = $validated['pic_name'] ?? null;
        $notes = $validated['notes'] ?? null;
        $periodDate = $validated['period_date'] ?? date('Y-m-d');
        $userId = Auth::id();

        $type = LetterNumberType::findOrFail($typeId);
        $signerCode = $type->default_signer_code ?: '1';

        DB::beginTransaction();
        try {
            if ($purpose === 'available') {
                $start = (int) ($validated['start_number'] ?? 0);
                $end = (int) ($validated['end_number'] ?? 0);

                if ($start <= 0 || $end < $start) {
                    throw new RuntimeException('Rentang nomor tidak valid.');
                }

                $exists = LetterNumber::where('type_id', $typeId)
                    ->where('number_year', $year)
                    ->whereBetween('sequence_number', [$start, $end])
                    ->exists();

                if ($exists) {
                    throw new RuntimeException('Sebagian nomor dalam rentang tersebut sudah terdaftar.');
                }

                LetterNumberAvailabilityBatch::create([
                    'type_id' => $typeId,
                    'number_year' => $year,
                    'purpose' => 'available',
                    'start_sequence' => $start,
                    'end_sequence' => $end,
                    'period_month' => $periodDate,
                    'status' => 'active',
                    'notes' => $notes,
                    'created_by' => $userId,
                ]);

                $insertRows = [];
                $now = now();
                for ($n = $start; $n <= $end; $n++) {
                    $insertRows[] = [
                        'type_id' => $typeId,
                        'number_year' => $year,
                        'sequence_number' => $n,
                        'status' => 'available',
                        'signer_code' => $signerCode,
                        'created_by' => $userId,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                foreach (array_chunk($insertRows, 500) as $chunk) {
                    LetterNumber::insert($chunk);
                }
            } else {
                $start = ($purpose === 'reservation')
                    ? (int) ($validated['reservation_sequence_number'] ?? 0)
                    : (int) ($validated['preorder_start_number'] ?? 0);

                $end = ($purpose === 'reservation')
                    ? $start
                    : (int) ($validated['preorder_end_number'] ?? 0);

                $letterDate = ($purpose === 'reservation') ? ($validated['letter_date'] ?? null) : null;

                if ($start <= 0 || $end < $start) {
                    throw new RuntimeException('Pilih nomor urut yang tersedia.');
                }

                // Verify all slots are available with row lock
                $lockedCount = LetterNumber::where('type_id', $typeId)
                    ->where('number_year', $year)
                    ->whereBetween('sequence_number', [$start, $end])
                    ->where('status', 'available')
                    ->lockForUpdate()
                    ->count();

                $expectedCount = ($end - $start + 1);
                if ($lockedCount !== $expectedCount) {
                    throw new RuntimeException('Gagal! Sebagian atau seluruh nomor dalam rentang sudah terpakai/direservasi.');
                }

                LetterNumber::where('type_id', $typeId)
                    ->where('number_year', $year)
                    ->whereBetween('sequence_number', [$start, $end])
                    ->where('status', 'available')
                    ->update([
                        'status' => 'reserved',
                        'unit_id' => $unitId,
                        'processing_unit_text' => $unitText,
                        'reserved_for' => $picName,
                        'letter_date' => $letterDate,
                        'reserved_at' => now(),
                    ]);

                LetterNumberAvailabilityBatch::create([
                    'type_id' => $typeId,
                    'number_year' => $year,
                    'purpose' => $purpose,
                    'start_sequence' => $start,
                    'end_sequence' => $end,
                    'period_month' => $periodDate,
                    'letter_date' => $letterDate,
                    'unit_id' => $unitId,
                    'unit_text' => $unitText,
                    'pic_name' => $picName,
                    'status' => 'active',
                    'notes' => $notes,
                    'created_by' => $userId,
                ]);
            }

            DB::commit();

            return redirect()->route('ketersediaan-nomor.index', ['year' => $year, 'open_type' => $typeId])
                ->with('success', 'Ketersediaan nomor berhasil disimpan.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Delete an availability batch
     */
    public function destroy($id)
    {
        $batch = LetterNumberAvailabilityBatch::findOrFail($id);

        DB::beginTransaction();
        try {
            $usedCount = LetterNumber::where('type_id', $batch->type_id)
                ->where('number_year', $batch->number_year)
                ->whereBetween('sequence_number', [$batch->start_sequence, $batch->end_sequence])
                ->where('status', 'used')
                ->count();

            if ($usedCount > 0) {
                throw new RuntimeException('Tidak dapat dihapus karena ' . $usedCount . ' nomor sudah digunakan dalam Data Surat.');
            }

            if ($batch->purpose === 'available') {
                LetterNumber::where('type_id', $batch->type_id)
                    ->where('number_year', $batch->number_year)
                    ->whereBetween('sequence_number', [$batch->start_sequence, $batch->end_sequence])
                    ->where('status', 'available')
                    ->delete();
            } else {
                LetterNumber::where('type_id', $batch->type_id)
                    ->where('number_year', $batch->number_year)
                    ->whereBetween('sequence_number', [$batch->start_sequence, $batch->end_sequence])
                    ->where('status', 'reserved')
                    ->update([
                        'status' => 'available',
                        'unit_id' => null,
                        'processing_unit_text' => null,
                        'reserved_for' => null,
                        'letter_date' => null,
                        'reserved_at' => null,
                    ]);
            }

            $typeId = $batch->type_id;
            $year = $batch->number_year;
            $batch->delete();

            DB::commit();

            return redirect()->route('ketersediaan-nomor.index', ['year' => $year, 'open_type' => $typeId])
                ->with('success', 'Data ketersediaan berhasil dihapus.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }
}
