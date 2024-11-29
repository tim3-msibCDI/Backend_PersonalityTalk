<?php

namespace App\Http\Controllers\API;

use Carbon\Carbon;
use App\Models\Consultation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\ConsultationTransaction;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\API\BaseController;
use App\Http\Resources\ConsultationTransactionResource;

class ConsultationTransactionController extends BaseController
{
    /**
     * List Transaction
     * 
     * @return \Illuminate\Http\JsonResponse 
     *  
     */
    public function listConsulTransaction()
    {
        $transactions = ConsultationTransaction::with(['consultation' ,'user',])->get();
        return $this->sendResponse('List transaksi berhasil diambil.', ConsultationTransactionResource::collection($transactions));
    }

    /**
     * Approve Payment Proof
     * 
     * @param  \Illuminate\Http\Request $request
     * @param int  $transactionId                                                                              
     * @return \Illuminate\Http\JsonResponse 
     *  
     */
    public function approvePaymentProof(Request $request, $transactionId)
    {
        try {
            DB::beginTransaction();

            $transaction = ConsultationTransaction::with('consultation')->find($transactionId);
            if (!$transaction) {
                return $this->sendError('Transaksi tidak ditemukan.', [], 404);
            }

            // Validasi status sebelum approve
            if ($transaction->status !== 'pending_confirmation') {
                return $this->sendError('Transaksi tidak dapat diterima karena sudah diproses sebelumnya.', [], 422);
            }

            // Update status transaksi dan konsultasi
            $transaction->status = 'completed';
            $transaction->save();

            // Update status konsultasi menjadi scheduled
            $consultation = $transaction->consultation;
            $consultation->consul_status = 'scheduled';
            $consultation->save();

            DB::commit();
            return $this->sendResponse('Bukti pembayaran berhasil diterima.', $transaction);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError('Terjadi kesalahan saat menerima bukti pembayaran.', [$e->getMessage()], 500);
        }
    }

    /**
     * Disapprove Payment Proof
     * 
     * @param  \Illuminate\Http\Request $request
     * @param int  $transactionId                                                                              
     * @return \Illuminate\Http\JsonResponse 
     *  
     */
    public function rejectPaymentProof(Request $request, $transactionId)
    {
        // Validasi input
        $validatedData = Validator::make($request->all(), [
            'reason' => 'required|string',
        ], [
            'reason.required' => 'Alasan wajib diisi.',
        ]);

        try {
            DB::beginTransaction();

            // Cari transaksi
            $transaction = ConsultationTransaction::with('consultation')->find($transactionId);
            if (!$transaction) {
                return $this->sendError('Transaksi tidak ditemukan.', [], 404);
            }

            // Validasi status sebelum disapprove
            if ($transaction->status !== 'pending_confirmation') {
                return $this->sendError('Transaksi tidak dapat ditolak karena sudah diproses sebelumnya.', [], 422);
            }

            // Update status transaksi dan konsultasi
            $transaction->status = 'failed';
            $transaction->failure_reason = $request->reason ?? 'Pembayaran tidak valid';
            $transaction->save();

            // Update status konsultasi menjadi failed
            $consultation = $transaction->consultation;
            $consultation->consul_status = 'failed';
            $consultation->save();

            DB::commit();
            return $this->sendResponse('Bukti pembayaran berhasil ditolak.', $transaction);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError('Terjadi kesalahan saat menolak bukti pembayaran.', [$e->getMessage()], 500);
        }
    }
}

