<?php

namespace App\Http\Controllers\API;

use Carbon\Carbon;
use App\Models\ChatSession;
use App\Models\Consultation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\ConsultationTransaction;
use Illuminate\Support\Facades\Storage;
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
                'client_name' => $transaction->user->name,
                'payment_date' => $transaction->payment_completed_at 
                    ? Carbon::parse($transaction->payment_completed_at)->format('d-m-Y H:i') 
                    : null,
                'payment_method' => $transaction->paymentMethod->name,
                'status' => $transaction->status,
                'consul_fee' => $transaction->consul_fee,
                'psikolog_comission' => $psikolog_comission,
                'sender_name' => $transaction->sender_name,
                'sender_bank' => $transaction->sender_bank,
                'payment_proof' => $transaction->payment_proof,
                'failure_reason' => $transaction->failure_reason,
            ];
        });

        // Simpan data hasil transformasi ke dalam paginasi
        $paginatedData = $transactions->toArray();
        $paginatedData['data'] = $data;

        return $this->sendResponse('List transaksi berhasil diambil.', $paginatedData);
    }

    public function searchConsulTransaction(Request $request)
    {
        // Validasi input pencarian
        $request->validate([
            'search' => 'nullable|string|max:255',
        ]);

        $search = $request->search;

        $transactions = ConsultationTransaction::with(['consultation', 'user', 'paymentMethod'])
            ->when($search, function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    // Filter berdasarkan nama klien
                    $subQuery->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', '%' . $search . '%');
                    })
                    // Filter berdasarkan nomor pembayaran
                    ->orWhere('payment_number', 'like', '%' . $search . '%')
                    // Filter berdasarkan harga konsultasi
                    ->orWhere('consul_fee', 'like', '%' . $search . '%')
                    // Filter berdasarkan nama metode pembayaran
                    ->orWhereHas('paymentMethod', function ($methodQuery) use ($search) {
                        $methodQuery->where('name', 'like', '%' . $search . '%');
                    })
                    // Filter berdasarkan status transaksi
                    ->orWhere('status', 'like', '%' . $search . '%');
                });
            })
            ->orderByRaw('ISNULL(payment_completed_at), payment_completed_at DESC') // Prioritas transaksi selesai
            ->orderBy('created_at', 'asc') // Urutkan berdasarkan waktu dibuat
            ->paginate(10);

        // Transformasi data untuk setiap item
        $data = $transactions->getCollection()->transform(function ($transaction) {
            $consultation_price = $transaction->consul_fee - $transaction->discount_amount;
            $psikolog_comission = $transaction->consul_fee * 0.6;

            return [
                'id' => $transaction->id,
                'payment_number' => $transaction->payment_number,
                'client_name' => $transaction->user->name,
                'payment_date' => $transaction->payment_completed_at 
                    ? Carbon::parse($transaction->payment_completed_at)->format('d-m-Y H:i') 
                    : null,
                'payment_method' => $transaction->paymentMethod->name,
                'status' => $transaction->status,
                'consul_fee' => $transaction->consul_fee,
                'psikolog_comission' => $psikolog_comission,
                'sender_name' => $transaction->sender_name,
                'sender_bank' => $transaction->sender_bank,
                'payment_proof' => $transaction->payment_proof,
                'failure_reason' => $transaction->failure_reason,
            ];
        });

        // Simpan data hasil transformasi ke dalam paginasi
        $paginatedData = $transactions->toArray();
        $paginatedData['data'] = $data;

        return $this->sendResponse('List transaksi berhasil diambil.', $paginatedData);
    }


    public function detailPaymentProof($transactionId){
        $transaction = ConsultationTransaction::find($transactionId);

        if (!$transaction) {
            return $this->sendError('Transaksi tidak ditemukan.', [], 404);
        }

        $data = [
            'sender_name' => $transaction->sender_name,
            'sender_bank' => $transaction->sender_bank,
            'photo' => $transaction->payment_proof
        ]; 
        return $this->sendResponse('Detail bukti pembayaran berhasil diambil.', $data);
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
            $chatSession = new ChatSession();
            $chatSession->user_id = $consultation->user_id;
            $chatSession->psi_id = $consultation->psi_id;
            $chatSession->consultation_id = $consultation->id;
            $chatSession->start_time = $consultation->psikologSchedule->mainSchedule->start_hour;
            $chatSession->end_time = $consultation->psikologSchedule->mainSchedule->end_hour;
            $chatSession->save();

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
            \Log::info('Reason received: ', [$request->reason]);

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

    public function listPsikologCommission(){
        $transactions = ConsultationTransaction::with(['consultation.psikolog.user', 'paymentMethod'])
            ->whereHas('consultation', function ($query) {
                $query->where('consul_status', 'completed');
            })
            ->orderByRaw('ISNULL(payment_completed_at), payment_completed_at DESC') // Prioritas: yang sudah selesai dulu
            ->orderBy('created_at', 'asc') // Jika waktu pembayaran sama, urutkan berdasarkan waktu dibuat
            ->paginate(10); // Sesuaikan jumlah per halaman
        
        // Transformasi data untuk setiap item
        $data = $transactions->getCollection()->transform(function ($transaction) {
            $psikolog_comission = $transaction->consul_fee * 0.6;

            return [
                'id' => $transaction->id,
                'psikolog_name' => $transaction->consultation->psikolog->user->name,
                'payment_date' => $transaction->payment_completed_at 
                    ? Carbon::parse($transaction->payment_completed_at)->format('d-m-Y H:i') 
                    : null,
                'payment_method' => $transaction->paymentMethod->name,
                'psikolog_comission' => $psikolog_comission,
                'commission_transfer_status' => $transaction->commission_transfer_status,
                'commission_transfer_proof' => $transaction->commission_transfer_proof,
            ];
        });

        // Simpan data hasil transformasi ke dalam paginasi
        $paginatedData = $transactions->toArray();
        $paginatedData['data'] = $data;
        return $this->sendResponse('List transaksi berhasil diambil.', $paginatedData);
    }

    public function getDetailPsikologCommission($transactionId){
        $transaction = ConsultationTransaction::with('consultation.psikolog.user', 'paymentMethod')
            ->where('id', $transactionId)
            ->whereHas('consultation', function ($query) {
                $query->where('consul_status', 'completed');
            })
            ->first();

        if (!$transaction) {
            return $this->sendError('Transaksi tidak ditemukan.', [], 404);
        }

        $psikolog_comission = $transaction->consul_fee * 0.6;
        $data = [
            'id' => $transaction->id,
            'psikolog_name' => $transaction->consultation->psikolog->user->name,
            'psikolog_comission' => $psikolog_comission,
            'payment_method' => $transaction->paymentMethod->name,
            'rekening' => $transaction->consultation->psikolog->account_number
        ];
        return $this->sendResponse('Detail komisi psikolog berhasil diambil', $data);
    }

    /**
     * Get list of transaction IDs that have completed payment and are waiting for 
     * commission confirmation.
     * 
     * @return \Illuminate\Http\JsonResponse 
     *  
     */
    public function listIdPsikologCommission(){
        $transactions = ConsultationTransaction::select('id')
            ->where('status', 'completed')
            ->where('commission_transfer_status', 'Menunggu Konfirmasi')
            ->orderBy('payment_completed_at', 'desc')
            ->get();

        return $this->sendResponse('List transaksi berhasil diambil.', $transactions);
    }

    public function transferCommission(Request $request, $transactionId)
    {
        $request->validate([
            'transfer_proof' => 'required|file|mimes:jpeg,png,jpg|max:2048', // Maksimal 2MB
        ]);

        $transaction = ConsultationTransaction::find($transactionId);
        if (!$transaction) {
            return $this->sendError('Transaksi tidak ditemukan.', [], 404);
        }

        // Pastikan status transaksi memungkinkan transfer komisi
        if ($transaction->commission_transfer_status === 'Diterima') {
            return $this->sendError('Komisi sudah ditransfer sebelumnya.', [], 400);
        }

        if ($request->hasFile('transfer_proof')) {
            $imagePath = Storage::disk('public')->put('commission_psikolog_proofs', $request->file('transfer_proof'));
            if (!$imagePath) {
                return $this->sendError('Gagal menyimpan gambar.', [], 500);
            }
        }
        $imageUrl = 'storage/' . $imagePath;

        $transaction->commission_transfer_proof = $imageUrl;
        $transaction->commission_transfer_status = 'Menunggu Konfirmasi';
        $transaction->save();

        return $this->sendResponse('Bukti transfer berhasil dikirim.', [
            'transaction_id' => $transaction->id,
            'transfer_proof_url' => asset($imageUrl), 
        ]);
    }

    public function updatePsikologCommission(Request $request, $transactionId)
    {
        $transaction = ConsultationTransaction::find($transactionId);
        if (!$transaction) {
            return $this->sendError('Transaksi tidak ditemukan.', [], 404);
        }

        $request->validate([
            'transfer_proof' => 'required|file|mimes:jpeg,png,jpg|max:2048',
        ]);

        try {
            DB::beginTransaction();

            $imageUrl = $transaction->commission_transfer_proof; // Default ke nilai lama

            // Hapus file lama jika ada dan file baru diunggah
            if ($request->hasFile('transfer_proof')) {
                if ($transaction->commission_transfer_proof) {
                    $oldFilePath = str_replace('storage/', '', $transaction->commission_transfer_proof);
                    if (Storage::disk('public')->exists($oldFilePath)) {
                        Storage::disk('public')->delete($oldFilePath);
                    }
                }

                // Simpan file baru
                $imagePath = Storage::disk('public')->put('commission_psikolog_proofs', $request->file('transfer_proof'));
                if (!$imagePath) {
                    return $this->sendError('Gagal menyimpan gambar.', [], 500);
                }

                $imageUrl = 'storage/' . $imagePath;
            }

            // Perbarui data transaksi
            $transaction->commission_transfer_proof = $imageUrl;
            $transaction->commission_transfer_status = 'Menunggu Konfirmasi';
            $transaction->save();

            DB::commit();

            return $this->sendResponse('Bukti transfer komisi berhasil diperbarui.', null);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError('Terjadi kesalahan saat memperbarui bukti pembayaran.', [$e->getMessage()], 500);
        }
    }

}

