<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Models\ArticleCategory;
use App\Http\Controllers\Controller;
use App\Http\Controllers\API\BaseController;

class ArticleCategoryController extends BaseController
{   
    /**
     * Get List Article Category
     *
     * @return \Illuminate\Http\JsonResponse   
     *     
     */
    public function index()
    {
        $categories = ArticleCategory::select('id', 'name')->paginate(5);
        return $this->sendResponse('Data seluruh kategori artikel berhasil diambil.', $categories);
    }

    /**
     * Get Detail Article Category
     *
     * @param int  $id                                       
     * @return \Illuminate\Http\JsonResponse   
     *     
     */
    public function show($id)
    {
        $category = ArticleCategory::find($id);

        if (!$category) {
            return $this->sendError('Kategori tidak ditemukan', [], 404);
        }

        return $this->sendResponse('Kategori berhasil ditemukan.', $category);
    }

    /**
     * Store Article Category
     *
     * @param  \Illuminate\Http\Request $request                                       
     * @return \Illuminate\Http\JsonResponse   
     *     
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:50',
        ], [
            'name.required' => 'Nama kategori wajib diisi.',
        ]);

        $category = new ArticleCategory;
        $category->name = $request->name;
        $category->save();  

        return $this->sendResponse('Kategori baru berhasil ditambahkan', $category);
    }

    /**
     * Update Article Category
     *
     * @param  \Illuminate\Http\Request $request 
     * @param int  $id                                                                             
     * @return \Illuminate\Http\JsonResponse   
     *     
     */
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

        $category->name = $request->name;
        $category->save();

        return $this->sendResponse('Kategori berhasil diperbarui.', $category);
    }

    /**
     * Delete Article Category
     *
     * @param int  $id                                       
     * @return \Illuminate\Http\JsonResponse   
     *     
     */
    public function destroy($id)
    {
        $category = ArticleCategory::find($id);

        if (!$category) {
            return $this->sendError('Kategori tidak ditemukan', [], 404);
        }
        if ($category->article()->exists()) {
            return $this->sendError("Kategori tidak dapat dihapus karena digunakan oleh artikel tertentu", [], 400);
        }

        $categoryName = $category->name; 
        $category->delete();

        return $this->sendResponse("Kategori '{$categoryName}' berhasil dihapus.", null); 
    }
}
