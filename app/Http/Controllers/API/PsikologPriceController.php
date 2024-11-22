<?php

namespace App\Http\Controllers\API;

use App\Models\PsikologPrice;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\API\BaseController;

class PsikologPriceController extends BaseController
{
    /**
     * Psikolog Price List - Admin
     * 
     * @return \Illuminate\Http\JsonResponse   
     * 
     */
    public function index()
    {
        $prices = PsikologPrice::select('id', 'code', 'price')->paginate(10);
        return $this->sendResponse('Data seluruh harga psikolog berhasil diambil.', $prices);
    }

    /**
     * Detail Psikolog Price
     * 
     * @param int  $id                                                                              
     * @return \Illuminate\Http\JsonResponse   
     * 
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
     * Store Psikolog Price
     * 
     * @param  \Illuminate\Http\Request $request                                                                            
     * @return \Illuminate\Http\JsonResponse   
     * 
     */
    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required',
            'price' => 'required|numeric|min:0',
        ], [
            'code.required' => 'Kode psikolog wajib diisi',
            'price.required' => 'Harga wajib diisi.',
            'price.numeric' => 'Harga harus berupa angka.',
            'price.min' => 'Harga tidak boleh kurang dari 0.',
        ]);

        $price = PsikologPrice::create([
            'code' => $request->code,
            'price' => $request->price,
        ]);

        return $this->sendResponse('Harga baru berhasil ditambahkan.', $price);
    }

    /**
     * Update Psikolog Price
     * 
     * @param  \Illuminate\Http\Request $request
     * @param int  $id                                                                              
     * @return \Illuminate\Http\JsonResponse   
     * 
     */
    public function update(Request $request, $id)
    {
        $price = PsikologPrice::find($id);

        if (!$price) {
            return $this->sendError('Harga tidak ditemukan', [], 404);
        }

        $request->validate([
            'code' => 'required',
            'price' => 'required|numeric|min:0',
        ], [
            'code.required' => 'Kode psikolog wajib disi',
            'price.required' => 'Harga wajib diisi.',
            'price.numeric' => 'Harga harus berupa angka.',
            'price.min' => 'Harga tidak boleh kurang dari 0.',
        ]);

        $price->update([
            'code' => $request->code,
            'price' => $request->price,
        ]);

        return $this->sendResponse('Harga berhasil diperbarui.', $price);
    }

    /**
     * Delete Psikolog Price
     * 
     * @param int  $id                                                                              
     * @return \Illuminate\Http\JsonResponse   
     * 
     */
    public function destroy($id)
    {
        $price = PsikologPrice::find($id);
        if (!$price) {
            return $this->sendError('Harga tidak ditemukan', [], 404);
        }
        if ($price->psikolog()->exists()) {
            return $this->sendError('Harga masih digunakan dan tidak bisa dihapus', [], 400);
        }

        $priceValue = $price->price;
        $price->delete();

        return $this->sendResponse("Harga sebesar berhasil dihapus.", null);
    }
}
