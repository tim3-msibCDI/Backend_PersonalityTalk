<?php

namespace App\Http\Controllers\API;

use Carbon\Carbon;
use App\Models\Consultation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\ConsultationTransaction;
use App\Http\Controllers\API\BaseController;

class ConsultationTransactionController extends BaseController
{
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

            // Cari transaksi
            $transaction = ConsultationTransaction::with('consultation')->findOrFail($transactionId);

            // Validasi status sebelum approve
            if ($transaction->status !== 'pending') {
                return $this->sendError('Transaksi tidak dapat di-approve karena sudah diproses sebelumnya.', [], 422);
            }

            // Update status transaksi dan konsultasi
            $transaction->status = 'completed';
            $transaction->save();

            // Update status konsultasi menjadi scheduled
            $consultation = $transaction->consultation;
            $consultation->consul_status = 'scheduled';
            $consultation->save();

            DB::commit();
            return $this->sendResponse('Bukti pembayaran berhasil di-approve.', $transaction);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError('Terjadi kesalahan saat approve pembayaran.', [$e->getMessage()], 500);
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
    public function disapprovePaymentProof(Request $request, $transactionId)
    {
        try {
            DB::beginTransaction();

            // Cari transaksi
            $transaction = ConsultationTransaction::with('consultation')->findOrFail($transactionId);

            // Validasi status sebelum disapprove
            if ($transaction->status !== 'pending') {
                return $this->sendError('Transaksi tidak dapat di-disapprove karena sudah diproses sebelumnya.', [], 422);
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
            return $this->sendResponse('Bukti pembayaran berhasil di-disapprove.', $transaction);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError('Terjadi kesalahan saat disapprove pembayaran.', [$e->getMessage()], 500);
        }
    }
}

