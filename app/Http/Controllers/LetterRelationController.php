<?php

namespace App\Http\Controllers;

use App\Models\LetterRelation;
use App\Models\Letter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LetterRelationController extends Controller
{
    /**
     * Store a cross-lane letter relation
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'source_letter_id' => ['required', 'exists:letters,id'],
            'target_letter_id' => ['required', 'exists:letters,id', 'different:source_letter_id'],
            'relation_type' => ['required', 'in:TINDAK_LANJUT_DARI,BALASAN_DARI,HASIL_DISPOSISI_DARI,TERKAIT_DENGAN'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        LetterRelation::firstOrCreate(
            [
                'source_letter_id' => $validated['source_letter_id'],
                'target_letter_id' => $validated['target_letter_id'],
                'relation_type' => $validated['relation_type'],
            ],
            [
                'notes' => $validated['notes'] ?? null,
                'created_by' => Auth::id(),
            ]
        );

        return back()->with('success', 'Relasi dokumen berhasil ditambahkan.');
    }

    /**
     * Search letters to relate (excluding a given letter), with lane/type filter.
     */
    public function search(Request $request)
    {
        $validated = $request->validate([
            'exclude_id' => ['required', 'exists:letters,id'],
            'lane' => ['nullable', 'in:disposition,signature'],
            'letter_number_type_id' => ['nullable', 'exists:letter_number_types,id'],
            'q' => ['nullable', 'string', 'max:150'],
        ]);

        $query = Letter::query()
            ->where('id', '!=', $validated['exclude_id'])
            ->select('id', 'agenda_number', 'tracking_code', 'subject', 'process_lane', 'letter_number_type_id', 'created_at');

        if (!empty($validated['lane'])) {
            $query->where('process_lane', $validated['lane']);
        }

        if (!empty($validated['letter_number_type_id'])) {
            $query->where('letter_number_type_id', $validated['letter_number_type_id']);
        }

        $q = trim((string) ($validated['q'] ?? ''));
        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('agenda_number', 'ILIKE', "%{$q}%")
                    ->orWhere('tracking_code', 'ILIKE', "%{$q}%")
                    ->orWhere('subject', 'ILIKE', "%{$q}%");
            });
        }

        $letters = $query->orderBy('id', 'desc')->limit(20)->get();

        return response()->json($letters);
    }

    /**
     * Remove a cross-lane letter relation
     */
    public function destroy($id)
    {
        $relation = LetterRelation::findOrFail($id);
        $relation->delete();

        return back()->with('success', 'Relasi dokumen berhasil dihapus.');
    }
}
