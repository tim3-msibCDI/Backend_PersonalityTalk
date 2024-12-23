<?php

namespace App\Http\Controllers\API;

use App\Models\Admin;
use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Models\ArticleCategory;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Resources\ArticleResource;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\API\BaseController;
use App\Http\Resources\DetailArticleResource;

class ArticleController extends BaseController
{   
    /**
     * Get List Article Category - User
     * @unauthenticated
     * @return \Illuminate\Http\JsonResponse 
     *       
     */
    public function listCategoryArticle()
    {
        $categories = ArticleCategory::select('id as category_id', 'name')->get();
        return $this->sendResponse('Berhasil mengambil daftar kategori artikel untuk Pengguna.', $categories);
    }

    /**
     * Get List Article - User
     * @unauthenticated
     * @param  \Illuminate\Http\Request $request                                       
     * @return \Illuminate\Http\JsonResponse   
     *     
     */
    public function listUserArticle(Request $request)
    {
        $categoryId = $request->get('category_id', 1);

        $articles = Article::with(['admin_writer', 'article_category'])
            ->where('category_id', $categoryId)
            ->select('id', 'article_img', 'article_title', 'publication_date', 'admin_id', 'category_id')
            ->paginate(12);

        $articles->setCollection($articles->getCollection()->map(function ($article) {
            return [
                'id' => $article->id,
                'article_img' => $article->article_img,
                'article_title' => $article->article_title,
                'publication_date' => Carbon::parse($article->publication_date)->translatedFormat('d F Y'), 
                'admin_writer' => [
                    'id' => $article->admin_writer->id,
                    'name' => $article->admin_writer->name,
                ],
                'article_category' => [
                    'id' => $article->article_category->id,
                    'category_name' => $article->article_category->category_name,
                ],
            ];
        }));

        return $this->sendResponse('Berhasil mengambil daftar artikel untuk Pengguna.', $articles);
    }

    /**
     * Get Detail and Related Articles - User
     * @unauthenticated
     * @param int  $id                                       
     * @return \Illuminate\Http\JsonResponse  
     *      
     */
    public function showArticleWithRelated($id)
    {
        $article = Article::with(['admin_writer', 'article_category'])->find($id);

        if (!$article) {
            return $this->sendError('Artikel tidak ditemukan.', [], 404);
        }

        $relatedArticles = Article::where('category_id', $article->category_id)
            ->where('id', '!=', $id)
            ->select('id', 'article_img', 'article_title', 'publication_date')
            ->inRandomOrder()
            ->limit(5)
            ->get();
        
        return $this->sendResponse(
            'Berhasil mengambil detail artikel untuk Pengguna.', 
            [
                'article' => new DetailArticleResource($article), 
                'related_articles' => ArticleResource::collection($relatedArticles), 
            ]
        );

    }

    /**
     * Get Articles Written by Admin - User
     * @unauthenticated
     * @param int  $id                                       
     * @return \Illuminate\Http\JsonResponse 
     *       
     */
    public function getArticlesByAdmin($id)
    {
        $admin = Admin::with('articles')->find($id);
        if (!$admin) {
            return $this->sendError('Artikel tidak ditemukan.', [], 404);
        }

        $articles = Article::where('admin_id', $id)->paginate(12);
        $formattedArticles = ArticleResource::collection($articles);

        return $this->sendResponse(
            'Berhasil mengambil artikel berdasarkan Penulis.', 
            [
                'admin' => [
                    'name' => $admin->name,
                ],
                'articles' => $formattedArticles, 
            ]
        );
    }

    /**
     * Get List Article - Admin
     *
     * @param  \Illuminate\Http\Request $request                                       
     * @return \Illuminate\Http\JsonResponse   
     *     
     */
    public function listAdminArticle(Request $request)
    {
        $articles = Article::select('id', 'article_title', 'article_img')->paginate(10);
        return $this->sendResponse('Berhasil mengambil list artikel untuk Admin.', $articles);
    }

