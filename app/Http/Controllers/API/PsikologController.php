<?php

namespace App\Http\Controllers\API;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Psikolog;
use App\Models\Consultation;
use Illuminate\Http\Request;
use App\Services\PsikologService;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\ConsultationTransaction;
use Illuminate\Support\Facades\Validator;

class PsikologController extends BaseController
{   
    
    protected $psikologService;

    // PsikologService constructor
    public function __construct(PsikologService $psikologService)
    {
        parent::__construct();
        $this->psikologService = $psikologService;
    }

    /**
     * Psikolog Registration
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse   
     *     
     */
    public function psikologRegister(Request $request)
    {
        // Validasi input
        $validator = Validator::make($request->all(), [
            // Validasi untuk tabel 'users'
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|unique:users,email',
            'password' => 'required|string|min:8',
            'phone_number' => 'required|string|regex:/^[0-9]{10,15}$/',
            'date_birth' => 'required|date',
            'gender' => 'required|in:M,F',
            'photo_profile' => 'required|image|mimes:jpeg,png,jpg|max:2048', 
            'role' => 'required|in:P,K',

            // Validasi untuk tabel 'psikolog'
            'description' => 'required|string|max:255',
            'sipp' => 'required_if:role,P|string|max:255',
            'practice_start_date' => 'required|date',
            'topics' => 'required|array', // Topik keahlian harus dalam bentuk array
            'topics.*' => 'exists:topics,id', // Setiap topik harus ada di tabel 'topics'
        ], [
            'name.required' => 'Nama wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.unique' => 'Email sudah terdaftar.',
            'password.required' => 'Password wajib diisi.',
            'phone_number.required' => 'Nomor telepon wajib diisi.',
            'phone_number.regex' => 'Nomor telepon harus valid dan terdiri dari 10-15 angka.',
            'sipp.required_if' => 'Psikolog wajib mengisi SIPP.',
            'photo_profile.required' => 'Foto profil wajib diunggah.',
            'photo_profile.image' => 'Foto profil harus berupa gambar.',
            'topics.required' => 'Pilih setidaknya satu topik keahlian.',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validasi gagal.', $validator->errors(), 422);
        }

        try {
            // Menggunakan function yang terdapat pada PsikologService
            $psikolog = $this->psikologService->registerPsikolog($request->all());
            return $this->sendResponse('Pendaftaran psikolog berhasil dilakukan', $psikolog);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError('Terjadi kesalahan saat registrasi psikolog.', [$e->getMessage()], 500);
        }
    }

    /**
     * Menampilkan list chat consultation yang dimiliki oleh psikolog yang sedang login dengan paginasi.
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function listChatConsultation(Request $request)
    {
        $user = Auth::user();
        $psikolog = $user->psikolog;

        if (!$psikolog) {
            return $this->sendError('Psikolog tidak ditemukan.', null, 404);
        }

        $consultations = Consultation::with([
            'psikologSchedule.mainSchedule', 
            'user', 
            'psikolog.user',
            'topic',
            'chatSession'
        ])
        ->where('psi_id', $psikolog->id)
        ->whereIn('consul_status', ['completed', 'ongoing', 'scheduled']) 
        ->orderByRaw("
            FIELD(consul_status, 'ongoing', 'scheduled', 'completed'),
            created_at DESC
        ")
        ->paginate(10); // Tambahkan paginasi dengan 10 item per halaman

        // Transformasi data
        $consultations->getCollection()->transform(function ($item) {
            return [
                'consul_id' => $item->id,
                'chat_session_id' => $item->chatSession->id ?? null,
                'status' => $item->consul_status ?? null,
                'client_id' => $item->user_id ?? null,
                'client_name' => $item->user->name ?? null,
                'psikolog_id' => $item->psikolog->user_id ?? null, // ID dari user psikolog bukan id psikolog
                'topic' => $item->topic->topic_name ?? null,
                'date' => Carbon::parse($item->psikologSchedule->date)->format('j M Y') ?? null,
                'start_hour' => Carbon::parse($item->psikologSchedule->mainSchedule->start_hour)->format('H:i') ?? null,
                'end_hour' => Carbon::parse($item->psikologSchedule->mainSchedule->end_hour)->format('H:i') ?? null,
                'status' => $item->consul_status ?? null,
                'keluhan' => $item->patient_complaint ?? null,
            ];
        });

        return $this->sendResponse('List chat consultation', $consultations);
    }


    /**
     * Retrieve the complaint details for a specific consultation.
     *
     * This function fetches the complaint details for a consultation based on
     * the provided consultation ID, ensuring that the consultation is associated
     * with the currently authenticated psychologist.
     *
     * @param int $consulId The ID of the consultation to retrieve the complaint from.
     * @return \Illuminate\Http\JsonResponse The response containing the complaint details
     * or an error message if the consultation is not found.
     */
    public function detailComplaintUser($consulId)
    {
        $user = Auth::user();
        $psikolog = $user->psikolog;

        // Cari konsultasi berdasarkan ID dan psikolog yang terkait
        $consultation = Consultation::where('id', $consulId)
            ->where('psi_id', $psikolog->id)
            ->first(); 
        
        // Periksa jika konsultasi tidak ditemukan
        if (!$consultation) {
            return $this->sendError('Konsultasi tidak ditemukan', [], 404);
        }

        $complaint = $consultation->patient_complaint;
        return $this->sendResponse('Detail Keluhan berhasil diambil', ['keluhan' => $complaint]);
    }


    public function listPsikologTransaction(Request $request)
    {
        $user = Auth::user();
        $psikolog = $user->psikolog;

        if (!$psikolog) {
            return $this->sendError('Psikolog tidak ditemukan.', null, 404);
        }

        $transactions = ConsultationTransaction::with(['consultation.psikolog.user', 'user', 'paymentMethod'])
            ->whereHas('consultation', function ($query) use ($psikolog) {
                $query->where('consul_status', 'completed')
                    ->where('psi_id', $psikolog->id);
            })
            ->orderByRaw('ISNULL(payment_completed_at), payment_completed_at DESC') // Prioritas: yang sudah selesai dulu
            ->orderBy('created_at', 'asc') // Jika waktu pembayaran sama, urutkan berdasarkan waktu dibuat
            ->paginate(10); 

        // Transformasi data
        $transactions->getCollection()->transform(function ($transaction) {
            $psikolog_comission = $transaction->consul_fee * 0.6;

            return [
                'id' => $transaction->id,
                'payment_date' => $transaction->payment_completed_at 
                    ? Carbon::parse($transaction->payment_completed_at)->format('d-m-Y H:i') 
                    : null,
                'client_name' => optional($transaction->user)->name ?? 'Tidak Diketahui',
                'psikolog_comission' => $psikolog_comission,
                'commission_transfer_status' => $transaction->commission_transfer_status,
                'commission_transfer_proof' => $transaction->commission_transfer_proof,
            ];
        });

        return $this->sendResponse('List transaksi psikolog berhasil diambil.', $transactions);
    }

    /**
     * Retrieve the commission transfer proof for a specific transaction.
     *
     * This function fetches the transfer proof of the commission for a given
     * transaction ID. It ensures the transaction exists and returns the proof
     * of commission transfer if available.
     *
     * @param int $transactionId The ID of the transaction to retrieve the commission proof from.
     * @return \Illuminate\Http\JsonResponse The response containing the commission proof details
     * or an error message if the transaction is not found.
     */
    public function getPsikologCommissionProof($transactionId)
    {
        $user = Auth::user();
        $psikolog = $user->psikolog;
    
        if (!$psikolog) {
            return $this->sendError('Psikolog tidak ditemukan.', null, 404);
        }
    
        // Cari transaksi milik psikolog terkait
        $transaction = ConsultationTransaction::where('id', $transactionId)
            ->whereHas('consultation', function ($query) use ($psikolog) {
                $query->where('consul_status', 'completed')
                      ->where('psi_id', $psikolog->id);
            })
            ->first();
    
        if (!$transaction) {
            return $this->sendError('Transaksi tidak ditemukan atau tidak dimiliki oleh Anda.', null, 404);
        }

        $data = [
            'transfer_proof' => $transaction->commission_transfer_proof
        ];
    
        return $this->sendResponse('Detail bukti transfer komisi psikolog berhasil diambil.', $data);
    }
    
    /**
     * Approve the commission for a specific transaction.
     *
     * This function allows a Psikolog to approve the commission of a given
     * transaction ID. It ensures the transaction exists and that the user
     * is the owner of the transaction. If the transaction is found, it
     * updates the status of the transaction to 'Diterima' and returns a
     * success message. If the transaction is not found, it returns an error
     * message.
     *
     * @param int $transactionId The ID of the transaction to approve the commission for.
     * @return \Illuminate\Http\JsonResponse The response containing the result of the approval
     * or an error message if the transaction is not found.
     */
    public function approveCommission($transactionId){
        $user = Auth::user();
        $psikolog = $user->psikolog;
    
        if (!$psikolog) {
            return $this->sendError('Psikolog tidak ditemukan.', null, 404);
        }
    
        // Cari transaksi milik psikolog terkait
        $transaction = ConsultationTransaction::where('id', $transactionId)
            ->whereHas('consultation', function ($query) use ($psikolog) {
                $query->where('consul_status', 'completed')
                      ->where('psi_id', $psikolog->id);
            })
            ->first();

        $transaction->commission_transfer_status = 'Diterima';
        $transaction->save();
        return $this->sendResponse('Komisi berhasil diterima.', null);
    }

    public function rejectCommission($transactionId){
        $user = Auth::user();
        $psikolog = $user->psikolog;
    
        if (!$psikolog) {
            return $this->sendError('Psikolog tidak ditemukan.', null, 404);
        }
    
        // Cari transaksi milik psikolog terkait
        $transaction = ConsultationTransaction::where('id', $transactionId)
            ->whereHas('consultation', function ($query) use ($psikolog) {
                $query->where('consul_status', 'completed')
                      ->where('psi_id', $psikolog->id);
            })
            ->first();

        $transaction->commission_transfer_status = 'Menunggu Konfirmasi';
        $transaction->save();
        return $this->sendResponse('Komisi tidak diterima.', null);
    }
}
