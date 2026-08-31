<?php

namespace App\Http\Controllers;

use App\Models\LetterNumberType;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LetterNumberTypeController extends Controller
{
    /**
     * Display master letter number types list
     */
    public function index(Request $request): Response
    {
        $types = LetterNumberType::withCount('letterNumbers')
            ->orderBy('display_order', 'asc')
            ->orderBy('type_name', 'asc')
            ->get();

        return Inertia::render('Master/NumberTypes/Index', [
            'types' => $types,
        ]);
    }

    /**
     * Store or update a letter number type
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'id' => ['nullable', 'exists:letter_number_types,id'],
            'type_code' => ['required', 'string', 'max:50'],
            'type_name' => ['required', 'string', 'max:150'],
            'workbook_name' => ['required', 'string', 'max:120'],
            'uses_security_access' => ['boolean'],
            'extra_field' => ['required', 'in:scan_result,nd_pengantar'],
            'number_pattern' => ['required', 'string', 'max:255'],
            'sequence_padding' => ['required', 'integer', 'min:1', 'max:8'],
            'default_signer_code' => ['nullable', 'string', 'max:20'],
            'display_order' => ['nullable', 'integer'],
            'is_active' => ['boolean'],
        ]);

        $validated['type_code'] = strtoupper(preg_replace('/[^A-Za-z0-9_]/', '_', $validated['type_code']));
        $validated['default_signer_code'] = $validated['default_signer_code'] ?: '1';

        if (!empty($validated['id'])) {
            $type = LetterNumberType::findOrFail($validated['id']);
            $type->update($validated);
            $message = 'Jenis naskah dan formula penomoran berhasil diperbarui.';
        } else {
            LetterNumberType::create($validated);
            $message = 'Jenis naskah baru berhasil ditambahkan.';
        }

        return redirect()->route('master.number-types.index')->with('success', $message);
    }

    /**
     * Delete a letter number type
     */
    public function destroy($id)
    {
        $type = LetterNumberType::findOrFail($id);
        
        if ($type->letterNumbers()->count() > 0) {
            return back()->with('error', 'Jenis naskah tidak dapat dihapus karena sudah memiliki slot nomor surat.');
        }

        $type->delete();

        return redirect()->route('master.number-types.index')->with('success', 'Jenis naskah berhasil dihapus.');
    }
}
