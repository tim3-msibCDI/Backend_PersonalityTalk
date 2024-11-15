<?php

namespace App\Http\Controllers\API;

use Carbon\Carbon;
use App\Models\Topic;
use App\Models\Voucher;
use App\Models\Psikolog;
use Illuminate\Support\Str;
use App\Models\Consultation;
use Illuminate\Http\Request;
use App\Models\PaymentMethod;
use App\Models\PsikologCategory;
use App\Models\PsikologSchedule;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\ConsultationTransaction;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\API\BaseController;

class ConsultationController extends BaseController
{
    /**
     * Get Psikolog Topic
     *                                                                             
     * @return \Illuminate\Http\JsonResponse   
     *     
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

    /** v1
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

    /** v1
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

    /**
     * Get Available Psikolog and Konselor 
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse   
     *     
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
            $selectedTopicId = $request->topic_id;

            // Ambil semua topik untuk psikolog saat ini
            $topics = DB::table('psikolog_topics as pt')
                ->join('topics as t', 'pt.topic_id', '=', 't.id')
                ->where('pt.psikolog_id', $psikolog->psikolog_id)
                ->pluck('t.topic_name', 't.id') // Mengambil nama topik dengan ID
                ->toArray();

            // Mengambil topik sesuai dengan topic_id yang dipilih
            $selectedTopic = $topics[$selectedTopicId] ?? null;

            // Menghapus topik yang sudah terpilih agar tidak terpilih secara random lagi
            if ($selectedTopic) {
                unset($topics[$selectedTopicId]);
            }

            // Ambil 1 topik random dari sisa topik
            $randomTopics = [];
            if (count($topics) > 0) {
                $randomTopicName = array_rand($topics); // Mengambil ID topik secara random
                $randomTopics[] = $topics[$randomTopicName];
                unset($topics[$randomTopicName]); // Hapus topik random yang sudah terambil
            }

            // Gabungkan topik yang dipilih dengan topik random
            $finalTopics = [];
            if ($selectedTopic) {
                $finalTopics[] = $selectedTopic;
            }
            if (!empty($randomTopics)) {
                $finalTopics[] = $randomTopics[0];
            }

            // Tambahkan sisa topik sebagai "2+", "3+", dll
            $remainingCount = count($topics);
            if ($remainingCount > 0) {
                $finalTopics[] = "{$remainingCount}+";
            }

            // Kelompokkan berdasarkan kategori (Psikolog atau Konselor)
            if ($psikolog->category_name === 'Psikolog') {
                $response[$date]['Psikolog'][] = [
                    'id' => $psikolog->psikolog_id,
                    'name' => $psikolog->name,
                    'photo_profile' => $psikolog->photo_profile,
                    'years_of_experience' => $yearsOfExperience,
                    'available_schedule_count' => $psikolog->available_schedule_count,
                    'category' => 'Psikolog',
                    'topics' => $finalTopics, 
                ];
            } elseif ($psikolog->category_name === 'Konselor') {
                $response[$date]['Konselor'][] = [
                    'id' => $psikolog->psikolog_id,
                    'name' => $psikolog->name,
                    'photo_profile' => $psikolog->photo_profile,
                    'years_of_experience' => $yearsOfExperience,
                    'available_schedule_count' => $psikolog->available_schedule_count,
                    'category' => 'Konselor',
                    'topics' => $finalTopics, 
                ];
            }
        }

        return $this->sendResponse('Berhasil mengambil jadwal psikolog dan konselor yang tersedia.', $response);
    }

    /**
     * Get Psikolog Detail and Available Schedule
     *
     * @param int  $id                                                                              
     * @return \Illuminate\Http\JsonResponse   
     *     
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

    /**
     * Get Preview Consul Before Payment
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse   
     *     
     */
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

    /**
     * Get Payment Method List
     * 
     * @return \Illuminate\Http\JsonResponse   
     *   
     */
    public function listUserPaymentMethod()
    {
        $payments = PaymentMethod::select('id', 'logo','no_rek', 'name')->get();
        return $this->sendResponse('List metode pembayaran berhasil diambil.', $payments);
    }

