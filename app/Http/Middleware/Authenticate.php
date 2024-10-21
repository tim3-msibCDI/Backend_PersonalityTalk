<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        return $request->expectsJson() ? null : null;
    }

     /**
     * Override handle method to return 401 error for unauthenticated users in APIs.
     */
    protected function unauthenticated($request, array $guards)
    {
        // Kembalikan respons JSON jika user tidak terautentikasi
        abort(response()->json(['message' => 'Unauthorized'], 401));
    }
}
