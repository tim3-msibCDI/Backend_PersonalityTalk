<?php

namespace App\Http\Controllers\API;

use App\Models\Disease;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\API\BaseController;

class DiseaseController extends BaseController
{
    /**
     * Get List Kesehatan Mental - User
     * 
     * @unauthenticated
     * @return \Illuminate\Http\JsonResponse   
     *     
     */
    public function listUserDisease()
    {
        $diseases = Disease::select('id', 'disease_name')
            ->orderBy('disease_name', 'asc')
            ->get();

        return $this->sendResponse('List informasi kesehatan mental baru berhasil diambil.', $diseases);
    }

    /**
     * Get Detail Kesehatan Mental - User
     *
     * @unauthenticated
     * @param int  $id                                                                              
     * @return \Illuminate\Http\JsonResponse   
     *     
     */
    public function showDiseaseDetail($id)
    {
        $disease = DB::table('diseases as d')
            ->join('admins as wr', 'd.admin_id', '=', 'wr.id')
            ->where('d.id', $id)
            ->select('d.id', 'd.disease_name', 'd.disease_img', 'd.content', 'wr.id as writer_id', 'wr.name as writer_name')
            ->first();
    
        if (!$disease) {
            return $this->sendError('Detail informasi kesehatan mental tidak ditemukan.', [], 404);
        }

        return $this->sendResponse('Berhasil mengambil detail artikel untuk Pengguna.', $disease);
    }

    /**
     * Get List Kesehatan Mental - Admin
     *                                                                             
     * @return \Illuminate\Http\JsonResponse   
     *     
     */
    public function listAdminDisease()
    {
        $diseases = Disease::select('id', 'disease_name')->paginate(10);
        return $this->sendResponse('Berhasil mengambil list kesehatan untuk Admin.', $diseases);
    }

    /**
     * Store Kesehatan Mental - Admin
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse   
     *     
     */
    public function store(Request $request)
    {
        $validatedData = Validator::make($request->all(), [
            'disease_name' => 'required|string|max:255',
            'disease_img' => 'required|image|mimes:jpeg,png,jpg|max:2048', 
            'admin_id' => 'required|exists:admins,id',
            'content' => 'required|string',
        ], [
            'disease_name.required' => 'Nama wajib diisi.',
            'content.required' => 'Konten kesehatan mental wajib diisi.',
            'disease_img.required' => 'Foto wajib diunggah.',
            'disease_img.image' => 'Foto harus berupa file gambar.',
            'disease_img.mimes' => 'Foto harus berformat jpeg, png, atau jpg.',
            'disease_img.max' => 'Foto tidak boleh lebih besar dari 2MB.',
            'admin_id.required' => 'Admin wajib dipilih.',
            'admin_id.exists' => 'Admin yang dipilih tidak valid.'
        ]);

        if ($validatedData->fails()) {
            return $this->sendError('Validasi gagal', $validatedData->errors(), 422);
        }

        try {
            DB::beginTransaction();
            
            if ($request->hasFile('disease_img')) {
                $imagePath = Storage::disk('public')->put('diseases_photos', $request->file('disease_img'));
                if (!$imagePath) {
                    return $this->sendError('Gagal menyimpan gambar.', [], 500);
                }
            }
            $imageUrl = 'storage/' . $imagePath; 
            $penyakitMental = Disease::create([
                'disease_name' => $validatedData->validated()['disease_name'],
                'content' => $validatedData->validated()['content'],
                'admin_id' => $validatedData->validated()['admin_id'],
                'disease_img' => $imageUrl, 
            ]);
            DB::commit();

            return $this->sendResponse('Informasi kesehatan mental baru berhasil dibuat.', $penyakitMental);

        } catch (Exception $e) {
            DB::rollback();
            return $this->sendError('Terjadi kesalahan saat membuat konten kesehatan mental.', [$e->getMessage()], 500);
        }
    }

