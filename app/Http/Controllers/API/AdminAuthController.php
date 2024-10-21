<?php

namespace App\Http\Controllers\API;

use App\Models\Admin;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\API\BaseController;

class AdminAuthController extends BaseController
{
    public function loginAdmin(Request $request)
    {
        $validatedData = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string',
        ],[
            'email.required' => 'Email wajib diisi',
            'password.required' => 'Password wajib diisi',
        ])->validate();

        // Cek apakah admin dengan username yang diberikan ada di database
        $admin = Admin::where('email', $request->email)->first();
        if (!$admin) {
            return $this->sendError('Email anda tidak terdaftar.', [], 404);
        }

        // Cek apakah password valid
        if (!Hash::check($request->password, $admin->password)) {
            return $this->sendError('Password anda salah.', [], 401);
        }

        // Jika kredensial valid, buat token
        $token = $admin->createToken('PersonalityTalk', ['admin'])->plainTextToken;
        $success = [
            'token' => $token,
            'name' => $admin->name,
        ];
        return $this->sendResponse($success, 'Anda berhasil login.');
    }


}
