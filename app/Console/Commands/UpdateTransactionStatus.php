<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\ConsultationTransaction;

class UpdateTransactionStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:transaction-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update transaction status to failed if payment is not completed within 1 hour';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $transactions = ConsultationTransaction::where('status', 'pending')
            ->where('created_at', '<=', now()->subMinute(15))
            ->get();

        DB::beginTransaction();

        try {
            foreach ($transactions as $transaction) {
                // Update transaction status
                $transaction->status = 'failed';
                $transaction->failure_reason = 'Batas waktu pembayaran telah selesai';
                $transaction->save();

                // Update related consultation's status
                if ($transaction->consultation) {
                    $transaction->consultation->consul_status = 'failed';
                    $transaction->save();
                }
            }

            DB::commit();

            $this->info('Status transaksi dan konsultasi berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();

            $this->error('Terjadi kesalahan: ' . $e->getMessage());
        }
    }
}
