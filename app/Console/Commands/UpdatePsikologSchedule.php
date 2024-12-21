<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use App\Models\PsikologSchedule;

class UpdatePsikologSchedule extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:psikolog-schedules';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update is_available in psikolog_schedules if the schedule time has passed';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $now = Carbon::now();

        // Fetch all schedules that are still marked as available
        $schedules = PsikologSchedule::with('mainSchedule')
            ->where('is_available', true)
            ->get();

        foreach ($schedules as $schedule) {
            $scheduleDate = Carbon::parse($schedule->date);
            $startTime = Carbon::parse($schedule->mainSchedule->start_hour);

            // Combine the schedule date and start hour to get the full start time
            $fullStartTime = $scheduleDate->copy()->setTimeFrom($startTime);

            // If the start time has passed, set is_available to false
            if ($fullStartTime->lt($now)) {
                $schedule->update(['is_available' => false]);
            }
        }

        $this->info('Ketersediaan jadwal psikolog berhasil diperbarui.');
    }
}
