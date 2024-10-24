<?php

namespace App\Http\Controllers\API;

use App\Models\PsikologPrice;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\API\BaseController;

class PsikologPriceController extends BaseController
{
    /**
     * Get all prices.
     */
    public function index()
    {
        $prices = PsikologPrice::all();
        return $this->sendResponse('Data seluruh harga psikolog berhasil diambil.', $prices);
    }

    /**
     * Get a specific price by ID.
     */
    public function show($id)
    {
        $price = PsikologPrice::find($id);

        if (!$price) {
            return $this->sendError('Harga tidak ditemukan', [], 404);
        }

        return $this->sendResponse('Harga berhasil ditemukan.', $price);
    }

    /**
     * Store a new price.
     */
    public function store(Request $request)
    {
        $request->validate([
            'price' => 'required|numeric|min:0',
        ], [
            'price.required' => 'Harga wajib diisi.',
            'price.numeric' => 'Harga harus berupa angka.',
            'price.min' => 'Harga tidak boleh kurang dari 0.',
        ]);

        $price = PsikologPrice::create([
            'price' => $request->price,
        ]);

        return $this->sendResponse('Harga baru berhasil ditambahkan.', $price);
    }

    /**
     * Update an existing price.
     */
    public function update(Request $request, $id)
    {
        $price = PsikologPrice::find($id);

        if (!$price) {
            return $this->sendError('Harga tidak ditemukan', [], 404);
        }

        $request->validate([
            'price' => 'required|numeric|min:0',
        ], [
            'price.required' => 'Harga wajib diisi.',
            'price.numeric' => 'Harga harus berupa angka.',
            'price.min' => 'Harga tidak boleh kurang dari 0.',
        ]);

        $price->update([
            'price' => $request->price,
        ]);

        return $this->sendResponse('Harga berhasil diperbarui.', $price);
    }

    /**
     * Delete a price by ID.
     */
    public function destroy($id)
    {
        $price = PsikologPrice::find($id);

        if (!$price) {
            return $this->sendError('Harga tidak ditemukan', [], 404);
        }

        $priceValue = $price->price;
        $price->delete();

        return $this->sendResponse("Harga sebesar {$priceValue} berhasil dihapus.", null);
    }
}
