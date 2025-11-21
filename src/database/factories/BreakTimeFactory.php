<?php

namespace Database\Factories;

use App\Models\BreakTime;
use App\Models\Attendance;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

class BreakTimeFactory extends Factory
{
    protected $model = BreakTime::class;

    public function definition()
    {
        $start = Carbon::today()->addHours(12);
        $end = $start->copy()->addMinutes(30);

        return [
            'attendance_id' => Attendance::factory(),
            'break_start' => $start,
            'break_end' => $end,
            'total_break' => $start->diffInMinutes($end),
        ];
    }
}
