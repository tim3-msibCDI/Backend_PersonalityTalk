<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use App\Models\ConsultationTransaction;

class UpdateTransactionStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'transaction:update-status';

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
            ->where('created_at', '<=', Carbon::now()->subHour())
            ->get();

        // dd($transactions);
    
        foreach ($transactions as $transaction) {
            $transaction->update([
                'status' => 'failed',
                'failure_reason' => 'Batas waktu pembayaran telah selesai'
            ]);
        }

        $this->info('Status transaksi berhasil diperbarui');
    }
}
