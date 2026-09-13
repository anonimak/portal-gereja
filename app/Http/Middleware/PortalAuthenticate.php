<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class PortalAuthenticate
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Cek autentikasi via Bearer token jika belum login via session
        if (! Auth::check() && $token = $request->bearerToken()) {
            $user = User::where('api_token', $token)->first();
            if ($user) {
                Auth::setUser($user);
            }
        }

        // 2. Jika tetap unauthenticated
        if (! Auth::check()) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => 'Unauthenticated.',
                ], 401);
            }

            return redirect()->guest(route('portal.login'));
        }

        return $next($request);
    }
}
