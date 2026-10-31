<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();
        abort_unless(
            $user !== null
                && $user->status === 'active'
                && in_array($user->role, $roles, true),
            403
        );

        if ($user->role === 'merchant') {
            abort_unless(
                $user->merchantProfile()->where('status', 'active')->exists(),
                403,
                'Profil kantin belum aktif.'
            );
        }

        return $next($request);
    }
}
