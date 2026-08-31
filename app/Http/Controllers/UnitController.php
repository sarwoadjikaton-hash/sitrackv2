<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UnitController extends Controller
{
    /**
     * Display master units list
     */
    public function index(Request $request): Response
    {
        $search = trim((string) $request->input('search', ''));

        $query = Unit::query();
        if ($search !== '') {
            $query->where('unit_name', 'ILIKE', "%{$search}%")
                  ->orWhere('pic_name', 'ILIKE', "%{$search}%")
                  ->orWhere('phone', 'ILIKE', "%{$search}%");
        }

        $units = $query->orderBy('unit_name', 'asc')->paginate(20)->withQueryString();

        return Inertia::render('Master/Units/Index', [
            'units' => $units,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    /**
     * Store or update a unit
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'id' => ['nullable', 'exists:units,id'],
            'unit_name' => ['required', 'string', 'max:150'],
            'pic_name' => ['nullable', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:100'],
            'address' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        if (!empty($validated['id'])) {
            $unit = Unit::findOrFail($validated['id']);
            $unit->update($validated);
            $message = 'Data unit berhasil diperbarui.';
        } else {
            Unit::create($validated);
            $message = 'Unit baru berhasil ditambahkan.';
        }

        return redirect()->route('master.units.index')->with('success', $message);
    }

    /**
     * Delete a unit
     */
    public function destroy($id)
    {
        $unit = Unit::findOrFail($id);
        $unit->delete();

        return redirect()->route('master.units.index')->with('success', 'Unit berhasil dihapus.');
    }
}
