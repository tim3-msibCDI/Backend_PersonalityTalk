<?php

namespace App\Http\Controllers\API;

use Carbon\Carbon;
use App\Models\MainSchedule;
use App\Models\Psikolog;
use Illuminate\Http\Request;
use App\Models\PsikologSchedule;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class PsikologScheduleController extends BaseController
{   
     /**
     * Get all Main Schedule Data
     *
     * @return \Illuminate\Http\JsonResponse   
     *     
     */
    public function getMainSchedules()
    {
        // Fetch all main schedules ordered by day of the week and time
        $mainSchedules = MainSchedule::select('id', 'day', 'start_hour', 'end_hour')
            ->orderByRaw("FIELD(day, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday')")
            ->orderBy('start_hour')
            ->get();

        // Group schedules by day of the week, keeping the day as the key
        $groupedSchedules = $mainSchedules->groupBy('day')->map(function($schedules) {
            return $schedules->map(function ($schedule) {
                return [
                    'id' => $schedule->id,
                    'time_slot' => Carbon::parse($schedule->start_hour)->format('H:i') 
                               . ' - ' . 
                               Carbon::parse($schedule->end_hour)->format('H:i')
                ];
            });
        });

        return $this->sendResponse('Berhasil mengambil data jadwal master', $groupedSchedules);        
    }

    /**
     * Gemerate Psikolog Schedule
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse   
     *     
     */
    public function generatePsikologSchedule(Request $request)
    {
        $user = Auth::user();
        $psikologId = $user->psikolog->id;

        $validatedData = Validator::make($request->all(),[
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

        if ($validatedData->fails()) {
            return $this->sendError('Validasi gagal', $validatedData->errors(), 422);
        }

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
            return $this->sendResponse('Jadwal psikolog berhasil dibuat untuk bulan ' . $month . ' ' . $year, null);

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError('Terjadi kesalahan saat memperbarui jadwal.', [$e->getMessage()], 500);
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

    /**
     * Get the psychologist's schedule for a specific date.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSchedulesByDate(Request $request)
    {
        $user = Auth::user();
        $psikologId = $user->psikolog->id;
        $date = $request->date; // Expected input: "YYYY-MM-DD"

        // Fetch schedules for the specific date
        $schedules = PsikologSchedule::with('mainSchedule') 
            ->where('psikolog_id', $psikologId)
            ->where('date', $date)
            ->get();

        // Map the schedules into the required format
        $formattedSchedules = $schedules->map(function ($schedule) {
            return [
                'id' => $schedule->id,
                'is_available' => $schedule->is_available,
                'time_slot' => Carbon::parse($schedule->mainSchedule->start_hour)->format('H:i')
                    . ' - ' .
                    Carbon::parse($schedule->mainSchedule->end_hour)->format('H:i'),
            ];
        });

        return $this->sendResponse('Berhasil mengambil data jadwal psikolog.', $formattedSchedules);
    }

    /**
     * Bulk update psychologist schedules for a specific day.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkUpdatePsikologSchedule(Request $request)
    {
        $user = Auth::user();
        $psikologId = $user->psikolog->id;

        // Validate the input
        $validated = $request->validate([
            'schedules' => 'required|array',
            'schedules.*.schedule_id' => 'required|exists:psikolog_schedules,id',
            'schedules.*.is_available' => 'required|boolean',
        ]);

        $updatedSchedules = collect();

        // Loop through the schedules and update their availability
        foreach ($validated['schedules'] as $scheduleData) {
            $schedule = PsikologSchedule::where('id', $scheduleData['schedule_id'])
                ->where('psikolog_id', $psikologId)
                ->first();

            if ($schedule) {
                $schedule->is_available = $scheduleData['is_available'];
                $schedule->save();
                $updatedSchedules->push([
                    'id' => $schedule->id,
                    'is_available' => $schedule->is_available,
                    'time_slot' => Carbon::parse($schedule->mainSchedule->start_hour)->format('H:i')
                        . ' - ' .
                        Carbon::parse($schedule->mainSchedule->end_hour)->format('H:i'),
                ]);
            }
        }

        return $this->sendResponse(
            'Jadwal berhasil diperbarui.',
            $updatedSchedules
        );
    }

    /**
     * Get all schedules for the current logged in psychologist.
     * 
     * Only schedules for the current psychologist are returned.
     * 
     * @return \Illuminate\Http\JsonResponse A response containing the list of schedules.
     */
    public function listPsikolog()
    {
        $psikologs = Psikolog::with('user:id,name,email')
            ->where('is_active', true)
            ->get();
        // dd($psikologs);

        $list_psikolog = $psikologs->map(function ($psikolog) {
            return [
                'id' => $psikolog->id,
                'sipp' => $psikolog->sipp,
                'name' => $psikolog->user->name,
            ];
        });

        return $this->sendResponse('List psikolog pada jadwal konsultasi berhasil diambil.', $list_psikolog);
    }

    /**
     * Get the psychologist's schedule for a specific date.
     *
     * @param int $psikologId The ID of the psychologist.
     * @param string $date The date of the schedules to retrieve. Format: "YYYY-MM-DD".
     * @return \Illuminate\Http\JsonResponse A response containing the list of schedules in the required format.
     */
    public function detailPsikologSchedule(Request $request, $psikologId)
    {
        $date = $request->date; // Expected input: "YYYY-MM-DD"

        // Fetch schedules for the specific date
        $schedules = PsikologSchedule::with(['mainSchedule', 'consultation']) 
            ->where('psikolog_id', $psikologId) 
            ->where('date', $date)
            ->get();

        // Map the schedules into the required format
        $formattedSchedules = $schedules->map(function ($schedule) {
            return [
                'id' => $schedule->id,
                'time_slot' => Carbon::parse($schedule->mainSchedule->start_hour)->format('H:i')
                    . ' - ' .
                    Carbon::parse($schedule->mainSchedule->end_hour)->format('H:i'),
                'consul_status' => $schedule->consultation
                    ? $schedule->consultation->consul_status
                    : 'available',
                'is_available' => $schedule->is_available,
            ];
        });

        return $this->sendResponse('Berhasil mengambil data jadwal psikolog.', $formattedSchedules);
    }

}
