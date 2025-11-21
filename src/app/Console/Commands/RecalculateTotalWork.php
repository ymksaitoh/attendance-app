<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Attendance;
use Carbon\Carbon;

class RecalculateTotalWork extends Command
{
    protected $signature = 'attendance:recalculate-total-work';
    protected $description = 'Recalculate total_work for all attendances where it is null';

    public function handle()
    {
        $attendances = Attendance::with('breaks')->whereNull('total_work')->get();
        $updatedCount = 0;

        foreach ($attendances as $attendance) {
            if ($attendance->clock_in && $attendance->clock_out) {
                $clockIn = Carbon::parse($attendance->clock_in);
                $clockOut = Carbon::parse($attendance->clock_out);

                $totalMinutes = $clockIn->diffInMinutes($clockOut);

                foreach ($attendance->breaks as $break) {
                    if ($break->break_start && $break->break_end) {
                        $totalMinutes -= Carbon::parse($break->break_start)->diffInMinutes(Carbon::parse($break->break_end));
                    }
                }

                $attendance->total_work = max($totalMinutes, 0);
                $attendance->save();

                $updatedCount++;
            }
        }

        $this->info("✅ Recalculated total_work for {$updatedCount} attendances.");
        return Command::SUCCESS;
    }
}
