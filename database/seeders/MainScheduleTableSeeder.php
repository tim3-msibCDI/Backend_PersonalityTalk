<?php

namespace Database\Seeders;

use Carbon\Carbon;
use App\Models\MainSchedule;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class MainScheduleTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        Schema::disableForeignKeyConstraints();
        MainSchedule::truncate();
        Schema::enableForeignKeyConstraints();

        // Define time range for each day (6:00 AM to 10:00 PM)
        $startHour = 6;
        $endHour = 22;

        // Define days of the week
        $daysOfWeek = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

        // Loop through each day
        foreach ($daysOfWeek as $day) {
            // Loop through each hour from 6:00 AM to 10:00 PM
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
