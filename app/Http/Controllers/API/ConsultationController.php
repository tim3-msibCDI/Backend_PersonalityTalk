<?php

namespace App\Http\Controllers\API;

use Carbon\Carbon;
use App\Models\Topic;
use App\Models\Psikolog;
use Illuminate\Http\Request;
use App\Models\PsikologCategory;
use App\Models\PsikologSchedule;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class ConsultationController extends Controller
{
    /**
     * Get all psychologist categories
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPsikologTopics()
    {
        try {
            $categories = Topic::select('id', 'topic_name')->get();

            return response()->json([
                'success' => true,
                'data' => $categories
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data kategori: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get available psychologists and counselors based on topic 
     */
    public function getAvailablePsikolog(Request $request)
    {
        $request->validate([
            'topic_id' => 'required|exists:topics,id', // Topik yang dipilih pengguna
            'category_id' => 'required|in:1,2', // 1 psikolog, 2 konselor
            'date' => 'nullable|date', // tanggal yang dipilih pada slider
        ]);

        $topicId = $request->topic_id;
        $categoryId = $request->category_id;

        // If a date is provided, use it. Otherwise, default to today's date
        $selectedDate = $request->date ? Carbon::parse($request->date)->startOfDay() : Carbon::today()->startOfDay();
        $endDate = $selectedDate->copy()->endOfDay();
        // dd($selectedDate, $endDate);

        $list_psikolog = DB::table('psikolog as p')
            ->join('psikolog_topics as pt', 'p.id', '=', 'pt.psikolog_id')
            ->join('psikolog_schedules as ps', 'p.id', '=', 'ps.psikolog_id')
            ->join('psikolog_categories as pc', 'p.category_id', '=', 'pc.id') 
            ->join('users as u', 'p.user_id', '=', 'u.id') 
            ->whereIn('p.category_id', [$categoryId])
            ->where('pt.topic_id', $topicId) // Filter by the selected topic
            ->whereBetween('ps.date', [$selectedDate, $endDate])
            ->where('ps.is_available', true) // Ensure the professional is available
            ->select(
                'p.id as psikolog_id', 
                'u.name', 
                'u.photo_profile', 
                'p.practice_start_date',
                'pc.category_name',
                DB::raw('COUNT(ps.id) as available_schedule_count') // Hitung jumlah jadwal yang tersedia
            )
            ->groupBy('p.id', 'u.name', 'u.photo_profile', 'p.practice_start_date', 'pc.category_name')
            ->get();

    
        $response = $list_psikolog->map(function($psikolog) {
            // Hitung tahun pengalaman kerja
            $yearsOfExperience = floor(Carbon::parse($psikolog->practice_start_date)->diffInYears(Carbon::now()));

            return [
                'id' => $psikolog->psikolog_id,
                'name' => $psikolog->name,
                'photo_profile' => $psikolog->photo_profile,
                'years_of_experience' => $yearsOfExperience,
                'category_name' => $psikolog->category_name,
                'available_schedule_count' => $psikolog->available_schedule_count, 
            ];
        });

        return response()->json([
            'list_psikolog' => $response,
        ]);
    }

    /**
     * 
     */
    public function getPsikologDetailsAndSchedules(Request $request, $id)
    {
        $request->validate([
            'selected_date' => 'required|date', 
        ]);

        $psikolog = Psikolog::with(['user', 'psikolog_category', 'psikolog_price', 'psikolog_topic.topic'])
            ->where('id', $id)
            ->firstOrFail();

        // dd($psikolog);

        $selectedDate = Carbon::parse($request->selected_date)->format('Y-m-d');

        $availableSchedules = PsikologSchedule::where('psikolog_id', $id)
            ->where('date', $selectedDate)
            ->where('is_available', true)
            ->with('mainSchedule')
            ->get();

        return response()->json([
            'psikolog' => [
                'id' => $psikolog->id,
                'name' => $psikolog->user->name,
                'photo_profile' => $psikolog->user->photo_profile,
                'category_name' => $psikolog->psikolog_category->category_name,
                'years_of_experience' => $psikolog->getYearsOfExperience(),
                'price' => $psikolog->psikolog_price->price,
                'description' => $psikolog->description,
                'sipp' => $psikolog->sipp,
                'topics' => $psikolog->psikolog_topic->map(function($pt) {
                    return $pt->topic->topic_name; 
                }),
            ],
            'available_schedules' => $availableSchedules->map(function($schedule) {
                return [
                    'psch_id' => $schedule->id,
                    'time_slot' => Carbon::parse($schedule->mainSchedule->start_hour)->format('H:i') 
                               . ' - ' . 
                               Carbon::parse($schedule->mainSchedule->end_hour)->format('H:i')
                ];
            })
        ]);
    }


}
