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
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\API\BaseController;

class ConsultationController extends BaseController
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
            return $this->sendResponse('Berhasil mengambil data topik psikolog.', $categories);
        } catch (\Exception $e) {
            return $this->sendError('Gagal mengambil data topik psikolog', [$e->getMessage()], 500);
        }
    }

    /**
     * Get available psychologists and counselors based one category and date request
     */
    public function getAvailablePsikologV1(Request $request)
    {
        $validatedData = Validator::make($request->all(), [
            'topic_id' => 'required|exists:topics,id', // Topik yang dipilih pengguna
            'category_id' => 'required|in:1,2', // 1 psikolog, 2 konselor
            'date' => 'nullable|date', // tanggal yang dipilih pada slider
        ], [
            'topic_id.required' => 'Topik harus dipilih.',
            'topic_id.exists' => 'Topik yang dipilih tidak valid.',
            'category_id.required' => 'Kategori harus dipilih.',
            'category_id.in' => 'Kategori yang dipilih tidak valid. Pilih antara Psikolog atau Konselor.',
            'date.date' => 'Format tanggal tidak valid.',
        ]);

        if ($validatedData->fails()) {
            return $this->sendError('Validasi gagal', $validatedData->errors(), 422);
        }

        $topicId = $request->topic_id;
        $categoryId = $request->category_id;

        // If a date is provided, use it. Otherwise, default to today's date
        $startDate = $request->date ? Carbon::parse($request->date)->startOfDay() : Carbon::today()->startOfDay();
        $endDate = $startDate->copy()->endOfDay();
        // dd($selectedDate, $endDate);

        $list_psikolog = DB::table('psikolog as p')
            ->join('psikolog_topics as pt', 'p.id', '=', 'pt.psikolog_id')
            ->join('psikolog_schedules as ps', 'p.id', '=', 'ps.psikolog_id')
            ->join('psikolog_categories as pc', 'p.category_id', '=', 'pc.id') 
            ->join('users as u', 'p.user_id', '=', 'u.id') 
            ->whereIn('p.category_id', [$categoryId])
            ->where('pt.topic_id', $topicId) // Filter by the selected topic
            ->whereBetween('ps.date', [$startDate, $endDate])
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

        return $this->sendResponse('Berhasil mengambil jadwal psikolog yang tersedia.', $response);
    }

    /**
     * Get psikolog detail and availabe schedule
     */
    public function getPsikologDetailsAndSchedulesV1(Request $request, $id)
    {
        $validatedData = Validator::make($request->all(),[
            'selected_date' => 'required|date', 
        ], [
            'selected_date.date' => 'Format tanggal tidak valid.',
        ]);
        if ($validatedData->fails()) {
            return $this->sendError('Validasi gagal', $validatedData->errors(), 422);
        }

        $psikolog = Psikolog::with(['user', 'psikolog_category', 'psikolog_price', 'psikolog_topic.topic'])
            ->where('id', $id)
            ->firstOrFail();

        $selectedDate = Carbon::parse($request->selected_date)->format('Y-m-d');
        $availableSchedules = PsikologSchedule::where('psikolog_id', $id)
            ->where('date', $selectedDate)
            ->where('is_available', true)
            ->with('mainSchedule')
            ->get();

        return $this->sendResponse(
            'Berhasil mengambil detail dan jadwal Psikolog', 
            [
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
            ]
        );
    }

    /** V2
     * Get available psychologists and counselors full one week
     */
    public function getAvailablePsikologV2(Request $request)
    {
        $validatedData = Validator::make($request->all(),[
            'topic_id' => 'required|exists:topics,id', // Topik yang dipilih pengguna
        ],[
            'topic_id.required' => 'Topik harus dipilih.',
            'topic_id.exists' => 'Topik yang dipilih tidak valid.',
        ]);
        if ($validatedData->fails()) {
            return $this->sendError('Validasi gagal', $validatedData->errors(), 422);
        }

        $topicId = $request->topic_id;

        // Default ke hari ini jika tidak ada tanggal yang dikirim
        $startDate = Carbon::today()->startOfDay();
        $endDate = $startDate->copy()->addDays(6)->endOfDay(); // Satu minggu ke depan dari hari ini
        // dd($selectedDate, $endDate);

        $list_psikolog = DB::table('psikolog as p')
            ->join('psikolog_topics as pt', 'p.id', '=', 'pt.psikolog_id')
            ->join('psikolog_schedules as ps', 'p.id', '=', 'ps.psikolog_id')
            ->join('psikolog_categories as pc', 'p.category_id', '=', 'pc.id') 
            ->join('users as u', 'p.user_id', '=', 'u.id') 
            ->where('pt.topic_id', $topicId) // Filter by the selected topic
            ->whereBetween('ps.date', [$startDate, $endDate]) // Jadwal untuk satu minggu ke depan
            ->where('ps.is_available', true) // Hanya psikolog/konselor yang tersedia
            ->select(
                'p.id as psikolog_id', 
                'u.name', 
                'u.photo_profile', 
                'p.practice_start_date',
                'pc.category_name',
                'ps.date',
                DB::raw('COUNT(ps.id) as available_schedule_count') // Hitung jumlah jadwal yang tersedia
            )
            ->groupBy('p.id', 'u.name', 'u.photo_profile', 'p.practice_start_date', 'pc.category_name', 'ps.date')
            ->orderBy('ps.date', 'ASC') // Urutkan berdasarkan tanggal ascending
            ->get();

        // Grupkan hasil berdasarkan tanggal dan kategori
        $startDate = Carbon::today();
        $response = [];

        // Inisialisasi array dengan setiap tanggal dalam satu minggu ke depan
        for ($day = 0; $day <= 6; $day++) {
            $date = $startDate->copy()->addDays($day)->translatedFormat('l j M');
            $response[$date] = [
                'Psikolog' => [],
                'Konselor' => [],
            ];
        }

        foreach ($list_psikolog as $psikolog) {
            // Hitung tahun pengalaman kerja
            $yearsOfExperience = floor(Carbon::parse($psikolog->practice_start_date)->diffInYears(Carbon::now()));
            $date = Carbon::parse($psikolog->date)->translatedFormat('l j M');

            // Kelompokkan berdasarkan kategori (Psikolog atau Konselor)
            if ($psikolog->category_name === 'Psikolog') {
                $response[$date]['Psikolog'][] = [
                    'id' => $psikolog->psikolog_id,
                    'name' => $psikolog->name,
                    'photo_profile' => $psikolog->photo_profile,
                    'years_of_experience' => $yearsOfExperience,
                    'available_schedule_count' => $psikolog->available_schedule_count, 
                ];
            } elseif ($psikolog->category_name === 'Konselor') {
                $response[$date]['Konselor'][] = [
                    'id' => $psikolog->psikolog_id,
                    'name' => $psikolog->name,
                    'photo_profile' => $psikolog->photo_profile,
                    'years_of_experience' => $yearsOfExperience,
                    'available_schedule_count' => $psikolog->available_schedule_count, 
                ];
            }
        }

        return $this->sendResponse('Berhasil mengambil jadwal psikolog dan konselor yang tersedia.', $response);
    }

    /** V2
     * Get psikolog detail and availabe schedule
     */
    public function getPsikologDetailsAndSchedulesV2($id)
    {
        $psikolog = Psikolog::with(['user', 'psikolog_category', 'psikolog_price', 'psikolog_topic.topic'])
            ->where('id', $id)
            ->firstOrFail();

        $startDate = Carbon::today()->startOfDay();
        $endDate = $startDate->copy()->addDays(6)->endOfDay(); // Satu minggu ke depan dari hari ini

        $availableSchedules = PsikologSchedule::where('psikolog_id', $id)
            ->where('is_available', true)
            ->whereBetween('date', [$startDate, $endDate]) // Jadwal untuk satu minggu ke depan
            ->with('mainSchedule')
            ->get()
            ->groupBy(function ($schedule) {
                return Carbon::parse($schedule->date)->translatedFormat('l j M');;
            });

        // Struktur response untuk setiap hari selama 1 minggu
        $weeklySchedule = [];
        for ($day = 0; $day <= 6; $day++) {
            $date = $startDate->copy()->addDays($day)->translatedFormat('l j M');
            $dailySchedules = $availableSchedules->get($date, collect())->map(function($schedule) {
                return [
                    'psch_id' => $schedule->id,
                    'time_slot' => Carbon::parse($schedule->mainSchedule->start_hour)->format('H:i') 
                            . ' - ' . 
                            Carbon::parse($schedule->mainSchedule->end_hour)->format('H:i')
                ];
            });

            $weeklySchedule[] = [
                'date' => $date,
                'schedules' => $dailySchedules,
            ];
        }

        return $this->sendResponse(
            'Berhasil mengambil detail dan jadwal Psikolog', 
            [
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
                'weekly_schedule' => $weeklySchedule
            ]
        );
    }

    // Get data 
    public function getPreviewConsultation(Request $request){

        $validatedData = Validator::make($request->all(),[
            'psch_id' => 'required|exists:psikolog_schedules,id', 
            'psi_id' => 'required|exists:psikolog,id', 
            'topic_id' => 'required|exists:topics,id', 
        ],[
            'psch_id.required' => 'Jadwal konsultasi harus dipilih.',
            'psch_id.exists' => 'Jadwal konsultasi yang dipilih tidak valid.',
            'psi_id.required' => 'Psikolog harus dipilih.',
            'psi_id.exists' => 'Psikolog yang dipilih tidak valid.',
            'topic_id.required' => 'Topik konsultasi harus dipilih.',
            'topic_id.exists' => 'Topik konsultasi yang dipilih tidak valid.',
        ]);

        if ($validatedData->fails()) {
            return $this->sendError('Validasi gagal', $validatedData->errors(), 422);
        }

        $psikolog = Psikolog::with(['user', 'psikolog_category', 'psikolog_price', 'psikolog_topic.topic'])
            ->where('id', $request->psi_id)
            ->firstOrFail();
        $selectedSchedule = PsikologSchedule::with('mainSchedule')
            ->where('id', $request->psch_id)
            ->first();
        $selectedTopic = Topic::select('id', 'topic_name')->where('id', $request->topic_id)->first();
        // dd($selectedTopic);
            

        return $this->sendResponse(
            'Berhasil mengambil preview detail konsultasi', 
            [
                'name' => $psikolog->user->name,
                'photo_profile' => $psikolog->user->photo_profile,
                'category_name' => $psikolog->psikolog_category->category_name,
                'years_of_experience' => $psikolog->getYearsOfExperience(),
                'price' => $psikolog->psikolog_price->price,
                'sipp' => $psikolog->sipp,
                'topic' => $selectedTopic->topic_name,
                'consultation_date' => Carbon::parse($selectedSchedule->date)->translatedFormat('l, j F'),
                'consultation_time' => Carbon::parse($selectedSchedule->mainSchedule->start_hour)->format('H:i') . ' - ' . 
                    Carbon::parse($selectedSchedule->mainSchedule->end_hour)->format('H:i')

            ]
        );

    }

    // Buat konsultasi dulu
    public function createConsultationAndTransaction(Request $request){
        
    }   
}
