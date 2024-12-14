<?php

namespace App\Http\Controllers\API;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\API\BaseController;
use Illuminate\Http\Request;
use App\Http\Resources\CreateConsultationResource;
use App\Models\ConsultationTransaction;


class ActivityHistoryController extends BaseController
{

    /**
     * List consultation history for current user
     * 
     * Mengembalikan riwayat konsultasi pengguna yang sedang login
     * 
     * @return \Illuminate\Http\Response
     */
    public function listConsultationHistory()
    {
        $user = Auth::user();

        $listConsultation = DB::table('consultations')
            ->join('users as patients', 'consultations.user_id', '=', 'patients.id')
            ->join('psikolog', 'consultations.psi_id', '=', 'psikolog.id')
            ->join('users as psychologists', 'psychologists.id', '=', 'psikolog.user_id') // Ambil nama psikolog
            ->join('psikolog_schedules', 'consultations.psch_id', '=', 'psikolog_schedules.id')
            ->join('main_schedules', 'psikolog_schedules.msch_id', '=', 'main_schedules.id')
            ->join('chat_sessions', 'consultations.id', '=', 'chat_sessions.consultation_id')
            ->where('patients.id', $user->id) // Filter berdasarkan pengguna yang sedang login
            ->whereIn('consultations.consul_status', [ 'scheduled', 'completed', 'ongoing']) // Hanya konsultasi yang telah selesai
            ->select(
                'consultations.id as consultation_id',
                'psychologists.id as psi_id',
                'psychologists.name as psikolog_name',
                'psychologists.photo_profile as psikolog_profile',
                'patients.id as client_id',
                'consultations.consul_status',
                'psikolog_schedules.date as schedule_date',
                'main_schedules.day',
                'main_schedules.start_hour',
                'main_schedules.end_hour',
                'chat_sessions.id as chat_id'
            )
            ->orderByDesc('consultations.created_at') // Urutkan berdasarkan tanggal selesai terbaru
            ->get();

        $formattedConsultation = $listConsultation->map(function ($item) {
            return [
                'consultation_id' => $item->consultation_id,
                'chat_session_id' => $item->chat_id,
                'psikolog_name' => $item->psikolog_name,
                'psikolog_id' => $item->psi_id,
                'client_id' => $item->client_id,
                'psikolog_profile'=> $item->psikolog_profile,
                'status' => $item->consul_status,
                'date' => \Carbon\Carbon::parse($item->schedule_date)->format('d M Y'),
                'time' => \Carbon\Carbon::parse($item->start_hour)->format('H:i') . ' - ' . \Carbon\Carbon::parse($item->end_hour)->format('H:i'),
                
            ];
        });

        return $this->sendResponse('Berhasil mengambil riwayat konsultasi pengguna', $formattedConsultation);
    }


    /**
     * List consultation transaction history for current user
     * 
     * Retrieves the transaction history related to consultations for the
     * currently authenticated user. It includes details about the
     * transaction, psychologist, consultation fee, and schedule.
     * 
     * @return \Illuminate\Http\Response
     */
    public function listConsulTransactionHistory()
    {   

        $user = Auth::user();

        $listTransaction = DB::table('consul_transactions')
            ->join('consultations', 'consul_transactions.consultation_id', '=', 'consultations.id')
            ->join('users as patients', 'consultations.user_id', '=', 'patients.id')
            ->join('users as psychologists', 'psychologists.id', '=', 'consultations.psi_id')
            ->join('psikolog_schedules', 'consultations.psch_id', '=', 'psikolog_schedules.id')
            ->join('main_schedules', 'psikolog_schedules.msch_id', '=', 'main_schedules.id')
            ->where('patients.id', $user->id)
            ->whereIn('consul_transactions.status', ['pending', 'pending_confirmation', 'completed', 'failed']) // Hanya konsultasi yang telah selesai
            ->select(
                'consul_transactions.id as transaction_id',
                'psychologists.name as psikolog_name',
                'psychologists.photo_profile as psikolog_profile',
                'consul_transactions.status as transaction_status',
                'consul_transactions.consul_fee',
                'consul_transactions.discount_amount',
                'psikolog_schedules.date as schedule_date',
                'main_schedules.day',
                'main_schedules.start_hour',
                'main_schedules.end_hour',
                'consul_transactions.payment_number'
            )
            ->orderByDesc('consul_transactions.created_at')
            ->get();

        $formattedTransaction = $listTransaction->map(function ($item) {
            return [
                'transaction_id' => $item->transaction_id,
                'no_pemesanan' => $item->payment_number,
                'psikolog_name' => $item->psikolog_name,
                'psikolog_profile'=> $item->psikolog_profile,
                'status' => $item->transaction_status,
                'total_amount' => $item->consul_fee - $item->discount_amount,
                'date' => \Carbon\Carbon::parse($item->schedule_date)->format('d M Y'),
                'time' => \Carbon\Carbon::parse($item->start_hour)->format('H:i') . ' - ' . \Carbon\Carbon::parse($item->end_hour)->format('H:i'),
            ];
        });

        return $this->sendResponse('Berhasil mengambil riwayat transaksi konsultasi pengguna', $formattedTransaction);
    }   
    
    /**
     * Get Transaction Detail
     *
     * @param  int  $transactionId
     * @return \Illuminate\Http\JsonResponse
     */
    public function detailConsulTransaction($transactionId)
    {
        try {
            // Ambil data transaksi berdasarkan ID
            $transaction = ConsultationTransaction::with([
                'consultation',
                'consultation.user',
                'consultation.psikolog.user',
                'consultation.psikolog.psikolog_category',
                'consultation.psikolog.psikolog_price',
                'consultation.topic',
                'consultation.psikologSchedule.mainSchedule',
                'paymentMethod',
                'consultation.chatSession',
            ])->findOrFail($transactionId);

            // Validasi apakah transaksi dimiliki oleh pengguna yang sedang login
            if ($transaction->user_id !== auth()->id()) {
                return $this->sendError('Anda tidak memiliki akses ke transaksi ini.', [], 403);
            }

            // Hitung rating psikolog
            $rating = DB::table('psikolog_reviews')
                ->where('psi_id', $transaction->consultation->psi_id)
                ->avg('rating') ?? 0;
            $rating = number_format($rating, 1);

            // Data untuk Resource
            $resourceData = new CreateConsultationResource((object)[
                'user' => $transaction->consultation->user,
                'psikolog' => $transaction->consultation->psikolog,
                'selectedSchedule' => $transaction->consultation->psikologSchedule,
                'selectedTopic' => $transaction->consultation->topic,
                'rating' => $rating,
                'transaction' => $transaction,
                'finalAmount' => $transaction->consul_fee - $transaction->discount_amount,
                'payment' => $transaction->paymentMethod,
                'consultation' => $transaction->consultation,
                'chatSession' => $transaction->consultation->chatSession
            ]);

            // Ubah Resource ke array dengan toArray() sebelum mengirim respons
            return $this->sendResponse(
                'Detail transaksi berhasil diambil.',
                $resourceData->toArray(request())
            );

        } catch (ModelNotFoundException $e) {
            return $this->sendError('Transaksi tidak ditemukan.', [], 404);
        } catch (Exception $e) {
            return $this->sendError('Terjadi kesalahan saat mengambil detail transaksi.', [$e->getMessage()], 500);
        }
    }

}
