<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Models\PsikologCategory;
use App\Http\Controllers\Controller;

class CategoryController extends BaseController
{
    public function index()
    {
        $categories = PsikologCategory::all();
        return $this->sendResponse($categories, 'Data seluruh kategori berhasil diambil.');
    }

    public function show($id)
    {
        $category = PsikologCategory::findOrFail($id);

        if (!$category) {
            return $this->sendError('Kategori tidak ditemukan', [], 404);
        }

        return $this->sendResponse($category, 'Kategori berhasil ditemukan.');
    }

    public function store(Request $request)
    {
        $request->validate([
            'category_name' => 'required|string|max:50',
        ], [
            'category_name.required' => 'Nama kategori wajib diisi.',
        ]);

        $category = PsikologCategory::create([
            'category_name' => $request->category_name, 
        ]);

        return $this->sendResponse($category, 'Kategori baru berhasil ditambahkan');
    }

    public function update(Request $request, $id)
    {
        $category = PsikologCategory::findOrFail($id);

        if (!$category) {
            return $this->sendError('Kategori tidak ditemukan', [], 404);
        }

        $request->validate([
            'category_name' => 'required|string|max:50',
        ], [
            'category_name.required' => 'Nama kategori wajib diisi.',
        ]);

        $category->update([
            'category_name' => $request->category_name, 
        ]);

        return $this->sendResponse($category, 'Kategori berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $category = PsikologCategory::findOrFail($id);

        if (!$category) {
            return $this->sendError('Kategori tidak ditemukan', [], 404);
        }

        $categoryName = $category->category_name; // Simpan nama kategori sebelum dihapus
        $category->delete();

        return $this->sendResponse(null, "Kategori '{$categoryName}' berhasil dihapus."); 
    }
}
