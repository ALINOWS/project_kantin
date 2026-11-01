<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Services\AuditLogService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizeAdmin($request);

        return view('manage.categories.index', [
            'categories' => Category::query()->withCount('products')->orderBy('name')->paginate(15),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorizeAdmin($request);

        return view('manage.categories.create', ['category' => new Category]);
    }

    public function store(Request $request, AuditLogService $auditLog): RedirectResponse
    {
        $this->authorizeAdmin($request);
        $category = Category::query()->create($this->validatedData($request));
        $auditLog->record($request, 'category.created', 'category', $category->id);

        return to_route('manage.categories.index')->with('status', 'Kategori berhasil ditambahkan.');
    }

    public function edit(Request $request, Category $category): View
    {
        $this->authorizeAdmin($request);

        return view('manage.categories.edit', compact('category'));
    }

    public function update(Request $request, Category $category, AuditLogService $auditLog): RedirectResponse
    {
        $this->authorizeAdmin($request);
        $category->update($this->validatedData($request, $category));
        $auditLog->record($request, 'category.updated', 'category', $category->id);

        return to_route('manage.categories.index')->with('status', 'Kategori berhasil diperbarui.');
    }

    public function destroy(Request $request, Category $category, AuditLogService $auditLog): RedirectResponse
    {
        $this->authorizeAdmin($request);

        if ($category->products()->exists()) {
            return back()->with('error', 'Kategori masih digunakan oleh menu dan tidak dapat dihapus.');
        }

        $category->delete();
        $auditLog->record($request, 'category.deleted', 'category', $category->id);

        return to_route('manage.categories.index')->with('status', 'Kategori berhasil dihapus.');
    }

    /**
     * @return array{name: string, slug: string, description: ?string, is_active: bool}
     */
    private function validatedData(Request $request, ?Category $category = null): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['required', 'string', 'max:120', Rule::unique('categories', 'slug')->ignore($category)],
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        return $validated;
    }

    private function authorizeAdmin(Request $request): void
    {
        abort_unless(
            $request->user()?->role === 'admin' && $request->user()?->status === 'active',
            403
        );
    }
}
