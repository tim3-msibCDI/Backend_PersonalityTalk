<?php

namespace App\Http\Controllers\API;

use App\Models\User;
use App\Models\Mitra;
use App\Models\Article;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class LandingPageController extends BaseController
{

    /**
     * Menampilkan daftar 3 artikel yang direkomendasikan secara acak.
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function recomendationArticle()
    {
        $articles = Article::select('id', 'article_title', 'publication_date', 'article_img')->inRandomOrder()->limit(3)->get();
        return $this->sendResponse('Daftar artikel acak berhasil ditemukan.', $articles);
    }

    /**
     * Menampilkan daftar 4 psikolog yang direkomendasikan secara acak beserta topik dan kategori.
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function recomendationPsikolog()
    {
        $listPsikolog = User::with([
            'psikolog',
            'psikolog.psikolog_topic.topic',
            'psikolog.psikolog_category'
        ])
            ->whereHas('psikolog', function ($query) {
                $query->where('is_active', true);
            })
            ->limit(4)
            ->get()
            ->map(function ($user) {
                $topics = $user->psikolog->psikolog_topic->map(function ($pt) {
                    return $pt->topic->topic_name;
                });

                $formattedTopics = $topics->take(3)->toArray();
                $remainingCount = $topics->count() - 3;

                // Tambahkan "2+" langsung ke formattedTopics jika ada sisa topik
                if ($remainingCount > 0) {
                    $formattedTopics[] = "{$remainingCount}+";
                }

                return [
                    'name' => $user->name,
                    'photo_profile' => $user->photo_profile,
                    'category' => $user->psikolog->psikolog_category->category_name,
                    'topics' => $formattedTopics,
                ];
            });

        return $this->sendResponse('Daftar psikolog berhasil ditemukan.', $listPsikolog);
    }

    /**
     * Menampilkan daftar 10 mitra yang dipilih secara acak.
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function listMitra()
    {
        $mitra = Mitra::select('id', 'name', 'img')->inRandomOrder()->limit(10)->get();
        return $this->sendResponse('Daftar mitra berhasil ditemukan.', $mitra);
    }

}
