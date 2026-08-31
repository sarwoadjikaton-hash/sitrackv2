<?php

namespace App\Http\Controllers;

use App\Models\LetterCategory;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LetterCategoryController extends Controller
{
    /**
     * Display master categories list.
     */
    public function index(Request $request): Response
    {
        $categories = LetterCategory::orderBy('id', 'asc')->get();

        return Inertia::render('Master/Categories/Index', [
            'categories' => $categories,
        ]);
    }

    /**
     * Store a new category.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        LetterCategory::create($validated);

        return redirect()
            ->route('master.categories.index')
            ->with('success', 'Kategori baru berhasil ditambahkan.');
    }

    /**
     * Update an existing category.
     */
    public function update(Request $request, LetterCategory $category)
    {
        $validated = $request->validate([
            'category_name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        $category->update($validated);

        return redirect()
            ->route('master.categories.index')
            ->with('success', 'Kategori berhasil diperbarui.');
    }

    /**
     * Delete a category.
     */
    public function destroy($id)
    {
        $category = LetterCategory::findOrFail($id);

        $category->delete();

        return redirect()
            ->route('master.categories.index')
            ->with('success', 'Kategori berhasil dihapus.');
    }
}