    /**
     * Create Consultation and Transaction
     *
     * @param  \Illuminate\Http\Request $request
     * @param int  $id                                                                              
     * @return \Illuminate\Http\JsonResponse   
     *     
     */    
    public function createConsultationAndTransaction(Request $request)
    {
        // Validasi input
        $validatedData = Validator::make($request->all(), [
            'psch_id' => 'required|exists:psikolog_schedules,id',
            'psi_id' => 'required|exists:psikolog,id',
            'topic_id' => 'required|exists:topics,id',
            'voucher_code' => 'nullable|string|exists:vouchers,code', 
            'payment_method_id' => 'required|exists:payment_methods,id',
        ], [
            'psch_id.required' => 'Jadwal konsultasi harus dipilih.',
            'psch_id.exists' => 'Jadwal konsultasi tidak valid.',
            'psi_id.required' => 'Psikolog harus dipilih.',
            'psi_id.exists' => 'Psikolog tidak valid.',
            'topic_id.required' => 'Topik konsultasi harus dipilih.',
            'topic_id.exists' => 'Topik konsultasi tidak valid.',
            'voucher_code.exists' => 'Kode voucher tidak valid.',
            'payment_method_id.required' => 'Metode pembayaran harus dipilih.',
            'payment_method_id.exists' => 'Metode pembayaran tidak valid.',
        ]);

        if ($validatedData->fails()) {
            return $this->sendError('Validasi gagal', $validatedData->errors(), 422);
        }

        try {
            DB::beginTransaction();

            // Validasi jadwal psikolog (psch_id) apakah tersedia
            $psikologSchedule = PsikologSchedule::where('id', $request->psch_id)->first();
            if (!$psikologSchedule || !$psikologSchedule->is_available) {
                return $this->sendError('Jadwal konsultasi sudah tidak tersedia atau tidak valid', [], 422);
            }

            // Ambil data psikolog
            $psikolog = Psikolog::with('psikolog_price')->findOrFail($request->psi_id);
            $consultationFee = $psikolog->psikolog_price->price;

            // Menghitung diskon voucher
            $discount = 0;
            if ($request->voucher_code) {
                $voucher = Voucher::where('code', $request->voucher_code)->first();
                // Validasi status dan tanggal berlaku voucher
                if (!$voucher->is_active || Carbon::now()->lt($voucher->valid_from) || Carbon::now()->gt($voucher->valid_to)) {
                    return $this->sendError('Voucher tidak valid atau sudah kadaluwarsa', [], 422);
                }
                // Cek kuota penggunaan voucher
                if ($voucher->quota && $voucher->used >= $voucher->quota) {
                    return $this->sendError('Voucher sudah mencapai batas penggunaan', [], 422);
                }
                // Cek minimum transaksi
                if ($consultationFee < $voucher->min_transaction_amount) {
                    return $this->sendError('Jumlah transaksi belum memenuhi minimum transaksi voucher', [], 422);
                }
                // Hitung diskon
                $discount = min($voucher->discount_value, $consultationFee);
            }
            // Hitung total biaya setelah diskon
            $finalAmount = $consultationFee - $discount;

            $consultation = Consultation::create([
                'user_id' => auth()->id(),
                'psi_id' => $request->psi_id,
                'psch_id' => $request->psch_id,
                'topic_id' => $request->topic_id,
                'patient_complaint' => $request->patient_complaint ?? '',
                'consul_status' => 'pending',
            ]);

            // Mengupdate is_available pada psikolog_schedule menjadi false
            $psikologSchedule->is_available = false;
            $psikologSchedule->save();

            // Kode unik transaksi
            $paymentNumber = 'CS' . '-' . now()->format('ymd') . Str::upper(Str::random(4)) . $consultation->id;

            $transaction = ConsultationTransaction::create([
                'payment_number' => $paymentNumber,
                'user_id' => auth()->id(),
                'consultation_id' => $consultation->id,
                'voucher_id' => $voucher->id ?? null,
                'payment_method_id' => $request->payment_method_id,
                'consul_fee' => $consultationFee,
                'discount_amount' => $discount,
                'status' => 'pending',
            ]);

            // Update penggunaan voucher jika digunakan
            if (isset($voucher)) {
                $voucher->increment('used');
            }

            DB::commit();
            return $this->sendResponse('Konsultasi dan transaksi berhasil dibuat.', [
                'consultation' => $consultation,
                'transaction' => $transaction,
                'final_amount' => $finalAmount,
            ]);

        } catch (Exception $e) {
            DB::rollback();
            return $this->sendError('Terjadi kesalahan saat membuat konsultasi dan transaksi.', [$e->getMessage()], 500);
        }
    }

