<?php

namespace App\Http\Controllers\API;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Mail\ResetPasswordMail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class ForgotPasswordController extends BaseController
{   
    /**
     * Request Reset Password
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse   
     *     
     */
    public function requestReset(Request $request)
    {
        $validatedData = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ], [
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.exists' => 'Email tidak terdaftar dalam sistem kami.'
        ]);

        if ($validatedData->fails()) {
            return $this->sendError('Validasi gagal.', $validatedData->errors(), 422);
        }

        $user = User::where('email', $validatedData->validated()['email'])->first();
    
        // Generate random token
        $reset_token = Str::random(60); 
        $user->reset_token = $reset_token;
        $user->reset_token_expires_at = now()->addMinutes(5);
        $user->save();

        // Send email with reset link
        Mail::to($user->email)->send(new ResetPasswordMail($reset_token));

        return $this->sendResponse('Tautan untuk reset kata sandi telah dikirim ke email Anda.', null);
    }

    /**
     * Confirm Reset Password
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse   
     *     
     */
    public function confirmReset(Request $request)
    {
        $request->validate([
            'reset_token' => 'required|string',
        ]);

        $user = User::where('reset_token', $request->reset_token)
                    ->where('reset_token_expires_at', '>', now())
                    ->first();

        if (!$user) {
            return $this->sendError('Token tidak valid atau telah kadaluwarsa.', [], 404);
        }

        // Generate the redirect URL with the token
        $redirectUrl = config('app.frontend_url') . '/lupa-password/change-password';  // The URL where the frontend handles the reset
        $queryParams = http_build_query([
            'token' => $request->reset_token, // Pass the token as a query parameter
        ]);

        // Redirect to the frontend page with the token
        return redirect($redirectUrl . '?' . $queryParams);    
}

    /**
     * Reset and Change Password
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse   
     *     
     */
    public function resetAndChangePassword(Request $request)
    {
        $validatedData = Validator::make($request->all(), [
            'reset_token' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ], [
            'token.required' => 'Token reset diperlukan.',
            'token.string' => 'Token reset harus berupa string.',
            'password.required' => 'Kata sandi baru diperlukan.',
            'password.string' => 'Kata sandi harus berupa teks.',
            'password.min' => 'Kata sandi harus minimal :min karakter.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak cocok.',
        ]);

        // Cek jika validasi gagal
        if ($validatedData->fails()) {
            return $this->sendError('Validasi gagal.', $validatedData->errors(), 422);
        }

        $user = User::where('reset_token', $request->reset_token)
                    ->where('reset_token_expires_at', '>', now())
                    ->first();

        if (!$user) {
            return $this->sendError('Token tidak valid atau telah kadaluwarsa.', [], 404);
        }

        // Update password
        $user->password = Hash::make($request->password);
        $user->reset_token = null; // Hapus token reset
        $user->reset_token_expires_at = null; // Hapus tanggal kedaluwarsa
        $user->save();

        return $this->sendResponse('Kata sandi berhasil diperbarui.', null);
    }
}
