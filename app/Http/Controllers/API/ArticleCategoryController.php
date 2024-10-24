<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Models\ArticleCategory;
use App\Http\Controllers\Controller;
use App\Http\Controllers\API\BaseController;

class ArticleCategoryController extends BaseController
{
    public function index()
    {
        $categories = ArticleCategory::select('id', 'name')->get();
        return $this->sendResponse('Data seluruh kategori artikel berhasil diambil.', $categories);
    }

    public function show($id)
    {
        $category = ArticleCategory::find($id);

        if (!$category) {
            return $this->sendError('Kategori tidak ditemukan', [], 404);
        }

        return $this->sendResponse('Kategori berhasil ditemukan.', $category);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:50',
        ], [
            'name.required' => 'Nama kategori wajib diisi.',
        ]);

        $category = ArticleCategory::create([
            'name' => $request->name, 
        ]);

        return $this->sendResponse('Kategori baru berhasil ditambahkan', $category);
    }

    public function update(Request $request, $id)
    {
        $category = ArticleCategory::find($id);

        if (!$category) {
            return $this->sendError('Kategori tidak ditemukan', [], 404);
        }

        $request->validate([
            'name' => 'required|string|max:50',
        ], [
            'name.required' => 'Nama kategori artikel wajib diisi.',
        ]);

        $category->update([
            'name' => $request->name, 
        ]);

        return $this->sendResponse('Kategori berhasil diperbarui.', $category);
    }

    public function destroy($id)
    {
        $category = ArticleCategory::find($id);

        if (!$category) {
            return $this->sendError('Kategori tidak ditemukan', [], 404);
        }

        $categoryName = $category->name; 
        $category->delete();

        return $this->sendResponse("Kategori '{$categoryName}' berhasil dihapus.", null); 
    }
}
