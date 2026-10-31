<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\Order;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('kantin:expire-unpaid-orders')]
#[Description('Expire QRIS orders that were not paid within the payment window')]
class ExpireUnpaidOrders extends Command
{
    public function handle(): int
    {
        $expiredCount = 0;

        Order::query()
            ->where('order_status', 'pending_payment')
            ->where('payment_status', 'pending')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->chunkById(100, function ($orders) use (&$expiredCount): void {
                foreach ($orders as $order) {
                    DB::transaction(function () use ($order, &$expiredCount): void {
                        $lockedOrder = Order::query()->lockForUpdate()->find($order->id);
                        if ($lockedOrder === null
                            || $lockedOrder->order_status !== 'pending_payment'
                            || $lockedOrder->payment_status !== 'pending') {
                            return;
                        }

                        $lockedOrder->update([
                            'order_status' => 'expired',
                            'payment_status' => 'expired',
                        ]);
                        $lockedOrder->payment()->update([
                            'status' => 'expired',
                            'expired_at' => now(),
                        ]);
                        AuditLog::query()->create([
                            'action' => 'order.payment_expired',
                            'entity_type' => 'order',
                            'entity_id' => $lockedOrder->id,
                            'details' => ['order_number' => $lockedOrder->order_number],
                        ]);
                        $expiredCount++;
                    }, attempts: 3);
                }
            });

        $this->components->info("{$expiredCount} pesanan kedaluwarsa.");

        return self::SUCCESS;
    }
}
