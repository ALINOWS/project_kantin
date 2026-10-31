<?php

namespace App\Http\Controllers;

use App\Models\UserNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function read(Request $request, UserNotification $notification): RedirectResponse
    {
        abort_unless((int) $notification->user_id === (int) $request->user()->id, 404);
        $notification->update(['read_at' => now()]);

        $orderId = $notification->data['order_id'] ?? null;
        if ($orderId !== null && $request->user()->role === 'student') {
            return to_route('orders.show', $orderId);
        }

        if ($request->user()->role === 'merchant') {
            return to_route('merchant.orders.index');
        }

        return to_route('admin.dashboard');
    }
}
