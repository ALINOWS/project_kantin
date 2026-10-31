<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email', 'max:190'],
            'password' => ['required', 'string'],
            'remember' => ['sometimes', 'boolean'],
        ]);

        $remember = $request->boolean('remember');
        unset($credentials['remember']);
        $credentials['status'] = 'active';

        if (! Auth::attempt($credentials, $remember)) {
            return back()->withErrors([
                'email' => 'Email atau kata sandi tidak valid.',
            ])->onlyInput('email');
        }

        $request->session()->regenerate();
        $user = $request->user();

        if ($user->role === 'merchant' && $user->merchantProfile?->status !== 'active') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withErrors([
                'email' => 'Profil kantin belum aktif.',
            ])->onlyInput('email');
        }

        return match ($user->role) {
            'admin' => to_route('admin.dashboard'),
            'merchant' => to_route('merchant.dashboard'),
            default => to_route('orders.index'),
        };
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return to_route('menu.index');
    }
}