    /**
     * Upload submit complaint
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse 
     *   
     */
    public function submitComplaint(Request $request)
    {    
        $validatedData = Validator::make($request->all(), [
            'consul_transaction_id' => 'required',
            'patient_complaint' => 'required|string',
        ], 
        [   
            'consul_transaction_id.required' => 'ID transaksi wajib diisi.',
            'patient_complaint.required' => 'Keluhan wajib diisi.',
        ]);

        if ($validatedData->fails()) {
            return $this->sendError('Validasi gagal', $validatedData->errors(), 422);
        }

        $consultation = Consultation::find($request->consul_transaction_id);
        if (!$consultation) {
            return $this->sendError('Konsultasi tidak ditemukan.', [], 404);
        }
        $consultation->patient_complaint = $request->patient_complaint;
        $consultation->save();   
        return $this->sendResponse('Keluhan berhasil dikirim.', $consultation);
    }

    /**
     * Upload payment proof photo
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse   
     */
    public function uploadPaymentProof(Request $request)
    {
        // Validasi input
        $validatedData = Validator::make($request->all(), [
            'consul_transaction_id' => 'required|exists:consul_transactions,id',
            'payment_proof' => 'required|file|mimes:jpeg,png,jpg,pdf|max:2048',
        ], [
            'consul_transaction_id.required' => 'ID transaksi wajib diisi.',
            'consul_transaction_id.exists' => 'ID transaksi tidak valid.',
            'payment_proof.required' => 'Bukti pembayaran wajib diunggah.',
            'payment_proof.mimes' => 'Bukti pembayaran harus berupa file gambar (jpeg, png, jpg) atau PDF.',
            'payment_proof.max' => 'Ukuran file maksimal adalah 2MB.',
        ]);

        if ($validatedData->fails()) {
            return $this->sendError('Validasi gagal', $validatedData->errors(), 422);
        }

        try {
            DB::beginTransaction();

            // Ambil transaksi berdasarkan ID
            $transaction = ConsultationTransaction::find($request->consul_transaction_id);
            if (!$transaction) {
                return $this->sendError('Transaksi tidak ditemukan.', [], 404);
            }

            // Cek status transaksi (hanya transaksi dengan status 'pending' yang dapat mengunggah bukti pembayaran)
            if ($transaction->status !== 'pending') {
                return $this->sendError('Bukti pembayaran hanya bisa diunggah untuk transaksi dengan status pending.', [], 422);
            }

            // Upload dan update bukti pembayaraan
            if ($request->hasFile('payment_proof')) {
                $paymentProofPath = Storage::disk('public')->put('payment_proofs', $request->file('payment_proof'));
    
                if (!$paymentProofPath) {
                    return $this->sendError('Gagal mengunggah bukti pembayaran.', [], 500);
                }
            }
            $paymentProofUrl = 'storage/' . $paymentProofPath; 
            $transaction->payment_proof = $paymentProofUrl; 
            $transaction->payment_completed_at = now();
            $transaction->save();

            DB::commit();
            return $this->sendResponse('Bukti pembayaran berhasil diunggah.', [
                'transaction_id' => $transaction->id,
                'payment_proof_url' => asset($paymentProofUrl),
            ]);

        } catch (Exception $e) {
            DB::rollback();
            return $this->sendError('Terjadi kesalahan saat mengunggah bukti pembayaran.', [$e->getMessage()], 500);
        }
    }

   
}
