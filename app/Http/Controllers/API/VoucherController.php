<?php

namespace App\Http\Controllers;

use App\Models\Voucher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class VoucherController extends Controller
{
    /**
     * List all vouchers.
     */
    public function index()
    {
        $vouchers = Voucher::all(); //perlu diubah
        return $this->sendResponse('List voucher berhasil diambil.', $vouchers);
    }

    /**
     * Store a new voucher.
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
            'code.unique' => 'Kode voucher sudah digunakan, gunakan kode lain.',
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
        return $this->sendResponse('Voucher baru berhasil ditambahkan.', $vouchers);
    }

    /**
     * Show a specific voucher.
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
     * Update a specific voucher.
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
            'status' => 'required|boolean'
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
        return $this->sendResponse('Voucher berhasil diperbarui.', $voucher);
    }

    /**
     * Delete a specific voucher.
     */
    public function destroy($id)
    {
        $voucher = Voucher::find($id);
        if (!$voucher) {
            return $this->sendError('Voucher tidak ditemukan', [], 404);
        }
        $voucher->delete();
        return $this->sendResponse('Voucher berhasil dihapus.', $voucher);
    }
}
