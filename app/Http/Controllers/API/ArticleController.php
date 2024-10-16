<?php

namespace App\Http\Controllers\API;

use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;

class ArticleController extends Controller
{
    /**
     * Menampilkan daftar semua artikel.
     */
    public function index()
    {
        $articles = Article::with(['admin', 'category'])->paginate(10);
        return response()->json($articles, 200);
    }

    /**
     * Menyimpan artikel baru.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'article_title' => 'required|string|max:255',
            'content' => 'required|string',
            'publication_date' => 'required|date',
            'publisher_name' => 'required|string|max:255',
            'article_img' => 'required|image|mimes:jpeg,png,jpg|max:2048', 
            'admin_id' => 'required|exists:admins,id',
            'category_id' => 'required|exists:article_categories,id',
        ], [
            'article_title.required' => 'Judul artikel wajib diisi',
            'content.required' => 'Konten artikel wajib diisi',
            'publication_date.required' => 'Tanggal publikasi wajib diisi',
            'publisher_name.required' => 'Nama penerbit wajib diisi',
            'admin_id.required' => 'Admin wajib dipilih',
            'category_id.required' => 'Kategori artikel wajib dipilih',
            'article_img.required' => 'Foto profil wajib diunggah.',
            'article_img.image' => 'Foto profil harus berupa gambar.'
        ]);

        if (isset($data['article_img'])) {
           $imagePath = Storage::disk('public')->put('article_photos', $data['article_img']);
        }

        $article = Article::create([
            'article_title' => $validatedData['article_title'],
            'content' => $validatedData['content'],
            'publication_date' => $validatedData['publication_date'],
            'publisher_name' => $validatedData['publisher_name'],
            'article_img' => $imagePath, 
            'admin_id' => $validatedData['admin_id'],
            'category_id' => $validatedData['category_id'],
        ]);

        return response()->json([
            'message' => 'Artikel berhasil dibuat',
            'data' => $article
        ], 201);
    }

    /**
     * Menampilkan detail artikel tertentu.
     */
    public function show($id)
    {
        $article = Article::with(['article_category', 'admin_writer'])->find($id);

        if (!$article) {
            return response()->json(['message' => 'Artikel tidak ditemukan'], 404);
        }

        return response()->json($article, 200);
    }

    /**
     * Memperbarui artikel tertentu.
     */
    public function update(Request $request, $id)
    {
        $article = Article::find($id);

        if (!$article) {
            return response()->json(['message' => 'Artikel tidak ditemukan'], 404);
        }

        // Validasi data input
        $validatedData = $request->validate([
            'article_title' => 'sometimes|required|string|max:255',
            'content' => 'sometimes|required|string',
            'publication_date' => 'sometimes|required|date',
            'publisher_name' => 'sometimes|required|string|max:255',
            'admin_id' => 'sometimes|required|exists:admins,id',
            'category_id' => 'sometimes|required|exists:article_categories,id',
        ], [
            'article_title.required' => 'Judul artikel wajib diisi',
            'content.required' => 'Konten artikel wajib diisi',
            'publication_date.required' => 'Tanggal publikasi wajib diisi',
            'publisher_name.required' => 'Nama penerbit wajib diisi',
            'admin_id.required' => 'Admin wajib dipilih',
            'category_id.required' => 'Kategori artikel wajib dipilih',
        ]);

        // Memperbarui data artikel
        $article->update($validatedData);

        return response()->json([
            'message' => 'Artikel berhasil diperbarui',
            'data' => $article
        ], 200);
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

        $article->delete();

        return response()->json(['message' => 'Artikel berhasil dihapus'], 200);
    }
}
