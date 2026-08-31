<?php

namespace App\Http\Controllers;

use App\Models\LetterRelation;
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
     * Remove a cross-lane letter relation
     */
    public function destroy($id)
    {
        $relation = LetterRelation::findOrFail($id);
        $relation->delete();

        return back()->with('success', 'Relasi dokumen berhasil dihapus.');
    }
}
