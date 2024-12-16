<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Models\PsikologCategory;
use App\Http\Controllers\Controller;
use App\Http\Controllers\API\BaseController;

class PsikologCategoryController extends BaseController
{   
    public function index()
    {
        $categories = PsikologCategory::select('id','category_name')->get();
        return $this->sendResponse('Data seluruh kategori berhasil diambil.', $categories);
    }

    public function show($id)
    {
        $category = PsikologCategory::find($id);

        if (!$category) {
            return $this->sendError('Kategori tidak ditemukan', [], 404);
        }

        return $this->sendResponse('Kategori berhasil ditemukan.', $category);
    }

    public function store(Request $request)
    {
        $request->validate([
            'category_name' => 'required|string|max:50',
        ], [
            'category_name.required' => 'Nama kategori wajib diisi.',
        ]);

        $category = new PsikologCategory();
        $category->category_name = $request->category_name;
        $category->save();
        return $this->sendResponse('Kategori baru berhasil ditambahkan', $category);
    }

    public function update(Request $request, $id)
    {
        $category = PsikologCategory::find($id);

        if (!$category) {
            return $this->sendError('Kategori tidak ditemukan', [], 404);
        }

        $request->validate([
            'category_name' => 'required|string|max:50',
        ], [
            'category_name.required' => 'Nama kategori wajib diisi.',
        ]);

        $category->category_name = $request->category_name;
        $category->save();
        return $this->sendResponse('Kategori berhasil diperbarui.', $category);
    }

    public function destroy($id)
    {
        $category = PsikologCategory::find($id);

        if (!$category) {
            return $this->sendError('Kategori tidak ditemukan', [], 404);
        }

        $categoryName = $category->category_name; 
        $category->delete();

        return $this->sendResponse("Kategori '{$categoryName}' berhasil dihapus.", null); 
    }
}
