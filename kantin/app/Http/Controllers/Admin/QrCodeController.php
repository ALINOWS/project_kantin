<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MerchantProfile;
use App\Models\QrCode;
use App\Services\AuditLogService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class QrCodeController extends Controller
{
    public function scan(string $token)
    {
        $qrCode = QrCode::query()->where('token', $token)
            ->where('type', 'merchant_menu')
            ->where('is_active', true)
            ->with('merchantProfile')
            ->firstOrFail();

        abort_unless($qrCode->merchantProfile?->status === 'active', 404);

        return to_route('menu.index', ['merchant_id' => $qrCode->merchant_id]);
    }

    public function index(): View
    {
        return view('admin.qrcodes.index', [
            'merchants' => MerchantProfile::query()->where('status', 'active')->orderBy('stall_name')->get(),
            'qrCodes' => QrCode::query()->with('merchantProfile')->latest()->paginate(25),
        ]);
    }

    public function store(Request $request, AuditLogService $auditLog): RedirectResponse
    {
        $validated = $request->validate([
            'merchant_id' => ['required', 'integer', 'exists:merchant_profiles,id'],
        ]);

        $merchant = MerchantProfile::query()->whereKey($validated['merchant_id'])
            ->where('status', 'active')->firstOrFail();
        $token = Str::random(48);
        $qrCode = QrCode::query()->create([
            'token' => $token,
            'type' => 'merchant_menu',
            'merchant_id' => $merchant->id,
            'target_url' => route('qrcode.scan', $token),
            'is_active' => true,
        ]);
        $auditLog->record($request, 'qrcode.created', 'qr_code', $qrCode->id);

        return to_route('admin.qrcodes.index')->with('status', 'QR menu kantin berhasil dibuat.');
    }

    public function toggle(Request $request, QrCode $qrCode, AuditLogService $auditLog): RedirectResponse
    {
        $qrCode->update(['is_active' => ! $qrCode->is_active]);
        $auditLog->record($request, 'qrcode.toggled', 'qr_code', $qrCode->id, [
            'is_active' => $qrCode->is_active,
        ]);

        return to_route('admin.qrcodes.index')->with('status', 'Status QR code diperbarui.');
    }
}
