<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class RegistrationController extends Controller
{
    public function createStudent(): View
    {
        return view('auth.register');
    }

    public function createMerchant(): View
    {
        return view('auth.register-merchant');
    }

    public function storeStudent(Request $request, AuditLogService $auditLog): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $user = DB::transaction(fn (): User => User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'student',
            'status' => 'active',
        ]));
        $request->session()->regenerate();
        Auth::login($user);
        $auditLog->record($request, 'student.registered', 'user', $user->id, user: $user);

        return to_route('menu.index')->with('status', 'Akun siswa berhasil dibuat. Silakan pilih menu favorit Anda.');
    }

    public function storeMerchant(Request $request, AuditLogService $auditLog): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'stall_name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:5000'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $user = DB::transaction(function () use ($validated): User {
            $user = User::query()->create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => 'merchant',
                'status' => 'active',
            ]);
            $user->merchantProfile()->create([
                'stall_name' => $validated['stall_name'],
                'description' => $validated['description'] ?? null,
                'status' => 'pending',
            ]);

            return $user;
        });

        $auditLog->record(
            $request,
            'merchant.registration_requested',
            'merchant_profile',
            $user->merchantProfile->id,
            user: $user,
        );

        return to_route('login')->with('status', 'Pendaftaran kantin diterima. Admin akan mengaktifkan akun sebelum Anda dapat masuk.');
    }
}
