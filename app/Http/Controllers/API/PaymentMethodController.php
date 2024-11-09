<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Models\PaymentMethod;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\API\BaseController;

class PaymentMethodController extends BaseController
{
    /**
     * Get Payment Method List - Admin
     * 
     * @return \Illuminate\Http\JsonResponse   
     *     
     */
    public function index()
    {
        $payments = PaymentMethod::select('id', 'name','no_rek', 'type')->get();
        return $this->sendResponse('List metode pembayaran berhasil diambil.', $payments);
    }

    /**
     * Store Payment Method - Admin
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse   
     *     
     */
    public function store(Request $request)
    {
        $validatedData = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'type' => 'required|in:Pembayaran Otomatis,Transfer Bank',
            'bank_code' => 'nullable|string|max:50',
            'logo' => 'required|image|mimes:jpeg,png,jpg',  
            'no_rek' => 'nullable|string|max:50',
        ], [
            'name.required' => 'Nama metode pembayaran wajib diisi.',            
            'type.required' => 'Jenis metode pembayaran wajib diisi.',
            'bank_code.string' => 'Kode bank harus berupa teks.',            
            'logo.required' => 'Logo metode pembayaran wajib diisi.',                        
        ]);

        if ($validatedData->fails()) {
            return $this->sendError('Validasi gagal', $validatedData->errors(), 422);
        }

        try {
            DB::beginTransaction();
            
            if ($request->hasFile('logo')) {
                $imagePath = Storage::disk('public')->put('payment_methods', $request->file('logo'));
    
                if (!$imagePath) {
                    return $this->sendError('Gagal menyimpan gambar.', [], 500);
                }
            }  
            $paymentMethod = PaymentMethod::create([
                'name' => $validatedData->validated()['name'],
                'type' => $validatedData->validated()['type'],
                'bank_code' => $validatedData->validated()['bank_code'] ?? null, 
                'logo' => $imagePath, 
                'no_rek' => $validatedData->validated()['no_rek'] ?? null, 
                'is_active' => 1, 
            ]);
            
            DB::commit();
            return $this->sendResponse('Metode pembayaran baru berhasil dibuat.', $paymentMethod);

        } catch (Exception $e) {
            DB::rollback();
            return $this->sendError('Terjadi kesalahan saat menyimpan metode pembayaran baru.', [$e->getMessage()], 500);
        }
    }

    /**
     * Update Payment Method - Admin
     *
     * @param  \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $validatedData = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'type' => 'required|in:Pembayaran Otomatis,Transfer Bank', // Pastikan enum yang sesuai
            'bank_code' => 'nullable|string|max:50',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg',
            'no_rek' => 'nullable|string|max:50',
        ], [
            'name.required' => 'Nama metode pembayaran wajib diisi.',            
            'type.required' => 'Jenis metode pembayaran wajib diisi.',
            'type.in' => 'Jenis metode pembayaran tidak valid.',
            'bank_code.string' => 'Kode bank harus berupa teks.',
            'logo.image' => 'Logo harus berupa file gambar.',
            'no_rek.string' => 'Nomor rekening harus berupa teks.',
        ]);

        if ($validatedData->fails()) {
            return $this->sendError('Validasi gagal', $validatedData->errors(), 422);
        }

        try {
            DB::beginTransaction();

            $paymentMethod = PaymentMethod::findOrFail($id);

            // Update logo jika ada file baru
            if ($request->hasFile('logo')) {
                if ($paymentMethod->logo) {
                    Storage::disk('public')->delete($paymentMethod->logo);
                }

                $imagePath = Storage::disk('public')->put('payment_methods', $request->file('logo'));
                if (!$imagePath) {
                    return $this->sendError('Gagal menyimpan gambar.', [], 500);
                }
                $paymentMethod->logo = $imagePath;
            }

            // Update data lainnya
            $paymentMethod->name = $validatedData->validated()['name'];
            $paymentMethod->type = $validatedData->validated()['type'];
            $paymentMethod->bank_code = $validatedData->validated()['bank_code'] ?? null;
            $paymentMethod->no_rek = $validatedData->validated()['no_rek'] ?? null;
            $paymentMethod->is_active = $request->has('is_active') ? (bool)$request->is_active : $paymentMethod->is_active;
            $paymentMethod->save();

            DB::commit();
            return $this->sendResponse('Metode pembayaran berhasil diperbarui.', $paymentMethod);

        } catch (Exception $e) {
            DB::rollback();
            return $this->sendError('Terjadi kesalahan saat memperbarui metode pembayaran.', [$e->getMessage()], 500);
        }
    }

    /**
     * Detail Payment Method - Admin
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $paymentMethod = PaymentMethod::find($id);
        if (!$paymentMethod) {
            return $this->sendError('Metode pembayaran tidak ditemukan', [] , 404);
        }
        return $this->sendResponse('Metode pembayaran berhasil ditemukan.', $paymentMethod);
    }

    /**
     * Delete Payment Method - Admin
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {   
        $paymentMethod = PaymentMethod::find($id);
        if (!$paymentMethod) {
            return $this->sendError('Metode pembayaran tidak ditemukan.', [], 404);
        }

        try {
            // Hapus file logo dari storage jika ada
            if (Storage::disk('public')->exists($paymentMethod->logo)) {
                Storage::disk('public')->delete($paymentMethod->logo);
            }

            // Hapus metode pembayaran dari database
            $paymentMethod->delete();
            return $this->sendResponse('Metode pembayaran berhasil dihapus.', null);
        } catch (Exception $e) {
            return $this->sendError('Terjadi kesalahan saat menghapus metode pembayaran.', [$e->getMessage()], 500);
        }
    }

}
