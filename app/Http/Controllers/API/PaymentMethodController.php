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
        $payments = PaymentMethod::select('id', 'name','no_rek', 'owner', 'type')->paginate(10);
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
            'logo' => 'required|image|mimes:jpeg,png,jpg|max:512',
            'owner' => 'nullable|string|max:100',
            'no_rek' => 'nullable|string|max:50',
        ], [
            'name.required' => 'Nama metode pembayaran wajib diisi.',            
            'type.required' => 'Jenis metode pembayaran wajib diisi.',
            'type.in' => 'Jenis metode pembayaran hanya boleh diisi dengan "Pembayaran Otomatis" atau "Transfer Bank".',
            'bank_code.string' => 'Kode bank harus berupa teks.',            
            'logo.required' => 'Logo metode pembayaran wajib diunggah.',
            'logo.image' => 'Logo harus berupa file gambar.',
            'logo.mimes' => 'Logo hanya boleh berupa gambar dengan format jpeg, png, atau jpg.',
            'logo.max' => 'Logo tidak boleh lebih besar dari 512 KB.',                        
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
            $imageUrl = 'storage/' . $imagePath; 

            // Buat metode pembayaran baru
            $paymentMethod = new PaymentMethod;
            $paymentMethod->name = $validatedData->validated()['name'];
            $paymentMethod->type = $validatedData->validated()['type'];
            $paymentMethod->bank_code = $validatedData->validated()['bank_code'] ?? null; 
            $paymentMethod->logo = $imageUrl; 
            $paymentMethod->owner = $validatedData->validated()['owner'] ?? null;
            $paymentMethod->no_rek = $validatedData->validated()['no_rek'] ?? null; 
            $paymentMethod->is_active = 1; 
            $paymentMethod->save();
            
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
            'name' => 'sometimes|string|max:100',
            'type' => 'sometimes|in:Pembayaran Otomatis,Transfer Bank', // Pastikan enum yang sesuai
            'bank_code' => 'sometimes|nullable|string|max:50',
            'logo' => 'sometimes|image|mimes:jpeg,png,jpg|max:512',
            'owner' => 'sometimes|nullable|string|max:100',
            'no_rek' => 'sometimes|nullable|string|max:50',
        ], [
            'type.in' => 'Jenis metode pembayaran hanya boleh diisi dengan "Pembayaran Otomatis" atau "Transfer Bank".',
            'logo.required' => 'Logo metode pembayaran wajib diunggah.',
            'logo.image' => 'Logo harus berupa file gambar.',
            'logo.mimes' => 'Logo hanya boleh berupa gambar dengan format jpeg, png, atau jpg.',
            'logo.max' => 'Logo tidak boleh lebih besar dari 512 KB.',              
        ]);

        if ($validatedData->fails()) {
            return $this->sendError('Validasi gagal', $validatedData->errors(), 422);
        }

        try {
            DB::beginTransaction();
        
            $paymentMethod = PaymentMethod::find($id);
        
            if (!$paymentMethod) {
                return $this->sendError('Metode pembayaran tidak ditemukan.', [], 404);
            }
        
            // Update logo jika ada file baru
            if ($request->hasFile('logo')) {
                // Hapus logo lama jika ada
                if ($paymentMethod->logo) {
                    $relativePath = str_replace('storage/', '', $paymentMethod->logo); 
                    Storage::disk('public')->delete($relativePath);
                }
        
                // Simpan logo baru
                $imagePath = $request->file('logo')->store('payment_methods', 'public'); // Simpan di disk 'public'
                if (!$imagePath) {
                    return $this->sendError('Gagal menyimpan gambar.', [], 500);
                }
        
                // Simpan path logo ke database
                $paymentMethod->logo = 'storage/' . $imagePath;
            }
        
            // Update data lainnya
            $validated = $validatedData->validated(); 
            $paymentMethod->name = $validated['name'] ?? $paymentMethod->name;
            $paymentMethod->type = $validated['type'] ?? $paymentMethod->type;
            $paymentMethod->bank_code = $validated['bank_code'] ?? $paymentMethod->bank_code;
            $paymentMethod->no_rek = $validated['no_rek'] ?? $paymentMethod->no_rek;
            $paymentMethod->owner = $validated['owner'] ?? $paymentMethod->owner;
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
            if ($paymentMethod->logo) {
                $relativePath = str_replace('storage/', '', $paymentMethod->logo); 
                Storage::disk('public')->delete($relativePath);
            }
            // Hapus metode pembayaran dari database
            $paymentMethod->delete();
            return $this->sendResponse('Metode pembayaran berhasil dihapus.', null);
        } catch (Exception $e) {
            return $this->sendError('Terjadi kesalahan saat menghapus metode pembayaran.', [$e->getMessage()], 500);
        }
    }

    public function listPsikologBank()
    {
        // dd("halllo");
        $banks = PaymentMethod::where('id', '!=', 1)->select('id', 'name')->get();
        return $this->sendResponse('List bank berhasil diambil.', $banks);
    }

}
