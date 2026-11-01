<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'role' => ['nullable', 'in:student,merchant,admin'],
        ]);

        return view('admin.users.index', [
            'users' => User::query()->with('merchantProfile')
                ->when(trim($filters['search'] ?? ''), function ($query, string $search): void {
                    $query->where(fn ($query) => $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%"));
                })
                ->when($filters['role'] ?? null, fn ($query, string $role) => $query->where('role', $role))
                ->latest()
                ->paginate(25)
                ->withQueryString(),
        ]);
    }

    public function update(Request $request, User $user, AuditLogService $auditLog): RedirectResponse
    {
        $validated = $request->validate([
            'role' => ['required', 'in:student,merchant,admin'],
            'status' => ['required', 'in:active,inactive,suspended'],
            'merchant_status' => ['nullable', 'in:pending,active,inactive,rejected'],
        ]);

        if ($user->is($request->user())
            && ($validated['role'] !== 'admin' || $validated['status'] !== 'active')) {
            return back()->with('error', 'Admin tidak dapat menonaktifkan atau menurunkan peran akunnya sendiri.');
        }

        DB::transaction(function () use ($request, $user, $validated, $auditLog): void {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);
            if ($lockedUser->role === 'admin' && $lockedUser->status === 'active'
                && ($validated['role'] !== 'admin' || $validated['status'] !== 'active')
                && User::query()->where('role', 'admin')->where('status', 'active')->count() <= 1) {
                abort(422, 'Admin aktif terakhir tidak dapat dinonaktifkan.');
            }

            $lockedUser->update([
                'role' => $validated['role'],
                'status' => $validated['status'],
            ]);

            if ($validated['role'] === 'merchant') {
                $profile = $lockedUser->merchantProfile()->firstOrCreate(
                    [],
                    ['stall_name' => $lockedUser->name, 'status' => 'pending'],
                );
                $profileStatus = $validated['merchant_status']
                    ?? ($lockedUser->status === 'active' ? $profile->status : 'inactive');
                $profile->update(['status' => $profileStatus]);
            } elseif ($lockedUser->merchantProfile !== null) {
                $lockedUser->merchantProfile()->update(['status' => 'inactive']);
            }

            $auditLog->record($request, 'user.access_updated', 'user', $lockedUser->id, [
                'role' => $validated['role'],
                'status' => $validated['status'],
                'merchant_status' => $validated['merchant_status'] ?? null,
            ]);
        }, attempts: 3);

        return to_route('admin.users.index')->with('status', 'Peran dan status pengguna diperbarui.');
    }
}
