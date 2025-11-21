<?php

namespace Database\Factories;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

class AttendanceFactory extends Factory
{
    protected $model = Attendance::class;

    public function definition()
    {
        $clockIn = $this->faker->time('H:i:s');
        $clockOut = date('H:i:s', strtotime($clockIn) + rand(1, 8) * 3600);

        $totalWork = $this->calculateMinutes($clockIn, $clockOut);

        return [
            'user_id' => User::factory(),
            'work_date' => $this->faker->date(),
            'clock_in' => $clockIn,
            'clock_out' => $clockOut,
            'total_work' => $totalWork,
            'status' => $this->faker->randomElement(['勤務外', '勤務中', '申請中']),
        ];
    }

    private function calculateMinutes(string $clockIn, string $clockOut): int
    {
        $in = Carbon::parse($clockIn);
        $out = Carbon::parse($clockOut);

        if ($out->lt($in)) {
            $out->addDay();
        }

        return $out->diffInMinutes($in);
    }
}




