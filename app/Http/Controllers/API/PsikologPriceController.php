<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Models\PsikologPrice;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
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
        $validatedData = Validator::make($request->all(),[
            'code' => 'required|unique:psikolog_prices,code',
            'price' => 'required|numeric|min:0',
        ], [
            'code.required' => 'Kode psikolog wajib diisi.',
            'code.unique' => 'Kode psikolog sudah digunakan.',
            'price.required' => 'Harga wajib diisi.',
            'price.numeric' => 'Harga harus berupa angka.',
            'price.min' => 'Harga tidak boleh kurang dari 0.',
        ]);

        if($validatedData->fails()){
            return $this->sendError('Validasi gagal.', $validatedData->errors(), 422);
        }

        $price = new PsikologPrice();
        $price->code = $request->code;
        $price->price = $request->price;
        $price->save();

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

        $validatedData = Validator::make($request->all(),[
            'code' => 'sometimes|unique:psikolog_prices,code,' . $id,
            'price' => 'sometimes|numeric|min:0',
        ], [
            'code.required' => 'Kode psikolog wajib diisi.',
            'code.unique' => 'Kode psikolog sudah digunakan.',
            'price.required' => 'Harga wajib diisi.',
            'price.numeric' => 'Harga harus berupa angka.',
            'price.min' => 'Harga tidak boleh kurang dari 0.',
        ]);

        if($validatedData->fails()){
            return $this->sendError('Validasi gagal.', $validatedData->errors(), 422);
        }

        $price->code = $request->code;
        $price->price = $request->price;
        $price->save();
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
        $price->delete();
        return $this->sendResponse("Harga sebesar berhasil dihapus.", null);
    }
}
