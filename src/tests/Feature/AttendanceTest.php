<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class AttendanceTest extends TestCase
{
    use RefreshDatabase;

    private $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    public function test_current_date_time_is_displayed_correctly()
    {
        Carbon::setTestNow('2024-01-01 09:30:00');

        $response = $this->actingAs($this->user)
                        ->get(route('attendances.create'));

        $response->assertSee('2024年01月01日 (Mon)');
        $response->assertSee('09:30');
    }

    public function test_status_is_displayed_as_not_working()
    {
        $response = $this->actingAs($this->user)
                        ->get(route('attendances.create'));

        $response->assertSee('勤務外');
    }

    public function test_status_is_displayed_as_working()
    {
        Attendance::create([
            'user_id'   => $this->user->id,
            'work_date' => now()->toDateString(),
            'clock_in'  => now(),
            'status'    => '出勤中',
        ]);

        $response = $this->actingAs($this->user)
                        ->get(route('attendances.create'));

        $response->assertSee('出勤中');
    }

    public function test_status_is_displayed_as_on_break()
    {
        Attendance::create([
            'user_id'   => $this->user->id,
            'work_date' => now()->toDateString(),
            'clock_in'  => now(),
            'status'    => '休憩中',
        ]);

        $response = $this->actingAs($this->user)
                        ->get(route('attendances.create'));

        $response->assertSee('休憩中');
    }

    public function test_status_is_displayed_as_clocked_out()
    {
        Attendance::create([
            'user_id'   => $this->user->id,
            'work_date' => now()->toDateString(),
            'clock_in'  => now()->subHours(8),
            'clock_out' => now(),
            'status'    => '退勤済',
        ]);

        $response = $this->actingAs($this->user)
                        ->get(route('attendances.create'));

        $response->assertSee('退勤済');
    }

    public function test_clock_in_changes_status_to_working()
    {
        $response = $this->actingAs($this->user)
                        ->post(route('attendances.store'), ['action' => 'clock_in']);

        $attendance = Attendance::first();
        $this->assertEquals('出勤中', $attendance->status);
    }

    public function test_clock_in_button_does_not_show_after_clock_in()
    {
        Attendance::create([
            'user_id' => $this->user->id,
            'work_date' => now()->toDateString(),
            'clock_in' => now(),
            'status' => '出勤中',
        ]);

        $response = $this->actingAs($this->user)
                        ->get(route('attendances.create'));

        $response->assertDontSee('<button type="submit" name="action" value="clock_in"', false);
    }

    public function test_clock_in_time_is_recorded_in_list()
    {
        $this->actingAs($this->user)
            ->post(route('attendances.store'), ['action' => 'clock_in']);

        $response = $this->actingAs($this->user)
                        ->get(route('attendances.list'));

        $attendance = Attendance::first();
        $time = $attendance->clock_in->format('H:i');
        $response->assertSee($time);
    }

    public function test_break_start_changes_status_to_break()
    {
        $attendance = Attendance::create([
            'user_id' => $this->user->id,
            'work_date' => now()->toDateString(),
            'clock_in' => now(),
            'status' => '出勤中',
        ]);

        $this->actingAs($this->user)
            ->post(route('attendances.store'), ['action' => 'break_start']);

        $attendance->refresh();
        $this->assertEquals('休憩中', $attendance->status);
    }

    public function test_break_start_button_is_always_visible_while_working()
    {
        $attendance = Attendance::create([
            'user_id' => $this->user->id,
            'work_date' => now()->toDateString(),
            'clock_in' => now(),
            'status' => '出勤中',
        ]);

        $response = $this->actingAs($this->user)
                        ->get(route('attendances.create'));

        $response->assertSee('休憩入');
    }

    public function test_break_end_changes_status_to_working()
    {
        $attendance = Attendance::create([
            'user_id' => $this->user->id,
            'work_date' => now()->toDateString(),
            'clock_in' => now(),
            'status' => '休憩中',
        ]);

        $this->actingAs($this->user)
            ->post(route('attendances.store'), ['action' => 'break_end']);

        $attendance->refresh();
        $this->assertEquals('出勤中', $attendance->status);
    }

    public function test_break_end_button_is_always_visible_while_on_break()
    {
        $attendance = Attendance::create([
            'user_id' => $this->user->id,
            'work_date' => now()->toDateString(),
            'clock_in' => now(),
            'status' => '休憩中',
        ]);

        $response = $this->actingAs($this->user)
                        ->get(route('attendances.create'));

        $response->assertSee('休憩戻');
    }

    public function test_break_times_are_recorded_in_list()
    {
        $attendance = Attendance::create([
            'user_id' => $this->user->id,
            'work_date' => now()->toDateString(),
            'clock_in' => now(),
            'status' => '出勤中',
        ]);

        $this->actingAs($this->user)
            ->post(route('attendances.store'), ['action' => 'break_start']);

        $this->actingAs($this->user)
            ->post(route('attendances.store'), ['action' => 'break_end']);

        $response = $this->actingAs($this->user)
                        ->get(route('attendances.list'));

        $break = BreakTime::first();
        $start = $break->break_start->format('H:i');
        $end = $break->break_end->format('H:i');

        $response->assertSee($start);
        $response->assertSee($end);
    }

public function test_clock_out_changes_status_to_finished()
{
    $attendance = Attendance::create([
        'user_id' => $this->user->id,
        'work_date' => now()->toDateString(),
        'clock_in' => now()->subHours(8),
        'status' => '出勤中',
    ]);

    $this->actingAs($this->user)
        ->post(route('attendances.store'), ['action' => 'clock_out']);

    $attendance->refresh();
    $this->assertEquals('退勤済', $attendance->status);
}

    public function test_clock_out_time_is_recorded_in_list()
    {
        $attendance = Attendance::create([
            'user_id' => $this->user->id,
            'work_date' => now()->toDateString(),
            'clock_in' => now()->subHours(8),
            'status' => '出勤中',
        ]);

        $this->actingAs($this->user)
            ->post(route('attendances.store'), ['action' => 'clock_out']);

        $response = $this->actingAs($this->user)
                        ->get(route('attendances.list'));

        $attendance->refresh();
        $time = $attendance->clock_out->format('H:i');
        $response->assertSee($time);
    }

}


