<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminAuth
{
    public function handle(Request $request, Closure $next)
    {
        // Memeriksa apakah pengguna yang masuk adalah admin
        if (!$request->user() || !$request->user()->tokenCan('admin')) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        return $next($request);
    }
}

