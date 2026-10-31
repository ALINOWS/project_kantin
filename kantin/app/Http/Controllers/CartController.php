<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CartController extends Controller
{
    public function index(Request $request): View
    {
        $cart = $this->activeCart($request->user())
            ?->load(['items.product.category', 'items.product.merchantProfile']);

        return view('cart.index', [
            'cart' => $cart,
            'items' => $cart?->items ?? collect(),
            'subtotalCents' => $cart?->items->sum(
                fn (CartItem $item): int => (int) round((float) $item->product->price * 100) * $item->quantity
            ) ?? 0,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:99'],
        ]);

        DB::transaction(function () use ($request, $validated): void {
            $user = User::query()->lockForUpdate()->findOrFail($request->user()->id);
            $product = Product::query()->with(['category', 'merchantProfile'])->lockForUpdate()->findOrFail($validated['product_id']);
            $this->ensureProductIsOrderable($product);
            $cart = $user->carts()->where('status', 'active')->lockForUpdate()->first()
                ?? $user->carts()->create(['status' => 'active']);

            $merchantId = $cart->items()->join('products', 'cart_items.product_id', '=', 'products.id')
                ->value('products.merchant_id');
            if ($merchantId !== null && (int) $merchantId !== (int) $product->merchant_id) {
                throw ValidationException::withMessages([
                    'product_id' => 'Selesaikan atau kosongkan keranjang sebelum memesan dari kantin lain.',
                ]);
            }

            $item = $cart->items()->firstOrNew(['product_id' => $product->id]);
            $newQuantity = (int) ($item->quantity ?? 0) + $validated['quantity'];
            if ($newQuantity > 99) {
                throw ValidationException::withMessages([
                    'quantity' => 'Jumlah maksimal untuk satu menu adalah 99.',
                ]);
            }
            $item->quantity = $newQuantity;
            $item->save();
        });

        return to_route('cart.index')->with('status', 'Menu berhasil ditambahkan ke keranjang.');
    }

    public function update(Request $request, CartItem $cartItem): RedirectResponse
    {
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:99'],
        ]);

        abort_unless($cartItem->cart()->where('user_id', $request->user()->id)
            ->where('status', 'active')->exists(), 404);
        $cartItem->update(['quantity' => $validated['quantity']]);

        return to_route('cart.index')->with('status', 'Jumlah menu diperbarui.');
    }

    public function destroy(Request $request, CartItem $cartItem): RedirectResponse
    {
        abort_unless($cartItem->cart()->where('user_id', $request->user()->id)
            ->where('status', 'active')->exists(), 404);
        $cartItem->delete();

        return to_route('cart.index')->with('status', 'Menu dihapus dari keranjang.');
    }

    private function activeCart(User $user): ?Cart
    {
        return $user->carts()->where('status', 'active')->with('items.product')->latest()->first();
    }

    private function ensureProductIsOrderable(Product $product): void
    {
        abort_unless(
            $product->is_available
                && $product->category->is_active
                && $product->merchantProfile->status === 'active',
            422,
            'Menu sedang tidak tersedia.'
        );
    }
}
