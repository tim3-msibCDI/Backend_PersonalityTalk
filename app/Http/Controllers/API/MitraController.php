<?php

namespace App\Http\Controllers\API;

use App\Models\Mitra;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

use App\Http\Controllers\API\BaseController;

class MitraController extends BaseController
{
    /**
     * Menampilkan data seluruh mitra.
     * 
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $mitra = Mitra::select('id', 'name', 'img')->paginate(10);
        return $this->sendResponse('Data seluruh mitra berhasil diambil.', $mitra);
    }
    
    /**
     * Display the specified Mitra resource.
     *
     * @param  int  $id  The ID of the Mitra to retrieve.
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $mitra = Mitra::select('id', 'name', 'img', 'description')->where('id', $id)->first();
        if (!$mitra) {
            return $this->sendError('Data mitra tidak ditemukan.', [], 404);
        }
        return $this->sendResponse('Data mitra berhasil diambil.', $mitra);
    }

    /**
     * Store a newly created Mitra resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request  The request object containing Mitra data.
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validatedData = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'img' => 'required|image|mimes:jpeg,png,jpg|max:512', 
            'description' => 'required|string',
        ], [
            'name.required' => 'Nama wajib diisi.',
            'img.required' => 'Foto wajib diunggah.',
            'img.image' => 'Foto harus berupa file gambar.',
            'img.mimes' => 'Foto harus berformat jpeg, png, atau jpg.',
            'img.max' => 'Foto tidak boleh lebih besar dari 512 KB.',
            'description.required' => 'Konten kesehatan mental wajib diisi.',
        ]);

        if ($validatedData->fails()) {
            return $this->sendError('Validasi gagal', $validatedData->errors(), 422);
        }

        try {
            DB::beginTransaction();
            
            if ($request->hasFile('img')) {
                $imagePath = Storage::disk('public')->put('mitra_photos', $request->file('img'));
                if (!$imagePath) {
                    return $this->sendError('Gagal menyimpan gambar.', [], 500);
                }
            }
            $imageUrl = 'storage/' . $imagePath; 
            $mitra = Mitra::create([
                'name' => $validatedData->validated()['name'],
                'description' => $validatedData->validated()['description'],
                'img' => $imageUrl, 
            ]);
            DB::commit();

            return $this->sendResponse('Mitra baru berhasil ditambahkan.', $mitra);

        } catch (Exception $e) {
            DB::rollback();
            return $this->sendError('Terjadi kesalahan saat menambahkan mitra baru.', [$e->getMessage()], 500);
        }
    }
    

    /**
     * Update the specified Mitra resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request  The request object containing Mitra data.
     * @param  int  $id  The ID of the Mitra to update.
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $mitra = Mitra::find($id);
        if (!$mitra) {
            return $this->sendError('Mitra tidak ditemukan.', [], 404);
        }

        $validatedData = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'img' => 'image|mimes:jpeg,png,jpg|max:512',
            'description' => 'required|string',
        ], [
            'name.required' => 'Nama wajib diisi.',
            'img.image' => 'Foto harus berupa file gambar.',
            'img.mimes' => 'Foto harus berformat jpeg, png, atau jpg.',
            'img.max' => 'Foto tidak boleh lebih besar dari 512 KB.',
            'description.required' => 'Konten kesehatan mental wajib diisi.',
        ]);

        if ($validatedData->fails()) {
            return $this->sendError('Validasi gagal', $validatedData->errors(), 422);
        }

        try {
            DB::beginTransaction();

            $imagePath = null;

            // Periksa apakah ada file gambar baru
            if ($request->hasFile('img')) {
                // Hapus gambar sebelumnya jika ada
                if ($mitra->img && Storage::disk('public')->exists(str_replace('storage/', '', $mitra->img))) {
                    Storage::disk('public')->delete(str_replace('storage/', '', $mitra->img));
                }

                // Simpan gambar baru
                $imagePath = Storage::disk('public')->put('mitra_photos', $request->file('img'));
                if (!$imagePath) {
                    return $this->sendError('Gagal menyimpan gambar.', [], 500);
                }
            }

            $imageUrl = $imagePath ? 'storage/' . $imagePath : $mitra->img; // Gunakan gambar lama jika tidak ada gambar baru

            // Perbarui data mitra
            $mitra->update([
                'name' => $validatedData->validated()['name'],
                'description' => $validatedData->validated()['description'],
                'img' => $imageUrl,
            ]);

            DB::commit();

            return $this->sendResponse('Mitra berhasil diperbarui.', $mitra);

        } catch (Exception $e) {
            DB::rollback();
            return $this->sendError('Terjadi kesalahan saat memperbarui mitra.', [$e->getMessage()], 500);
        }
    }


    /**
     * Remove the specified Mitra resource from storage.
     *
     * @param int $id The ID of the Mitra to delete.
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $mitra = Mitra::find($id);
        if (!$mitra) {
            return $this->sendError('Mitra tidak ditemukan.', [], 404);
        }

        try {
            // Hapus gambar terkait jika ada
            if ($mitra->img) {
                $relativePath = str_replace('storage/', '', $mitra->img); 
                Storage::disk('public')->delete($relativePath);
            }

            $mitra->delete();
            return $this->sendResponse('Mitra berhasil dihapus.', null);
        } catch (Exception $e) {
            return $this->sendError('Terjadi kesalahan saat menghapus mitra.', [$e->getMessage()], 500);
        }
    }
}
