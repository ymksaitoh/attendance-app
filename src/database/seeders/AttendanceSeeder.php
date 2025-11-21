<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;

class AttendanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $users = User::where('role', 'user')->get();

        foreach ($users as $user) {
            for($i = 0; $i < 90; $i++) {
                $date = Carbon::today()->subDays($i);
                if ($date->isWeekend()) continue;

                $clockIn = '09:00';
                $clockOut = '18:00';
                $breakStart = '12:00';
                $breakEnd = '13:00';

                Attendance::updateOrCreate(
                    [
                    'user_id' => $user->id,
                    'work_date' => $date,
                    ],
                    [
                    'clock_in' => $clockIn,
                    'clock_out' => $clockOut,
                    'status' => '退勤済'
                ]
            );

            }
        }
    }
}
