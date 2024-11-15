<?php

namespace App\Http\Controllers\API;

use Carbon\Carbon;
use App\Models\Psikolog;
use Illuminate\Http\Request;
use App\Models\PsikologReview;
use App\Models\PsikologSchedule;
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
            'psi_id' => 'required|exists:psikolog,id', 
        ],[
            'psch_id.required' => 'Jadwal konsultasi harus dipilih.',
            'psch_id.exists' => 'Jadwal konsultasi yang dipilih tidak valid.',
            'psi_id.required' => 'Psikolog harus dipilih.',
            'psi_id.exists' => 'Psikolog yang dipilih tidak valid.',
        ]);

        if ($validatedData->fails()) {
            return $this->sendError('Validasi gagal', $validatedData->errors(), 422);
        }

        $psikolog = Psikolog::with('user')
            ->where('id', $request->psi_id)
            ->firstOrFail();

        $selectedSchedule = PsikologSchedule::with('mainSchedule')
            ->where('id', $request->psch_id)
            ->first();            

        return $this->sendResponse(
            'Berhasil mengambil detail psikolog sebelum submit rating', 
            [
                'name' => $psikolog->user->name,
                'photo_profile' => $psikolog->user->photo_profile,
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
     * @bodyParam rating int required Rating yang diberikan. Example: 4
     * @bodyParam review string nullable Review yang diberikan. Example: Sangat puas dengan pelayanan psikolog ini.
     */
    public function submitReview(Request $request)
    {
        $validatedData = Validator::make($request->all(), [
            'psi_id' => 'required|exists:psikolog,id', 
            'rating' => 'required|integer|min:1|max:5', // Rating 1-5
            'review' => 'nullable|string',
        ], [
            'psi_id.required' => 'ID psikolog wajib diisi.',
            'psi_id.exists' => 'Psikolog tidak ditemukan.',
            'rating.required' => 'Rating wajib diisi.',
            'rating.integer' => 'Rating yang dikirim harus berupa angka.',
            'rating.min' => 'Rating minimal adalah bintang 1.',
            'rating.max' => 'Rating maksimal adalah bintang 5.',
        ]);

        if ($validatedData->fails()) {
            return $this->sendError('Validasi gagal', $validatedData->errors(), 422);
        } 

        $psikolog = Psikolog::find($request->psi_id); 
        if (!$psikolog) {
            return $this->sendError('Psikolog tidak ditemukan.', [], 404);
        }

        $review = PsikologReview::create([
            'user_id' => auth()->id(),
            'psi_id' => $request->psi_id,
            'rating' => $request->rating,
            'review' => $request->review,
        ]);
        return $this->sendResponse('Review berhasil disimpan.', $review);
    }      

}
