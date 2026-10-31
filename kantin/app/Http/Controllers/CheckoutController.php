<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CheckoutController extends Controller
{
    public function store(Request $request, AuditLogService $auditLog): RedirectResponse
    {
        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $order = DB::transaction(function () use ($request, $validated, $auditLog): Order {
            $user = User::query()->lockForUpdate()->findOrFail($request->user()->id);
            $cart = $user->carts()->where('status', 'active')->lockForUpdate()->first();
            if ($cart === null) {
                throw ValidationException::withMessages(['cart' => 'Keranjang Anda masih kosong.']);
            }

            $items = $cart->items()->with(['product.category', 'product.merchantProfile'])
                ->lockForUpdate()->get();
            if ($items->isEmpty()) {
                throw ValidationException::withMessages(['cart' => 'Keranjang Anda masih kosong.']);
            }

            $merchantIds = $items->pluck('product.merchant_id')->unique();
            if ($merchantIds->count() !== 1) {
                throw ValidationException::withMessages(['cart' => 'Satu pesanan hanya dapat dibuat dari satu kantin.']);
            }

            foreach ($items as $item) {
                if (! $item->product->is_available
                    || ! $item->product->category->is_active
                    || $item->product->merchantProfile->status !== 'active') {
                    throw ValidationException::withMessages([
                        'cart' => "{$item->product->name} sedang tidak tersedia. Perbarui keranjang Anda.",
                    ]);
                }
            }

            $merchant = $items->first()->product->merchantProfile;
            if ($merchant->qris_image === null) {
                throw ValidationException::withMessages([
                    'cart' => 'Kantin belum mengunggah QRIS sehingga pesanan belum dapat dibayar.',
                ]);
            }

            $subtotalCents = $items->sum(
                fn ($item): int => (int) round((float) $item->product->price * 100) * $item->quantity
            );
            $uniqueCode = $this->availableUniqueCode($merchant->id);
            $now = now();
            $order = Order::query()->create([
                'order_number' => $this->orderNumber(),
                'user_id' => $user->id,
                'merchant_id' => $merchant->id,
                'subtotal' => $subtotalCents / 100,
                'unique_code' => $uniqueCode,
                'total' => ($subtotalCents + $uniqueCode * 100) / 100,
                'order_status' => 'pending_payment',
                'payment_status' => 'pending',
                'expires_at' => $now->copy()->addMinutes(15),
                'notes' => $validated['notes'] ?? null,
            ]);

            foreach ($items as $item) {
                $unitCents = (int) round((float) $item->product->price * 100);
                $order->items()->create([
                    'product_id' => $item->product_id,
                    'product_name' => $item->product->name,
                    'unit_price' => $unitCents / 100,
                    'quantity' => $item->quantity,
                    'subtotal' => ($unitCents * $item->quantity) / 100,
                ]);
            }

            $order->payment()->create([
                'provider' => 'static_qris',
                'payment_method' => 'QRIS',
                'amount' => $order->total,
                'status' => 'pending',
                'expires_at' => $order->expires_at,
            ]);

            $cart->items()->delete();
            $cart->update(['status' => 'converted']);
            $auditLog->record($request, 'order.created', 'order', $order->id, [
                'order_number' => $order->order_number,
                'merchant_id' => $merchant->id,
                'total' => $order->total,
            ]);

            return $order;
        }, attempts: 3);

        return to_route('orders.show', $order)->with('status', 'Pesanan dibuat. Bayar QRIS sebelum batas waktu.');
    }

    private function availableUniqueCode(int $merchantId): int
    {
        for ($attempt = 0; $attempt < 50; $attempt++) {
            $code = random_int(1, 999);

            if (! Order::query()
                ->where('merchant_id', $merchantId)
                ->where('unique_code', $code)
                ->where('payment_status', 'pending')
                ->exists()) {
                return $code;
            }
        }

        throw ValidationException::withMessages([
            'cart' => 'Kantin sedang memiliki banyak pembayaran menunggu. Coba lagi sebentar.',
        ]);
    }

    private function orderNumber(): string
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $number = 'KM-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
            if (! Order::query()->where('order_number', $number)->exists()) {
                return $number;
            }
        }

        throw ValidationException::withMessages(['cart' => 'Nomor pesanan tidak dapat dibuat. Silakan coba kembali.']);
    }
}