    public function searchArticle(Request $request){

        $request->validate([
            'search' => 'nullable|string|max:255',
        ]);

        $articles = Article::select('id', 'article_title', 'article_img')
            ->where('article_title', 'like', '%' . $request->search . '%')
            ->paginate(10);

        return $this->sendResponse('Berhasil mengambil daftar artikel untuk Admin.', $articles);
    }

    /**
     * Store Article - Admin
     *
     * @param  \Illuminate\Http\Request $request                                       
     * @return \Illuminate\Http\JsonResponse   
     *     
     */
    public function store(Request $request)
    {
        $validatedData = Validator::make($request->all(), [
            'article_title' => 'required|string|max:255',
            'content' => 'required|string',
            'publication_date' => 'required|date',
            'publisher_name' => 'required|string|max:255',
            'article_img' => 'required|image|mimes:jpeg,png,jpg|max:2048', 
            'admin_id' => 'required|exists:admins,id',
            'category_id' => 'required|exists:article_categories,id',
        ], [
            'article_title.required' => 'Judul artikel wajib diisi.',
            'content.required' => 'Konten artikel wajib diisi.',
            'publication_date.required' => 'Tanggal publikasi wajib diisi.',
            'publication_date.date' => 'Tanggal publikasi harus berupa tanggal yang valid.',
            'publisher_name.required' => 'Nama penerbit wajib diisi.',
            'article_img.required' => 'Foto artikel wajib diunggah.',
            'article_img.image' => 'Foto artikel harus berupa file gambar.',
            'article_img.mimes' => 'Foto artikel harus berformat jpeg, png, atau jpg.',
            'article_img.max' => 'Foto artikel tidak boleh lebih besar dari 2MB.',
            'admin_id.required' => 'Admin wajib dipilih.',
            'admin_id.exists' => 'Admin yang dipilih tidak valid.',
            'category_id.required' => 'Kategori artikel wajib dipilih.',
            'category_id.exists' => 'Kategori yang dipilih tidak valid.'
        ]);

        if ($validatedData->fails()) {
            return $this->sendError('Validasi gagal', $validatedData->errors(), 422);
        }

        try {
            DB::beginTransaction();
            
            if ($request->hasFile('article_img')) {
                $imagePath = Storage::disk('public')->put('article_photos', $request->file('article_img'));
                if (!$imagePath) {
                    return $this->sendError('Gagal menyimpan gambar.', [], 500);
                }
            }
            $imageUrl = 'storage/' . $imagePath; 
            
            $article = new Article();
            $article->article_title = $validatedData->validated()['article_title'];
            $article->content = $validatedData->validated()['content'];
            $article->publication_date = $validatedData->validated()['publication_date'];
            $article->publisher_name = $validatedData->validated()['publisher_name'];
            $article->article_img = $imageUrl;
            $article->admin_id = $validatedData->validated()['admin_id'];
            $article->category_id = $validatedData->validated()['category_id'];
            $article->save();

            DB::commit();
            return $this->sendResponse('Artikel baru berhasil dibuat.', $article);
            
        } catch (Exception $e) {
            DB::rollback();
            return $this->sendError('Terjadi kesalahan saat membuat konten artikel.', [$e->getMessage()], 500);
        }
    }

    /**
     * Get Detail Article - Admin
     *
     * @param int  $id                                       
     * @return \Illuminate\Http\JsonResponse   
     *     
     */
    public function show($id)
    {
        $article = Article::with('admin_writer')->find($id);
        if (!$article) {
            return $this->sendError('Artikel tidak ditemukan.', [], 404);
        }

        return $this->sendResponse('Detail artikel berhasil ditemukan.', new DetailArticleResource($article));
    }

