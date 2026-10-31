<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Services\AuditLogService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class SettingsController extends Controller
{
    public function edit(Request $request): View
    {
        return view('merchant.settings', [
            'merchant' => $request->user()->merchantProfile,
        ]);
    }

    public function update(Request $request, AuditLogService $auditLog): RedirectResponse
    {
        $merchant = $request->user()->merchantProfile;
        $validated = $request->validate([
            'stall_name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:5000'],
            'opening_hours' => ['nullable', 'string', 'max:500'],
            'qris_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'remove_qris' => ['sometimes', 'boolean'],
        ]);

        $qrisImage = $validated['qris_image'] ?? null;
        $removeQris = $validated['remove_qris'] ?? false;
        unset($validated['qris_image'], $validated['remove_qris']);
        $validated['opening_hours'] = filled($validated['opening_hours'] ?? null)
            ? ['text' => $validated['opening_hours']]
            : null;

        if ($qrisImage instanceof UploadedFile) {
            $validated['qris_image'] = $qrisImage->store('qris', 'public');
        } elseif ($removeQris) {
            $validated['qris_image'] = null;
        }

        $oldImage = $merchant->qris_image;
        $merchant->update($validated);

        if ($oldImage !== null && array_key_exists('qris_image', $validated) && $oldImage !== $merchant->qris_image) {
            Storage::disk('public')->delete($oldImage);
        }

        $auditLog->record($request, 'merchant.settings_updated', 'merchant_profile', $merchant->id);

        return to_route('merchant.settings.edit')->with('status', 'Informasi kantin dan QRIS diperbarui.');
    }
}
