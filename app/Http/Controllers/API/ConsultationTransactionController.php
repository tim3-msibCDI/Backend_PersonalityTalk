<?php

namespace App\Http\Controllers\API;

use Carbon\Carbon;
use App\Models\ChatSession;
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
        $transactions = ConsultationTransaction::with(['consultation', 'user', 'paymentMethod'])
            ->orderByRaw('ISNULL(payment_completed_at), payment_completed_at DESC') // Prioritas: yang sudah selesai dulu
            ->orderBy('created_at', 'asc') // Jika waktu pembayaran sama, urutkan berdasarkan waktu dibuat
            ->paginate(10); // Sesuaikan jumlah per halaman

        // Transformasi data untuk setiap item
        $data = $transactions->getCollection()->transform(function ($transaction) {
            $consultation_price = $transaction->consul_fee - $transaction->discount_amount;
            $psikolog_comission = $transaction->consul_fee * 0.6;

            return [
                'id' => $transaction->id,
                'payment_number' => $transaction->payment_number,
                'user_name' => $transaction->user->name,
                'payment_date' => $transaction->payment_completed_at 
                    ? Carbon::parse($transaction->payment_completed_at)->format('d-m-Y H:i') 
                    : null,
                'payment_method' => $transaction->paymentMethod->name,
                'status' => $transaction->status,
                'consul_fee' => $transaction->consul_fee,
                'psikolog_comission' => $psikolog_comission,
                'payment_proof' => $transaction->payment_proof,
            ];
        });

        // Simpan data hasil transformasi ke dalam paginasi
        $paginatedData = $transactions->toArray();
        $paginatedData['data'] = $data;

        return $this->sendResponse('List transaksi berhasil diambil.', $paginatedData);
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

            $transaction = ConsultationTransaction::with('consultation.psikologSchedule.mainSchedule')->find($transactionId);
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

            // Buat chat session yang terjadwalkan
            $chatSession = ChatSession::create([
                'user_id' => $consultation->user_id,
                'psi_id' => $consultation->psi_id,
                'consultation_id' => $consultation->id,
                'start_time' => $consultation->psikologSchedule->mainSchedule->start_hour,
                'end_time' => $consultation->psikologSchedule->mainSchedule->end_hour
            ]);

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

