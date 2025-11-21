<?php

namespace Database\Factories;

use App\Models\AttendanceRequest;
use App\Models\Attendance;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AttendanceRequestFactory extends Factory
{
    protected $model = AttendanceRequest::class;

    public function definition()
    {
        $user = User::factory()->create(['role' => 'user']);
        $attendance = Attendance::factory()->create(['user_id' => $user->id]);

        $afterValue = [
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'breaks' => [
                ['break_start' => '12:00', 'break_end' => '12:30'],
            ],
        ];

        return [
            'user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'after_value' => json_encode($afterValue, JSON_UNESCAPED_UNICODE),
            'status' => 'pending',
        ];
    }

    public function approved()
    {
        return $this->state(fn () => ['status' => 'approved']);
    }

    public function pending()
    {
        return $this->state(fn () => ['status' => 'pending']);
    }
}