    /**
     * Get Detail Kesehatan Mental - Admin
     *
     * @param int  $id                                                                              
     * @return \Illuminate\Http\JsonResponse   
     *     
     */
    public function show($id)
    {
        $penyakitMental = Disease::with('admin_writer')->find($id);

        if (!$penyakitMental) {
            return $this->sendError('Penyakit mental tidak ditemukan.', [], 404);
        }

        return $this->sendResponse('Penyakit mental berhasil ditemukan.', $penyakitMental);
    }

    /**
     * Update Kesehatan Mental - Admin
     *
     * @param  \Illuminate\Http\Request $request
     * @param int  $id                                                                              
     * @return \Illuminate\Http\JsonResponse   
     *     
     */
    public function update(Request $request, $id)
    {
        $penyakitMental = Disease::find($id);

        if (!$penyakitMental) {
            return $this->sendError('Penyakit mental tidak ditemukan.', [], 404);
        }

        $validatedData = Validator::make($request->all(), [
            'disease_name' => 'required|string|max:255',
            'disease_img' => 'required|image|mimes:jpeg,png,jpg|max:2048', 
            'admin_id' => 'required|exists:admins,id',
            'content' => 'required|string',
        ], [
            'disease_name.required' => 'Nama wajib diisi.',
            'content.required' => 'Konten kesehatan mental wajib diisi.',
            'disease_img.required' => 'Foto wajib diunggah.',
            'disease_img.image' => 'Foto harus berupa file gambar.',
            'disease_img.mimes' => 'Foto harus berformat jpeg, png, atau jpg.',
            'disease_img.max' => 'Foto tidak boleh lebih besar dari 2MB.',
            'admin_id.required' => 'Admin wajib dipilih.',
            'admin_id.exists' => 'Admin yang dipilih tidak valid.'
        ]);

        if ($validatedData->fails()) {
            return $this->sendError('Validasi gagal', $validatedData->errors(), 422);
        }

        try {
            DB::beginTransaction();
            
            $dataToUpdate = $validatedData->validated();

            // Jika ada file gambar baru, simpan dan hapus gambar lama
            if ($request->hasFile('disease_img')) {
                // Simpan gambar baru
                $imagePath = Storage::disk('public')->put('diseases_photos', $request->file('disease_img'));
                if (!$imagePath) {
                    return $this->sendError('Gagal memperbarui gambar.', [], 500);
                }

                // Hapus gambar lama jika ada
                if ($penyakitMental->disease_img) {
                    Storage::disk('public')->delete($penyakitMental->disease_img);
                }
                $imageUrl = 'storage/' . $imagePath; 
                $dataToUpdate['disease_img'] = $imageUrl;
            }

            // Update penyakit mental 
            $penyakitMental->update($dataToUpdate);
            DB::commit();
            return $this->sendResponse('Informasi kesehatan mental berhasil dipebarui.', $penyakitMental);

        } catch (Exception $e) {
            DB::rollback();
            return $this->sendError('Terjadi kesalahan saat memperbarui informasi kesehatan mental.', [$e->getMessage()], 500);
        }
    }

    /**
     * Delete Kesehatan Mental - Admin
     *
     * @param int  $id                                                                              
     * @return \Illuminate\Http\JsonResponse   
     *     
     */
    public function destroy($id)
    {
        $penyakitMental = Disease::find($id);
        if (!$penyakitMental) {
            return $this->sendError('Penyakit mental tidak ditemukan.', [], 404);
        }

        try {
            DB::beginTransaction();

            // Hapus gambar terkait jika ada
            if ($penyakitMental->disease_img) {
                Storage::disk('public')->delete($penyakitMental->disease_img);
            }
            $penyakitMental->delete();

            DB::commit();
            return $this->sendResponse('Informasi kesehatan mental berhasil dihapus.', null);

        } catch (Exception $e) {
            DB::rollback();
            return $this->sendError('Terjadi kesalahan saat menghapus informasi kesehatan mental.', [$e->getMessage()], 500);
        }
    }
}
