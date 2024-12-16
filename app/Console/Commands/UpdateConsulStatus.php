<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use App\Models\Consultation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class UpdateConsulStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:consultation-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update consultation status based on scheduled time';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $consultations = Consultation::with('psikologSchedule.mainSchedule')
            ->whereIn('consul_status', ['scheduled', 'ongoing'])
            ->get();

        foreach ($consultations as $consultation) {
            $psikologSchedule = $consultation->psikologSchedule;

            if ($psikologSchedule) {
                $mainSchedule = $psikologSchedule->mainSchedule;

                if ($mainSchedule) {
                    // Ambil waktu mulai dan selesai dari MainSchedule
                    $startTime = Carbon::parse($psikologSchedule->date . ' ' . $mainSchedule->start_hour);
                    $endTime = Carbon::parse($psikologSchedule->date . ' ' . $mainSchedule->end_hour);

                    Log::info(" ID: {$consultation->id}, Status: {$consultation->consul_status}, Waktu mulai: {$startTime}, Waktu selesai: {$endTime}, Waktu saat ini: " . Carbon::now());

                    // Update status berdasarkan waktu saat ini
                    if ($consultation->consul_status == 'scheduled') {
                        
                        // Jika sekarang sudah melewati waktu mulai, ubah status menjadi ongoing
                        if (Carbon::now()->greaterThanOrEqualTo($startTime)) {
                            $consultation->consul_status = 'ongoing';
                            $consultation->save();
                            $this->info("Konsultasi {$consultation->id} status diubah menjadi ongoing.");
                        }
                    }

                    if ($consultation->consul_status == 'ongoing') {
                        // Jika sekarang sudah melewati waktu selesai, ubah status menjadi completed
                        if (Carbon::now()->greaterThanOrEqualTo($endTime)) {
                            $consultation->consul_status = 'completed';
                            $consultation->save();
                            $this->info("Konsultasi {$consultation->id} status diubah menjadi completed.");
                        }
                    }
                }
            }
        }

        $this->info('Status konsultasi berhasil diperbarui.');
    }
}

