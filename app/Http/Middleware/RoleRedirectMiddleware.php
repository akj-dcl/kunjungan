<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleRedirectMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Kalau bukan user login, biarkan lewat (akan dicegat middleware 'auth' nanti)
        if (!$user) return $next($request);

        // 1. Jika Pengunjung mencoba masuk ke root '/' atau '/dashboard'
        if ($user->hasRole('Pengunjung')) {
            return redirect()->route('kunjungans.index');
        }

        // 2. Jika Operator Lapas mencoba masuk
        if ($user->hasRole('Operator Lapas')) {
            return redirect()->route('scan-qr.index');
        }

        // User lain (Admin/Super Admin) boleh lewat
        return $next($request);
    }
}