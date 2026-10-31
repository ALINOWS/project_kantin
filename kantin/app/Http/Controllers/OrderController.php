<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Review;
use App\Models\UserNotification;
use App\Services\AuditLogService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        return view('orders.index', [
            'orders' => $request->user()->orders()->with('merchantProfile')->latest()->paginate(12),
        ]);
    }

    public function show(Request $request, Order $order): View
    {
        abort_unless((int) $order->user_id === (int) $request->user()->id, 404);
        $this->expireIfOverdue($order);

        return view('orders.show', [
            'order' => $order->load(['items', 'merchantProfile', 'payment', 'review']),
        ]);
    }

    public function claimPayment(Request $request, Order $order, AuditLogService $auditLog): RedirectResponse
    {
        abort_unless((int) $order->user_id === (int) $request->user()->id, 404);

        $claimed = DB::transaction(function () use ($request, $order, $auditLog): bool {
            $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->id);
            if ($lockedOrder->order_status !== 'pending_payment'
                || $lockedOrder->payment_status !== 'pending'
                || $lockedOrder->paid_claimed_at !== null
                || ($lockedOrder->expires_at !== null && $lockedOrder->expires_at->isPast())) {
                if ($lockedOrder->order_status === 'pending_payment'
                    && $lockedOrder->expires_at !== null
                    && $lockedOrder->expires_at->isPast()) {
                    $this->expireOrder($lockedOrder);
                }

                return false;
            }

            $lockedOrder->update([
                'order_status' => 'awaiting_merchant',
                'paid_claimed_at' => now(),
            ]);
            $lockedOrder->payment()->update([
                'claimed_at' => now(),
            ]);
            $merchantUserId = $lockedOrder->merchantProfile()->value('user_id');
            if ($merchantUserId !== null) {
                UserNotification::query()->create([
                    'user_id' => $merchantUserId,
                    'type' => 'payment_claimed',
                    'data' => [
                        'order_id' => $lockedOrder->id,
                        'order_number' => $lockedOrder->order_number,
                        'message' => 'Siswa mengklaim sudah membayar; cocokkan nominal QRIS.',
                    ],
                ]);
            }

            $auditLog->record($request, 'order.payment_claimed', 'order', $lockedOrder->id, [
                'amount' => $lockedOrder->total,
            ]);

            return true;
        }, attempts: 3);

        return to_route('orders.show', $order)->with(
            $claimed ? 'status' : 'error',
            $claimed
                ? 'Klaim pembayaran dikirim. Penjual akan mencocokkan nominal QRIS.'
                : 'Pesanan sudah kedaluwarsa atau klaim pembayaran telah dikirim sebelumnya.'
        );
    }

    public function review(Request $request, Order $order, AuditLogService $auditLog): RedirectResponse
    {
        abort_unless((int) $order->user_id === (int) $request->user()->id, 404);

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);

        $review = DB::transaction(function () use ($request, $order, $validated, $auditLog): Review {
            $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->id);
            if ($lockedOrder->order_status !== 'completed'
                || $lockedOrder->payment_status !== 'paid'
                || $lockedOrder->review()->exists()) {
                throw ValidationException::withMessages([
                    'review' => 'Ulasan hanya dapat ditulis satu kali untuk pesanan yang sudah selesai.',
                ]);
            }

            $review = $lockedOrder->review()->create([
                'user_id' => $request->user()->id,
                ...$validated,
            ]);
            $auditLog->record($request, 'order.reviewed', 'review', $review->id);

            return $review;
        }, attempts: 3);

        return to_route('orders.show', $order)->with('status', 'Ulasan Anda berhasil disimpan.');
    }

    private function expireIfOverdue(Order $order): void
    {
        if ($order->order_status === 'pending_payment'
            && $order->payment_status === 'pending'
            && $order->expires_at !== null
            && $order->expires_at->isPast()) {
            DB::transaction(fn () => $this->expireOrder(
                Order::query()->lockForUpdate()->findOrFail($order->id)
            ));
        }
    }

    private function expireOrder(Order $order): void
    {
        if ($order->order_status !== 'pending_payment' || $order->payment_status !== 'pending') {
            return;
        }

        $order->update([
            'order_status' => 'expired',
            'payment_status' => 'expired',
        ]);
        $order->payment()->update([
            'status' => 'expired',
            'expired_at' => now(),
        ]);
    }
}
