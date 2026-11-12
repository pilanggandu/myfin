<?php

namespace App\Http\Controllers\Categories;

use App\Http\Controllers\Controller;
use App\Http\Requests\Category\StoreCategoryRequest;
use App\Http\Requests\Category\UpdateCategoryRequest;
use App\Models\Category;
use App\Services\ActivityLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function __construct(
        private readonly ActivityLogService $activityLog
    ) {}

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

        $this->activityLog->log(
            module: 'category',
            action: 'created',
            description: "Menambahkan kategori {$category->type} '{$category->name}'",
            space: $space,
            actor: $request->user(),
            subject: $category,
            subjectLabel: $category->name,
            properties: ['type' => $category->type],
        );

        return redirect()->route('categories.index')
            ->with('success', "Kategori '{$category->name}' berhasil ditambahkan.");
    }

    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        Gate::authorize('update', $category);

        $before = ['name' => $category->name];
        $category->update($request->validated());
        $diff = $this->activityLog->buildDiff($before, ['name' => $category->name], ['name' => 'Nama Kategori']);

        $this->activityLog->log(
            module: 'category',
            action: 'updated',
            description: "Memperbarui kategori '{$category->name}'",
            space: $category->space,
            actor: $request->user(),
            subject: $category,
            subjectLabel: $category->name,
            properties: $diff,
        );

        return redirect()->route('categories.index')
            ->with('success', "Kategori '{$category->name}' berhasil diperbarui.");
    }

    public function archive(Category $category): RedirectResponse
    {
        Gate::authorize('update', $category);

        $category->archive();

        $this->activityLog->log(
            module: 'category',
            action: 'archived',
            description: "Mengarsipkan kategori '{$category->name}'",
            space: $category->space,
            subject: $category,
            subjectLabel: $category->name,
        );

        return redirect()->route('categories.index')
            ->with('success', "Kategori '{$category->name}' berhasil diarsipkan.");
    }

    public function unarchive(Category $category): RedirectResponse
    {
        Gate::authorize('update', $category);

        $category->unarchive();

        $this->activityLog->log(
            module: 'category',
            action: 'unarchived',
            description: "Mengaktifkan kembali kategori '{$category->name}'",
            space: $category->space,
            subject: $category,
            subjectLabel: $category->name,
        );

        return redirect()->route('categories.index')
            ->with('success', "Kategori '{$category->name}' berhasil diaktifkan kembali.");
    }
}
