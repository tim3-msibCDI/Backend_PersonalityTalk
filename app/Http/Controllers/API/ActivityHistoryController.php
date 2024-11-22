<?php

namespace App\Http\Controllers\API;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\API\BaseController;
use Illuminate\Http\Request;

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
            ->where('patients.id', $user->id) // Filter berdasarkan pengguna yang sedang login
            // ->whereIn('consultations.consul_status', ['completed', 'done']) // Hanya konsultasi yang telah selesai
            ->select(
                'consultations.id as consultation_id',
                'psychologists.name as psikolog_name',
                'psychologists.photo_profile as psikolog_profile',
                'consultations.consul_status',
                'psikolog_schedules.date as schedule_date',
                'main_schedules.day',
                'main_schedules.start_hour',
                'main_schedules.end_hour'
            )
            ->orderByDesc('consultations.updated_at') // Urutkan berdasarkan tanggal selesai terbaru
            ->get();

        $formattedConsultation = $listConsultation->map(function ($item) {
            return [
                'consultation_id' => $item->consultation_id,
                'psikolog_name' => $item->psikolog_name,
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
                'main_schedules.end_hour'
            )
            ->orderByDesc('consul_transactions.updated_at')
            ->get();

        $formattedTransaction = $listTransaction->map(function ($item) {
            return [
                'transaction_id' => $item->transaction_id,
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
}