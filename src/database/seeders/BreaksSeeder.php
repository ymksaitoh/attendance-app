<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use Carbon\Carbon;

class BreaksSeeder extends Seeder
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

            $attendances = Attendance::where('user_id', $user->id)->get();

            foreach ($attendances as $attendance) {

                BreakTime::create([
                    'attendance_id' => $attendance->id,
                    'break_start' => Carbon::parse($attendance->work_date . ' 12:00:00'),
                    'break_end' => Carbon::parse($attendance->work_date . ' 13:00:00'),
                    'total_break' => 60,
                ]);
            }
        }
    }
}
