<?php

namespace App\Http\Controllers\Categories;

use App\Http\Controllers\Controller;
use App\Http\Requests\Category\StoreCategoryRequest;
use App\Http\Requests\Category\UpdateCategoryRequest;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(Request $request): View
    {
        $space = app('currentSpace');

        $incomeCategories = $space->categories()
            ->income()
            ->orderBy('archived_at')
            ->orderBy('name')
            ->get();

        $expenseCategories = $space->categories()
            ->expense()
            ->orderBy('archived_at')
            ->orderBy('name')
            ->get();

        return view('categories.index', compact('incomeCategories', 'expenseCategories'));
    }

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        $space = app('currentSpace');
        $validated = $request->validated();

        $category = $space->categories()->create($validated);

        return redirect()->route('categories.index')
            ->with('success', "Kategori '{$category->name}' berhasil ditambahkan.");
    }

    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        Gate::authorize('update', $category);

        $category->update($request->validated());

        return redirect()->route('categories.index')
            ->with('success', "Kategori '{$category->name}' berhasil diperbarui.");
    }

    public function archive(Category $category): RedirectResponse
    {
        Gate::authorize('update', $category);

        $category->archive();

        return redirect()->route('categories.index')
            ->with('success', "Kategori '{$category->name}' berhasil diarsipkan.");
    }

    public function unarchive(Category $category): RedirectResponse
    {
        Gate::authorize('update', $category);

        $category->unarchive();

        return redirect()->route('categories.index')
            ->with('success', "Kategori '{$category->name}' berhasil diaktifkan kembali.");
    }
}
