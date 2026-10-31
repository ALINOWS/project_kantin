<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\MerchantProfile;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MenuController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'category_id' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id')->where('is_active', true),
            ],
            'merchant_id' => [
                'nullable',
                'integer',
                Rule::exists('merchant_profiles', 'id')->where('status', 'active'),
            ],
        ]);

        $products = Product::query()
            ->with(['category', 'merchantProfile'])
            ->whereHas('category', fn (Builder $query) => $query->where('is_active', true))
            ->whereHas('merchantProfile', fn (Builder $query) => $query->where('status', 'active'))
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($filters['category_id'] ?? null, function (Builder $query, int $categoryId): void {
                $query->where('category_id', $categoryId);
            })
            ->when($filters['merchant_id'] ?? null, function (Builder $query, int $merchantId): void {
                $query->where('merchant_id', $merchantId);
            })
            ->orderByDesc('is_available')
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        return view('menu.index', [
            'products' => $products,
            'categories' => Category::query()->where('is_active', true)->orderBy('name')->get(),
            'filters' => $filters,
            'merchant' => isset($filters['merchant_id'])
                ? MerchantProfile::query()->find($filters['merchant_id'])
                : null,
        ]);
    }

    public function show(Product $product): View
    {
        abort_unless(
            $product->category()->where('is_active', true)->exists()
                && $product->merchantProfile()->where('status', 'active')->exists(),
            404
        );

        return view('menu.show', [
            'product' => $product->load(['category', 'merchantProfile']),
        ]);
    }
}
