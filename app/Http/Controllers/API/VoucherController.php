<?php

namespace App\Http\Controllers\API;

use Carbon\Carbon;
use App\Models\Voucher;
use App\Models\Psikolog;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class VoucherController extends BaseController
{
    /**
     * Get List Voucher
     *                                                                            
     * @return \Illuminate\Http\JsonResponse   
     *     
     */
    public function index()
    {
        $vouchers = Voucher::all(); //perlu diubah
        return $this->sendResponse('List voucher berhasil diambil.', $vouchers);
    }
    
    /**
     * Store Voucher
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse   
     *     
     */
    public function store(Request $request)
    {
        $validatedData = Validator::make($request->all(), [
            'code' => 'required|string|max:50|unique:vouchers,code',
            'voucher_type' => 'required|in:consultation,course',
            'discount_value' => 'required|numeric',
            'min_transaction_amount' => 'required|numeric',
            'valid_from' => 'required|date',
            'valid_to' => 'required|date|after:valid_from',
            'quota' => 'required|nullable|integer',
        ],[
            'code.required' => 'Kode voucher wajib diisi.',
            'code.unique' => 'Kode voucher sudah ada, gunakan kode lain.',
            'voucher_type.required' => 'Jenis voucher wajib diisi.',
            'discount_value.required' => 'Nilai diskon wajib diisi.',
            'discount_value.numeric' => 'Nilai diskon harus berupa angka.',
            'valid_from.required' => 'Tanggal mulai voucher wajib diisi',
            'valid_from.date' => 'Tanggal mulai harus berupa tanggal yang valid.',
            'valid_to.required' => 'Tanggal berakhir voucher wajib diisi.',
            'valid_to.date' => 'Tanggal berakhir harus berupa tanggal yang valid.',
            'valid_to.after' => 'Tanggal berakhir harus setelah tanggal mulai.',
            'quota.required' => 'Jumlah kuota voucher wajib diisi.',
            'quota.integer' => 'Kuota harus berupa angka bulat.'
        ]);

        if ($validatedData->fails()) {
            return $this->sendError('Validasi gagal', $validatedData->errors(), 422);
        }
        $voucher = Voucher::create($request->all());
        return $this->sendResponse('Voucher baru berhasil ditambahkan.', $voucher);
    }

    /**
     * Get Detail Voucher
     *
     * @param int  $id                                                                              
     * @return \Illuminate\Http\JsonResponse   
     *     
     */
    public function show($id)
    {
        $voucher = Voucher::find($id);
        if (!$voucher) {
            return $this->sendError('Voucher tidak ditemukan', [] , 404);
        }
        return $this->sendResponse('Detail voucher berhasil ditemukan.', $voucher);
    }
    
    /**
     * Update Voucher
     *
     * @param  \Illuminate\Http\Request $request
     * @param int  $id                                                                              
     * @return \Illuminate\Http\JsonResponse   
     *     
     */
    public function update(Request $request, $id)
    {
        $voucher = Voucher::find($id);
        
        if (!$voucher) {
            return $this->sendError('Voucher tidak ditemukan', [] , 404);
        }

        $validatedData = Validator::make($request->all(), [
            'code' => 'required|string|max:50|unique:vouchers,code,' . $voucher->id,
            'voucher_type' => 'required|in:consultation,course',
            'discount_value' => 'required|numeric',
            'min_transaction_amount' => 'nullable|numeric',
            'valid_from' => 'nullable|date',
            'valid_to' => 'nullable|date|after:valid_from',
            'quota' => 'required|nullable|integer|min:0',
            'is_active' => 'required|boolean'
        ],[
            'code.required' => 'Kode voucher wajib diisi.',
            'code.unique' => 'Kode voucher sudah digunakan, gunakan kode lain.',
            'voucher_type.required' => 'Jenis voucher wajib diisi.',
            'discount_value.required' => 'Nilai diskon wajib diisi.',
            'discount_value.numeric' => 'Nilai diskon harus berupa angka.',
            'valid_from.date' => 'Tanggal mulai harus berupa tanggal yang valid.',
            'valid_to.date' => 'Tanggal berakhir harus berupa tanggal yang valid.',
            'valid_to.after' => 'Tanggal berakhir harus setelah tanggal mulai.',
            'quota.required' => 'Jumlah kuota voucher wajib diisi.',
            'quota.integer' => 'Kuota harus berupa angka bulat.'
        ]);

        if ($validatedData->fails()) {
            return $this->sendError('Validasi gagal', $validatedData->errors(), 422);
        }
        $voucher->update($request->all());
        $code = $voucher->code;
        return $this->sendResponse('Voucher '. $code .' berhasil diperbarui.', $voucher);
    }

    /**
     * Delete Voucher
     *
     * @param int  $id                                                                              
     * @return \Illuminate\Http\JsonResponse   
     *     
     */
    public function destroy($id)
    {
        $voucher = Voucher::find($id);
        if (!$voucher) {
            return $this->sendError('Voucher tidak ditemukan', [], 404);
        }
        $code = $voucher->code; 
        $voucher->delete();
        return $this->sendResponse('Voucher '. $code .' berhasil dihapus.', null);
    }

    /**
     * Redeem Consultation Voucher 
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse   
     *     
     */
    public function redeemConsultationVoucher(Request $request)
    {
        // Validasi request
        $validatedData = Validator::make($request->all(), [
            'code' => 'required|string', 
            'psi_id' => 'required|exists:psikolog,id',
        ], [
            'code.required' => 'Kode voucher wajib diisi.',
            'psi_id.required' => 'Psikolog harus dipilih.',
            'psi_id.exists' => 'Psikolog tidak valid.',
        ]);

        if ($validatedData->fails()) {
            return $this->sendError('Validasi gagal', $validatedData->errors(), 422);
        }

        // Cari psikolog price
        $psikolog = Psikolog::with('psikolog_price')->findOrFail($request->psi_id);
        $transaction_amount = $psikolog->psikolog_price->price;

        // Cari voucher
        $voucher = Voucher::where('code', $request->code)->first();
        if (!$voucher) {
            return $this->sendError('Kode voucher tidak ditemukan', [], 422);
        }
        // Cek status aktif
        if (!$voucher->is_active) { 
            return $this->sendError('Voucher sudah tidak dapat digunakan', [], 422);
        }
        // cek hanya dapat dipakai di consultasi
        if ($voucher->voucher_type !== 'consultation') {
            return $this->sendError('Voucher ini tidak bisa digunakan untuk konsultasi.', [], 400);
        }
        // Cek tanggal berlaku
        $now = Carbon::now(); 
        if ($voucher->valid_from && $now->lt($voucher->valid_from)) {
            return $this->sendError('Voucher belum berlaku', [], 422);
        }
        if ($voucher->valid_to && $now->gt($voucher->valid_to)) {
            return $this->sendError('Voucher sudah kadaluwarsa', [], 422);
        }

        // Cek kuota penggunaan voucher
        if (!is_null($voucher->quota) && $voucher->used >= $voucher->quota) {
            return $this->sendError('Voucher sudah mencapai batas penggunaan', [], 422);
        }

        // Cek minimum transaksi
        if ($voucher->min_transaction_amount && $transaction_amount < $voucher->min_transaction_amount) {
            return $this->sendError('Jumlah minimum transkasi belum terpenuhi', [], 422);
        }

        // Hitung diskon, jika diskon melebihi jumlah transaksi maka ambil diskon berasal dari jumlah transaksi
        $discount = min($voucher->discount_value, $transaction_amount);

        return $this->sendResponse(
            'Voucher berhasil diredeem.', 
            [
                'voucher_code' => $voucher->code,
                'discount_value' => $discount,
                'final_amount' => $transaction_amount - $discount,
            ]
        );

    }
}
