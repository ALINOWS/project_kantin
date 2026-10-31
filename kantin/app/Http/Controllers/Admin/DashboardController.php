<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Order;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.dashboard', [
            'userCount' => User::query()->count(),
            'pendingMerchants' => User::query()->where('role', 'merchant')
                ->whereHas('merchantProfile', fn ($query) => $query->where('status', 'pending'))->count(),
            'todayOrders' => Order::query()->whereDate('created_at', today())->count(),
            'pendingPayments' => Order::query()->where('order_status', 'awaiting_merchant')->count(),
            'recentActivity' => AuditLog::query()->with('user')->latest()->limit(8)->get(),
        ]);
    }

    public function reports(Request $request): View
    {
        $start = $request->date('start') ?? today()->startOfMonth();
        $end = $request->date('end') ?? today();
        abort_if($start->greaterThan($end), 422, 'Tanggal mulai harus sebelum tanggal akhir.');

        return view('admin.reports', [
            'start' => $start,
            'end' => $end,
            'orders' => Order::query()
                ->with('merchantProfile')
                ->whereBetween('created_at', [$start->startOfDay(), $end->endOfDay()])
                ->latest()
                ->paginate(25)
                ->withQueryString(),
            'revenue' => Order::query()
                ->where('payment_status', 'paid')
                ->whereBetween('paid_at', [$start->startOfDay(), $end->endOfDay()])
                ->sum('total'),
        ]);
    }

    public function audit(): View
    {
        return view('admin.audit', [
            'logs' => AuditLog::query()->with('user')->latest()->paginate(30),
        ]);
    }
}
