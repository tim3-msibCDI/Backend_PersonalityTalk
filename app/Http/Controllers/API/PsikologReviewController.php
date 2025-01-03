<?php

namespace App\Http\Controllers\API;

use Carbon\Carbon;
use App\Models\Psikolog;
use App\Models\Consultation;
use Illuminate\Http\Request;
use App\Models\PsikologReview;
use App\Models\PsikologSchedule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\API\BaseController;

class PsikologReviewController extends BaseController
{
    /**
     * Retrieve details of the psychologist and consultation schedule before submitting a review.
     *
     * Validates the request to ensure that the provided schedule ID and psychologist ID exist.
     * If validation fails, returns an error response.
     * If validation succeeds, fetches the psychologist details and the selected consultation schedule.
     *
     * @param \Illuminate\Http\Request $request The request containing 'psch_id' and 'psi_id'.
     * @return \Illuminate\Http\JsonResponse A response with psychologist details and consultation schedule.
     */
    public function detailPsikologBeforeReview(Request $request)
    {
        $validatedData = Validator::make($request->all(),[
            'psch_id' => 'required|exists:psikolog_schedules,id', 
        ],[
            'psch_id.required' => 'Jadwal konsultasi harus dipilih.',
            'psch_id.exists' => 'Jadwal konsultasi yang dipilih tidak valid.',
        ]);

        if ($validatedData->fails()) {
            return $this->sendError('Validasi gagal', $validatedData->errors(), 422);
        }

        $selectedSchedule = PsikologSchedule::with(['mainSchedule', 'psikolog'])
            ->where('id', $request->psch_id)
            ->first();            

        return $this->sendResponse(
            'Berhasil mengambil detail psikolog sebelum submit rating', 
            [
                'name' => $selectedSchedule->psikolog->user->name,
                'photo_profile' => $selectedSchedule->psikolog->user->photo_profile,
                'consultation_date' => Carbon::parse($selectedSchedule->date)->translatedFormat('d M Y'),
                'consultation_time' => Carbon::parse($selectedSchedule->mainSchedule->start_hour)->format('H:i') . ' - ' . 
                    Carbon::parse($selectedSchedule->mainSchedule->end_hour)->format('H:i')
            ]
        );
    }

    /**
     * Submit Review for Psikolog
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     * 
     * @bodyParam psi_id int required ID psikolog yang akan di review. Example: 1
     * @bodyParam consul_id int required ID konsultasi yang terkait. Example: 10
     * @bodyParam rating int required Rating yang diberikan. Example: 4
     * @bodyParam review string nullable Review yang diberikan. Example: Sangat puas dengan pelayanan psikolog ini.
     */
    public function submitReview(Request $request)
    {
        $validatedData = Validator::make($request->all(), [
            'psi_id' => 'required|exists:psikolog,id', 
            'consul_id' => 'required|exists:consultations,id', // Validasi konsultasi
            'rating' => 'required|integer|min:1|max:5', // Rating 1-5
            'review' => 'nullable|string',
        ], [
            'psi_id.required' => 'ID psikolog wajib diisi.',
            'psi_id.exists' => 'Psikolog tidak ditemukan.',
            'consul_id.required' => 'ID konsultasi wajib diisi.',
            'consul_id.exists' => 'Konsultasi tidak ditemukan.',
            'rating.required' => 'Rating wajib diisi.',
            'rating.integer' => 'Rating yang dikirim harus berupa angka.',
            'rating.min' => 'Rating minimal adalah bintang 1.',
            'rating.max' => 'Rating maksimal adalah bintang 5.',
        ]);

        if ($validatedData->fails()) {
            return $this->sendError('Validasi gagal', $validatedData->errors(), 422);
        }

        // Pastikan konsultasi terkait psikolog dan user
        $consultation = Consultation::where('id', $request->consul_id)
            ->where('psi_id', $request->psi_id) //pastikan konsultasi milik psikolog
            ->where('user_id', auth()->id()) // Pastikan konsultasi milik user yang login
            ->first();

        if (!$consultation) {
            return $this->sendError('Konsultasi tidak valid untuk user ini.', [], 422);
        }

        // Cek apakah sudah ada review untuk konsultasi ini
        $existingReview = PsikologReview::where('consul_id', $request->consul_id)
            ->where('user_id', auth()->id())
            ->first();

        if ($existingReview) {
            return $this->sendError('Review untuk konsultasi ini sudah ada.', [], 422);
        }

        try {
            // Simpan review
            $review = new PsikologReview();
            $review->user_id = auth()->id();
            $review->psi_id = $request->psi_id;
            $review->consul_id = $request->consul_id;
            $review->rating = $request->rating;
            $review->review = $request->review;
            $review->save();

            return $this->sendResponse('Review berhasil disimpan.', $review);
        } catch (\Exception $e) {
            return $this->sendError('Terjadi kesalahan saat menilai psikolog.', [$e->getMessage()], 500);
        }
    }


    public function detailReview(Request $request){
        $userId = Auth::user()->id;
        $validatedData = Validator::make($request->all(), [
            'consul_id' => 'required|exists:consultations,id',
        ], [
            'consul_id.required' => 'ID konsultasi wajib diisi.',
            'consul_id.exists' => 'Konsultasi tidak ditemukan.',
        ]);

        if ($validatedData->fails()) {
            return $this->sendError('Validasi gagal', $validatedData->errors(), 422);
        }

        $review = PsikologReview::where('consul_id', $request->consul_id)
            ->where('user_id', $userId)
            ->first();

        if (!$review) {
            return $this->sendError('Review tidak ditemukan.', [], 404);
        }
        return $this->sendResponse('Review berhasil diambil.', $review);
    }

    public function listPsikologReview()
    {
        $psikologId = Auth::user()->psikolog->id;
    
        // Ambil daftar review beserta pengguna
        $reviews = PsikologReview::with('user')
            ->where('psi_id', $psikologId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($review) {
                // Ambil kata pertama dari nama user
                $userName = $review->user->name;
                $firstName = explode(' ', trim($userName))[0];

                // Samarkan kata pertama dengan bintang-bintang kecuali huruf pertama
                $hiddenName = substr($firstName, 0, 1) . str_repeat('*', strlen($firstName) - 1);

                return [
                    'rating' => $review->rating,
                    'review' => $review->review,
                    'user_name' => $hiddenName,
                ];
            });
  
        $totalRatings = $reviews->count();
        $averageRating = $totalRatings > 0 ? number_format($reviews->avg('rating'), 1) : 0;
    
        $response = [
            'average_rating' => $averageRating,
            'total_ratings' => $totalRatings,
            'reviews' => $reviews,
        ];
    
        return $this->sendResponse('Daftar review berhasil diambil.', $response);
    }
    


}