    /**
     * Update Article - Admin
     *
     * @param  \Illuminate\Http\Request $request
     * @param int  $id                                                                               
     * @return \Illuminate\Http\JsonResponse   
     *     
     */
    public function update(Request $request, $id)
    {
        $article = Article::find($id);
        if (!$article) {
            return $this->sendError('Artikel tidak ditemukan.', [], 404);
        }

        $validatedData = Validator::make($request->all(), [
            'article_title' => 'sometimes|required|string|max:255',
            'content' => 'sometimes|required|string',
            'publication_date' => 'sometimes|required|date',
            'publisher_name' => 'sometimes|required|string|max:255',
            'article_img' => 'nullable|image|mimes:jpeg,png,jpg|max:2048', 
            'admin_id' => 'sometimes|required|exists:admins,id',
            'category_id' => 'sometimes|required|exists:article_categories,id',
        ], [
            'article_title.required' => 'Judul artikel wajib diisi.',
            'content.required' => 'Konten artikel wajib diisi.',
            'publication_date.required' => 'Tanggal publikasi wajib diisi.',
            'publication_date.date' => 'Tanggal publikasi harus berupa tanggal yang valid.',
            'publisher_name.required' => 'Nama penerbit wajib diisi.',
            'article_img.image' => 'Foto artikel harus berupa file gambar.',
            'article_img.mimes' => 'Foto artikel harus berformat jpeg, png, atau jpg.',
            'article_img.max' => 'Foto artikel tidak boleh lebih besar dari 2MB.',
            'admin_id.required' => 'Admin wajib dipilih.',
            'admin_id.exists' => 'Admin yang dipilih tidak valid.',
            'category_id.required' => 'Kategori artikel wajib dipilih.',
            'category_id.exists' => 'Kategori yang dipilih tidak valid.'
        ]);

        if ($validatedData->fails()) {
            return $this->sendError('Validasi gagal', $validatedData->errors(), 422);
        }

        try {
            DB::beginTransaction();

            $dataToUpdate = $validatedData->validated();

            // Proses gambar jika ada
            if ($request->hasFile('article_img')) {
                $imagePath = Storage::disk('public')->put('article_photos', $request->file('article_img'));
                if (!$imagePath) {
                    return $this->sendError('Gagal menyimpan gambar.', [], 500);
                }

                // Hapus gambar lama jika ada
                if ($article->article_img) {
                    Storage::disk('public')->delete($article->article_img);
                }

                $article->article_img = 'storage/' . $imagePath;
            }

            $article->article_title = $dataToUpdate['article_title'] ?? $article->article_title;
            $article->content = $dataToUpdate['content'] ?? $article->content;
            $article->publication_date = $dataToUpdate['publication_date'] ?? $article->publication_date;
            $article->publisher_name = $dataToUpdate['publisher_name'] ?? $article->publisher_name;
            $article->admin_id = $dataToUpdate['admin_id'] ?? $article->admin_id;
            $article->category_id = $dataToUpdate['category_id'] ?? $article->category_id;
            $article->save();
            
            DB::commit();

            return $this->sendResponse('Artikel berhasil diperbarui.', $article);
        } catch (Exception $e) {
            DB::rollback();
            return $this->sendError('Terjadi kesalahan saat memperbarui artikel.', [$e->getMessage()], 500);
        }
    }


    /**
     * Delete Article - Admin
     *
     * @param int  $id                                       
     * @return \Illuminate\Http\JsonResponse   
     *     
     */
    public function destroy($id)
    {
        $article = Article::find($id);
        if (!$article) {
            return $this->sendError('Artikel tidak ditemukan.', [], 404);
        }

        try {
            DB::beginTransaction();

            if ($article->article_img) {
                $relativePath = str_replace('storage/', '', $article->article_img); 
                Storage::disk('public')->delete($relativePath);
            }
            $article->delete();

            DB::commit();
            return $this->sendResponse('Artikel berhasil dihapus.', null);

        } catch (Exception $e) {
            DB::rollback();
            return $this->sendError('Terjadi kesalahan saat menghapus artikel.', [$e->getMessage()], 500);
        }
    }

    public function listArticleCategory()
    {
        $categories = ArticleCategory::select('id', 'name')->get();
        return $this->sendResponse('Daftar kategori artikel berhasil ditemukan.', $categories);
    }

}
