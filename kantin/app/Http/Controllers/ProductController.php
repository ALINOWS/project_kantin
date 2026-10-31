<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\MerchantProfile;
use App\Models\Product;
use App\Services\AuditLogService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $merchantProfile = $this->authorizeManager($request);
        $products = Product::query()
            ->with(['category', 'merchantProfile'])
            ->when($merchantProfile, function (Builder $query) use ($merchantProfile): void {
                $query->where('merchant_id', $merchantProfile->id);
            })
            ->orderByDesc('updated_at')
            ->paginate(15);

        return view('manage.products.index', [
            'products' => $products,
            'routePrefix' => $request->user()->role === 'admin' ? 'manage.products' : 'merchant.products',
        ]);
    }

    public function create(Request $request): View
    {
        $merchantProfile = $this->authorizeManager($request);

        return view('manage.products.create', [
            'product' => new Product,
            'categories' => Category::query()->orderBy('name')->get(),
            'routePrefix' => $request->user()->role === 'admin' ? 'manage.products' : 'merchant.products',
            'merchantProfiles' => $merchantProfile === null
                ? MerchantProfile::query()->where('status', 'active')->orderBy('stall_name')->get()
                : collect(),
        ]);
    }

    public function store(Request $request, AuditLogService $auditLog): RedirectResponse
    {
        $merchantProfile = $this->authorizeManager($request);
        $validated = $this->validatedData($request, $merchantProfile);
        $image = $validated['image'] ?? null;
        unset($validated['image'], $validated['remove_image']);
        $product = Product::query()->create([
            ...$validated,
            'image' => $image instanceof UploadedFile ? $image->store('products', 'public') : null,
        ]);
        $auditLog->record($request, 'product.created', 'product', $product->id);

        return to_route($this->productRoute($request, 'edit'), $product)->with('status', 'Menu berhasil ditambahkan.');
    }

    public function edit(Request $request, Product $product): View
    {
        $merchantProfile = $this->authorizeManager($request);
        $this->authorizeProduct($product, $merchantProfile);

        return view('manage.products.edit', [
            'product' => $product,
            'categories' => Category::query()->orderBy('name')->get(),
            'routePrefix' => $request->user()->role === 'admin' ? 'manage.products' : 'merchant.products',
            'merchantProfiles' => $merchantProfile === null
                ? MerchantProfile::query()->where('status', 'active')->orderBy('stall_name')->get()
                : collect(),
        ]);
    }

    public function update(Request $request, Product $product, AuditLogService $auditLog): RedirectResponse
    {
        $merchantProfile = $this->authorizeManager($request);
        $this->authorizeProduct($product, $merchantProfile);
        $validated = $this->validatedData($request, $merchantProfile, $product);
        $uploadedImage = $validated['image'] ?? null;
        $removeImage = $validated['remove_image'] ?? false;
        unset($validated['image'], $validated['remove_image']);

        if ($uploadedImage instanceof UploadedFile) {
            $validated['image'] = $uploadedImage->store('products', 'public');
        } elseif ($removeImage) {
            $validated['image'] = null;
        }

        $oldImage = $product->image;
        $product->update($validated);

        if ($oldImage !== null && array_key_exists('image', $validated) && $oldImage !== $product->image) {
            Storage::disk('public')->delete($oldImage);
        }

        $auditLog->record($request, 'product.updated', 'product', $product->id);

        return to_route($this->productRoute($request, 'edit'), $product)->with('status', 'Menu berhasil diperbarui.');
    }

    public function destroy(Request $request, Product $product, AuditLogService $auditLog): RedirectResponse
    {
        $merchantProfile = $this->authorizeManager($request);
        $this->authorizeProduct($product, $merchantProfile);

        foreach (['cart_items', 'order_items'] as $table) {
            if (Schema::hasTable($table) && DB::table($table)->where('product_id', $product->id)->exists()) {
                return back()->with('error', 'Menu sudah memiliki riwayat pesanan. Ubah status ketersediaannya saja.');
            }
        }

        $oldImage = $product->image;
        $product->delete();
        if ($oldImage !== null) {
            Storage::disk('public')->delete($oldImage);
        }
        $auditLog->record($request, 'product.deleted', 'product', $product->id);

        return to_route($this->productRoute($request, 'index'))->with('status', 'Menu berhasil dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedData(
        Request $request,
        ?MerchantProfile $merchantProfile,
        ?Product $product = null
    ): array {
        $user = $request->user();
        $rules = [
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')],
            'name' => ['required', 'string', 'max:150'],
            'slug' => ['required', 'string', 'max:180', Rule::unique('products', 'slug')->ignore($product)],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'is_available' => ['sometimes', 'boolean'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_image' => ['sometimes', 'boolean'],
        ];

        if ($user->role === 'admin') {
            $rules['merchant_id'] = [
                'required',
                'integer',
                Rule::exists('merchant_profiles', 'id')->where('status', 'active'),
            ];
        } else {
            $rules['merchant_id'] = ['prohibited'];
        }

        $validated = $request->validate($rules);
        $validated['is_available'] = $request->boolean('is_available');
        $validated['merchant_id'] = $merchantProfile?->id ?? $validated['merchant_id'];

        return $validated;
    }

    private function productRoute(Request $request, string $action): string
    {
        $prefix = $request->user()->role === 'admin' ? 'manage' : 'merchant';

        return "{$prefix}.products.{$action}";
    }

    private function authorizeManager(Request $request): ?MerchantProfile
    {
        $user = $request->user();

        abort_unless(
            $user !== null
                && $user->status === 'active'
                && in_array($user->role, ['admin', 'merchant'], true),
            403
        );

        if ($user->role === 'admin') {
            return null;
        }

        $merchantProfile = $user->merchantProfile()
            ->where('status', 'active')
            ->first();

        abort_unless($merchantProfile !== null, 403, 'Profil kantin belum aktif.');

        return $merchantProfile;
    }

    private function authorizeProduct(Product $product, ?MerchantProfile $merchantProfile): void
    {
        if ($merchantProfile !== null && (int) $product->merchant_id !== (int) $merchantProfile->id) {
            abort(404);
        }
    }
}
