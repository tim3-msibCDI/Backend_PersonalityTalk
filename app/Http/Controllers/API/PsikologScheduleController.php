<?php

namespace App\Http\Controllers\API;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\PsikologSchedule;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class PsikologScheduleController extends BaseController
{   
    /**
     * Generate psikolog schedule 
     */
    public function generatePsikologSchedule(Request $request)
    {
        $request->validate([
            'psikolog_id' => 'required|exists:psikolog,id',
            'schedules' => 'required|array', // Array of days with specific main_schedule_ids
            'schedules.*.day' => 'required|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
            'schedules.*.main_schedule_ids' => 'required|array', // Array of selected time slots (main_schedule IDs)
            'schedules.*.main_schedule_ids.*' => 'exists:main_schedules,id',
            'month' => 'required|integer|between:1,12',
            'year' => 'required|integer|min:2024|max:2100',
        ],[
            'month.required' => 'Bulan wajib dipilih.',
            'year.required' => 'Tahun wajib dipilih.',
        ]);

        $psikologId = $request->psikolog_id;
        $schedules = $request->schedules;
        $month = $request->month;
        $year = $request->year;

        try {
            DB::beginTransaction();

            // Get all existing schedules for the psychologist in the selected month and year
            $existingSchedules = PsikologSchedule::where('psikolog_id', $psikologId)
                ->whereMonth('date', $month)
                ->whereYear('date', $year)
                ->get();

            // Prepare a list of new schedules to keep track of what should remain
            $newSchedules = [];

            // Loop through each day and its associated time slots
            foreach ($schedules as $schedule) {
                $dayOfWeek = $schedule['day'];
                $mainScheduleIds = $schedule['main_schedule_ids'];

                // Get all dates for the current day of the week in the selected month
                $dates = $this->getDatesForDayInMonth($dayOfWeek, $month, $year);

                // Loop through each date and create entries in psikolog_schedules or mark them to keep
                foreach ($dates as $date) {
                    foreach ($mainScheduleIds as $mschId) {
                        $newSchedules[] = [
                            'psikolog_id' => $psikologId,
                            'date' => $date,
                            'msch_id' => $mschId,
                        ];

                        // Check if schedule already exists, if not, insert new
                        $existingSchedule = PsikologSchedule::where('psikolog_id', $psikologId)
                            ->where('date', $date)
                            ->where('msch_id', $mschId)
                            ->first();

                        if (!$existingSchedule) {
                            PsikologSchedule::create([
                                'psikolog_id' => $psikologId,
                                'date' => $date,
                                'msch_id' => $mschId,
                                'is_available' => true, // Default to available when generating new schedules
                            ]);
                        }
                    }
                }
            }

            // Identify and delete schedules that were unchecked
            foreach ($existingSchedules as $existingSchedule) {
                $shouldKeep = false;
                foreach ($newSchedules as $newSchedule) {
                    if ($existingSchedule->psikolog_id == $newSchedule['psikolog_id'] &&
                        $existingSchedule->date == $newSchedule['date'] &&
                        $existingSchedule->msch_id == $newSchedule['msch_id']) {
                        $shouldKeep = true;
                        break;
                    }
                }

                // If the existing schedule is no longer in the new schedules, delete it
                if (!$shouldKeep) {
                    $existingSchedule->delete();
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Jadwal psikolog berhasil diperbarui untuk bulan ' . $month . ' ' . $year,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Terjadi kesalahan saat memperbarui jadwal: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all dates in a given month and year for a specific day of the week.
     */
    private function getDatesForDayInMonth($dayOfWeek, $month, $year)
    {
        $startOfMonth = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endOfMonth = Carbon::createFromDate($year, $month, 1)->endOfMonth();
        $dates = [];

        // dd($startOfMonth, $endOfMonth);


        // Loop through each day of the month and check if it matches the given day of the week
        for ($date = $startOfMonth->copy(); $date->lte($endOfMonth); $date->addDay()) {
            if ($date->isDayOfWeek(Carbon::parse($dayOfWeek)->dayOfWeek)) {
                $dates[] = $date->format('Y-m-d');
            }
        }

        return $dates;
    }
}
