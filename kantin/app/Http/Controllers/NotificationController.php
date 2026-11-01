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
        if ($request->user()->role === 'student') {
            return $orderId !== null
                ? to_route('orders.show', $orderId)
                : to_route('orders.index');
        }

        if ($request->user()->role === 'merchant') {
            return to_route('merchant.orders.index');
        }

        return $request->user()->role === 'admin'
            ? to_route('admin.dashboard')
            : to_route('menu.index');
    }
}
