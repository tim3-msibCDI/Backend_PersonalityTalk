<?php

namespace App\Http\Controllers\API;

use App\Models\Admin;
use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\API\BaseController;

class ArticleController extends BaseController
{
    /**
     * Get list article for user
     */
    public function indexUser(Request $request)
    {
        $categoryId = $request->get('category_id', 1);

        $articles = Article::with(['admin_writer', 'article_category'])
            ->where('category_id', $categoryId)
            ->select('id', 'article_img', 'article_title', 'publication_date')
            ->paginate(12);

        return response()->json($articles, 200);
    }

    /**
     * Get detail article for user
     */
    public function showArticleWithRelated($id)
    {
        $article = Article::with(['admin_writer', 'article_category'])->find($id);

        if (!$article) {
            return response()->json(['message' => 'Artikel tidak ditemukan'], 404);
        }

        $relatedArticles = Article::where('category_id', $article->category_id)
            ->where('id', '!=', $id)
            ->select('id', 'article_img', 'article_title', 'publication_date')
            ->limit(5)
            ->get();

        return response()->json([
            'article' => $article,  // Detail artikel
            'related_articles' => $relatedArticles, // Daftar artikel terkait
        ], 200);
    }

    /**
     * Get articles written by admin
     */
    public function getArticlesByAdmin($id)
    {
        $admin = Admin::with('articles')->find($id);

        if (!$admin) {
            return response()->json(['message' => 'Admin tidak ditemukan'], 404);
        }

        // Dapatkan artikel-artikel yang ditulis oleh admin tersebut
        $articles = Article::where('admin_id', $id)->paginate(10);

        return response()->json([
            'admin' => [
                'name' => $admin->name,
            ],
            'articles' => $articles,
        ], 200);
    }

    /**
     * Get list article for user
     */
    public function indexAdmin(Request $request)
    {
        $article = Article::select('id', 'article_title', 'article_img')->get();

        return $this->sendResponse($article, 'Artikel berhasil ditemukan.');
    }

    /**
     * Save new article from admin
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
            return response()->json(['errors' => $validatedData->errors()], 422);
        }

        try {
            DB::beginTransaction();
            
            if ($request->hasFile('article_img')) {
                $imagePath = Storage::disk('public')->put('article_photos', $request->file('article_img'));
    
                if (!$imagePath) {
                    return response()->json([
                        'message' => 'Gagal menyimpan gambar.'
                    ], 500);
                }
            }

            $article = Article::create([
                'article_title' => $validatedData->validated()['article_title'],
                'content' => $validatedData->validated()['content'],
                'publication_date' => $validatedData->validated()['publication_date'],
                'publisher_name' => $validatedData->validated()['publisher_name'],
                'article_img' => $imagePath, 
                'admin_id' => $validatedData->validated()['admin_id'],
                'category_id' => $validatedData->validated()['category_id'],
            ]);

            DB::commit();
    
            return response()->json([
                'message' => 'Artikel berhasil dibuat',
                'data' => $article
            ], 201);
            
        } catch (Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => 'Terjadi kesalahan saat membuat artikel.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Menampilkan detail artikel tertentu.
     */
    public function show($id)
    {
        $article = Article::with('admin_writer')->find($id);

        if (!$article) {
            return response()->json(['message' => 'Artikel tidak ditemukan'], 404);
        }

        return $this->sendResponse($article, 'Artikel berhasil ditemukan.');
    }

    /**
     * Memperbarui artikel tertentu oleh admin
     */ 
    public function update(Request $request, $id)
    {
        $article = Article::find($id);
        // dd($article);

        if (!$article) {
            return response()->json(['message' => 'Artikel tidak ditemukan'], 404);
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
            return response()->json(['errors' => $validatedData->errors()], 422);
        }

        try {
            DB::beginTransaction();
            
            $dataToUpdate = $validatedData->validated();

            // Jika ada file gambar baru, simpan dan hapus gambar lama
            if ($request->hasFile('article_img')) {
                // Simpan gambar baru
                $imagePath = Storage::disk('public')->put('article_photos', $request->file('article_img'));
                if (!$imagePath) {
                    return response()->json(['message' => 'Gagal menyimpan gambar.'], 500);
                }

                // Hapus gambar lama jika ada
                if ($article->article_img) {
                    Storage::disk('public')->delete($article->article_img);
                }

                // Update data gambar pada artikel
                $dataToUpdate['article_img'] = $imagePath;
            }

            // Update artikel 
            $article->update($dataToUpdate);

            DB::commit();

            return response()->json([
                'message' => 'Artikel berhasil diperbarui',
                'data' => $article
            ], 200);
            
        } catch (Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => 'Terjadi kesalahan saat memperbarui artikel.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Menghapus artikel tertentu.
     */
    public function destroy($id)
    {
        $article = Article::find($id);
        if (!$article) {
            return response()->json(['message' => 'Artikel tidak ditemukan'], 404);
        }

        try {
            DB::beginTransaction();

            // Hapus gambar terkait jika ada
            if ($article->article_img) {
                Storage::disk('public')->delete($article->article_img);
            }
            $article->delete();

            DB::commit();

            return response()->json(['message' => 'Artikel berhasil dihapus'], 200);
        } catch (Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => 'Terjadi kesalahan saat menghapus artikel.',
                'error' => $e->getMessage()
            ], 500);
        }
    }


}
