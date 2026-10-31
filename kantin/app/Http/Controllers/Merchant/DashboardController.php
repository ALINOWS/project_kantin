<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $merchant = $request->user()->merchantProfile;
        $orders = Order::query()->where('merchant_id', $merchant->id);

        return view('merchant.dashboard', [
            'newOrders' => (clone $orders)->where('order_status', 'awaiting_merchant')->count(),
            'processingOrders' => (clone $orders)->where('order_status', 'processing')->count(),
            'readyOrders' => (clone $orders)->where('order_status', 'ready_for_pickup')->count(),
            'completedToday' => (clone $orders)->where('order_status', 'completed')->whereDate('completed_at', today())->count(),
            'recentOrders' => (clone $orders)->with('user')->latest()->limit(8)->get(),
        ]);
    }

    public function reports(Request $request): View
    {
        $merchantId = $request->user()->merchantProfile->id;
        $start = $request->date('start') ?? today()->startOfMonth();
        $end = $request->date('end') ?? today();
        abort_if($start->greaterThan($end), 422, 'Tanggal mulai harus sebelum tanggal akhir.');
        $orders = Order::query()->where('merchant_id', $merchantId)
            ->where('payment_status', 'paid')
            ->whereBetween('paid_at', [$start->startOfDay(), $end->endOfDay()]);

        return view('merchant.reports', [
            'start' => $start,
            'end' => $end,
            'orders' => (clone $orders)->with('user')->latest()->paginate(25)->withQueryString(),
            'revenue' => (clone $orders)->sum('total'),
            'salesCount' => (clone $orders)->count(),
            'topProducts' => Product::query()->where('merchant_id', $merchantId)
                ->withSum(['orderItems as quantity_sold' => function ($query) use ($start, $end): void {
                    $query->whereHas('order', fn ($query) => $query->where('payment_status', 'paid')
                        ->whereBetween('paid_at', [$start->startOfDay(), $end->endOfDay()]));
                }], 'quantity')
                ->orderByDesc('quantity_sold')
                ->limit(5)
                ->get(),
        ]);
    }
}
