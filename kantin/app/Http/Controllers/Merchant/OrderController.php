<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\UserNotification;
use App\Services\AuditLogService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $merchantId = $request->user()->merchantProfile->id;
        $validated = $request->validate([
            'status' => ['nullable', 'in:awaiting_merchant,processing,ready_for_pickup,completed,cancelled,expired'],
        ]);

        return view('merchant.orders.index', [
            'orders' => Order::query()->where('merchant_id', $merchantId)
                ->with('user')->withCount('items')
                ->when($validated['status'] ?? null, fn ($query, string $status) => $query->where('order_status', $status))
                ->latest()->paginate(20)->withQueryString(),
        ]);
    }

    public function show(Request $request, Order $order): View
    {
        $this->authorizeOrder($request, $order);

        return view('merchant.orders.show', [
            'order' => $order->load(['user', 'items', 'payment']),
        ]);
    }

    public function verifyPayment(
        Request $request,
        Order $order,
        AuditLogService $auditLog
    ): RedirectResponse {
        $this->authorizeOrder($request, $order);
        $validated = $request->validate([
            'confirmed_amount' => ['required', 'numeric', 'min:0'],
        ]);

        DB::transaction(function () use ($request, $order, $validated, $auditLog): void {
            $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->id);
            $this->ensureAwaitingVerification($lockedOrder);

            if ((int) round((float) $validated['confirmed_amount'] * 100)
                !== (int) round((float) $lockedOrder->total * 100)) {
                throw ValidationException::withMessages([
                    'confirmed_amount' => 'Nominal tidak cocok. Pesanan tetap menunggu konfirmasi; periksa mutasi QRIS.',
                ]);
            }

            $pickupCode = $this->newPickupCode();
            $lockedOrder->update([
                'payment_status' => 'paid',
                'order_status' => 'processing',
                'paid_at' => now(),
                'pickup_code' => $pickupCode,
            ]);
            $lockedOrder->payment()->update([
                'status' => 'paid',
                'paid_at' => now(),
            ]);
            $this->notifyStudent($lockedOrder, 'payment_verified', 'Pembayaran diverifikasi dan pesanan sedang diproses.');
            $auditLog->record($request, 'order.payment_verified', 'order', $lockedOrder->id);
        }, attempts: 3);

        return to_route('merchant.orders.show', $order)->with('status', 'Pembayaran cocok. Pesanan masuk antrean persiapan.');
    }

    public function rejectPayment(Request $request, Order $order, AuditLogService $auditLog): RedirectResponse
    {
        $this->authorizeOrder($request, $order);
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($request, $order, $validated, $auditLog): void {
            $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->id);
            $this->ensureAwaitingVerification($lockedOrder);
            $lockedOrder->update([
                'order_status' => 'cancelled',
                'payment_status' => 'failed',
            ]);
            $lockedOrder->payment()->update(['status' => 'failed']);
            $this->notifyStudent($lockedOrder, 'payment_rejected', 'Pembayaran tidak dapat diverifikasi: '.$validated['reason']);
            $auditLog->record($request, 'order.payment_rejected', 'order', $lockedOrder->id, [
                'reason' => $validated['reason'],
            ]);
        }, attempts: 3);

        return to_route('merchant.orders.index')->with('status', 'Pesanan dibatalkan dan siswa diberi tahu.');
    }

    public function markReady(Request $request, Order $order, AuditLogService $auditLog): RedirectResponse
    {
        $this->authorizeOrder($request, $order);

        DB::transaction(function () use ($request, $order, $auditLog): void {
            $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->id);
            abort_unless($lockedOrder->order_status === 'processing' && $lockedOrder->payment_status === 'paid', 409);
            $lockedOrder->update(['order_status' => 'ready_for_pickup']);
            $this->notifyStudent($lockedOrder, 'order_ready', 'Pesanan siap diambil. Tunjukkan kode pengambilan kepada penjual.');
            $auditLog->record($request, 'order.ready_for_pickup', 'order', $lockedOrder->id);
        }, attempts: 3);

        return to_route('merchant.orders.show', $order)->with('status', 'Pesanan siap diambil.');
    }

    public function completePickup(Request $request, Order $order, AuditLogService $auditLog): RedirectResponse
    {
        $this->authorizeOrder($request, $order);
        $validated = $request->validate([
            'pickup_code' => ['required', 'string', 'max:32'],
        ]);

        DB::transaction(function () use ($request, $order, $validated, $auditLog): void {
            $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->id);
            abort_unless($lockedOrder->order_status === 'ready_for_pickup', 409);
            if ($lockedOrder->pickup_code === null
                || ! hash_equals(strtoupper($lockedOrder->pickup_code), strtoupper(trim($validated['pickup_code'])))) {
                throw ValidationException::withMessages([
                    'pickup_code' => 'Kode pengambilan tidak cocok atau sudah digunakan.',
                ]);
            }

            $lockedOrder->update([
                'order_status' => 'completed',
                'completed_at' => now(),
                'pickup_code' => null,
            ]);
            $this->notifyStudent($lockedOrder, 'order_completed', 'Pesanan telah diambil. Terima kasih!');
            $auditLog->record($request, 'order.completed', 'order', $lockedOrder->id);
        }, attempts: 3);

        return to_route('merchant.orders.show', $order)->with('status', 'Kode tervalidasi. Pesanan selesai.');
    }

    private function authorizeOrder(Request $request, Order $order): void
    {
        abort_unless(
            (int) $order->merchant_id === (int) $request->user()->merchantProfile->id,
            404
        );
    }

    private function ensureAwaitingVerification(Order $order): void
    {
        abort_unless(
            $order->order_status === 'awaiting_merchant'
                && $order->payment_status === 'pending'
                && $order->paid_claimed_at !== null,
            409,
            'Pesanan ini tidak sedang menunggu verifikasi pembayaran.'
        );
    }

    private function newPickupCode(): string
    {
        do {
            $code = Str::upper(Str::random(8));
        } while (Order::query()->where('pickup_code', $code)->exists());

        return $code;
    }

    private function notifyStudent(Order $order, string $type, string $message): void
    {
        UserNotification::query()->create([
            'user_id' => $order->user_id,
            'type' => $type,
            'data' => [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'message' => $message,
            ],
        ]);
    }
}
