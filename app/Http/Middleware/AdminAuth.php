<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminAuth
{
    public function handle(Request $request, Closure $next)
    {
        // Memastikan pengguna menggunakan guard 'admin' dan autentikasi berhasil
        if (Auth::guard('admin')->check()) {
            return $next($request);
        }

        return response()->json(['message' => 'Unauthorized.'], 403);
    }
}

