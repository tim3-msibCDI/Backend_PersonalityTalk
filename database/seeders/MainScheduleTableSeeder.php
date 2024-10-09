<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class MainScheduleTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Define time range for each day (6:00 AM to 10:00 PM)
        $startHour = 6;
        $endHour = 22;

        // Define days of the week
        $daysOfWeek = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

        foreach ($daysOfWeek as $day) {
            // Loop setiap jam
            for ($hour = $startHour; $hour < $endHour; $hour++) {
                // Create a new schedule entry for each hour
                DB::table('main_schedules')->insert([
                    'day' => $day,
                    'start_hour' => Carbon::createFromTime($hour, 0, 0)->format('H:i'), // Format as 24-hour format (HH:MM)
                    'end_hour' => Carbon::createFromTime($hour + 1, 0, 0)->format('H:i'), // Next hour
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
