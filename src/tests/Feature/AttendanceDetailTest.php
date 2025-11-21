<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class AttendanceDetailTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $admin;

    public function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['role' => 'user']);
        $this->admin = User::factory()->create([
            'role' => 'admin',
            'name' => 'Admin User'
        ]);
    }

    public function test_user_name_is_displayed()
    {
        $attendance = Attendance::factory()->for($this->user)->create();

        $response = $this->actingAs($this->user)
                        ->get(route('attendances.detail', $attendance->id));

        $response->assertSee($this->user->name);
    }

    public function test_attendance_date_is_displayed()
    {
        $attendance = Attendance::factory()->for($this->user)->create([
            'work_date' => '2025-11-19',
        ]);

        $response = $this->actingAs($this->user)
                        ->get(route('attendances.detail', $attendance->id));

        $response->assertSee('2025年');
        $response->assertSee('11月19日');
    }

    public function test_clock_in_and_out_are_displayed()
    {
        $attendance = Attendance::factory()->for($this->user)->create([
            'clock_in' => '2025-11-19 09:00:00',
            'clock_out' => '2025-11-19 18:00:00',
        ]);

        $response = $this->actingAs($this->user)
                        ->get(route('attendances.detail', $attendance->id));

        $response->assertSee('09:00');
        $response->assertSee('18:00');
    }

    public function test_break_times_are_displayed()
    {
        $attendance = Attendance::factory()->for($this->user)->create();
        BreakTime::factory()->for($attendance)->create([
            'break_start' => '2025-11-19 12:00:00',
            'break_end' => '2025-11-19 12:30:00',
        ]);

        $response = $this->actingAs($this->user)
                        ->get(route('attendances.detail', $attendance->id));

        $response->assertSee('12:00');
        $response->assertSee('12:30');
    }

    public function test_admin_can_see_selected_attendance_data()
    {
        $user = User::factory()->create();
        $attendance = Attendance::factory()->for($user)->create([
            'clock_in' => '2025-11-19 09:00:00',
            'clock_out' => '2025-11-19 18:00:00',
            'work_date' => '2025-11-19',
        ]);

        \App\Models\AttendanceRequest::factory()->for($attendance)->create([
            'reason' => '備考サンプル',
            'status' => 'approved',
        ]);

        $response = $this->actingAs($this->admin)
                        ->get(route('admin.attendances.detail', $attendance->id));

        $response->assertSee($user->name);
        $response->assertSee('2025年');
        $response->assertSee('11月19日');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('');
    }

    public function test_admin_sees_error_if_clock_in_after_clock_out()
    {
        $user = User::factory()->create();
        $attendance = Attendance::factory()->for($user)->create([
            'clock_in' => '18:00',
            'clock_out' => '09:00',
        ]);

        $response = $this->actingAs($this->admin)
                        ->patch(route('attendances.request', $attendance->id), [
                            'clock_in' => '18:00',
                            'clock_out' => '09:00',
                            'reason' => '修正'
                        ]);

        $response->assertSessionHasErrors(['clock_in' => '出勤時間もしくは退勤時間が不適切な値です']);
    }

    public function test_admin_sees_error_if_break_start_after_clock_out()
    {
        $attendance = Attendance::factory()->for($this->user)->create([
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'work_date' => '2025-11-19',
        ]);

        $response = $this->actingAs($this->admin)
                        ->from(route('attendances.detail', $attendance->id))
                        ->patch(route('admin.attendances.update', $attendance->id), [
                            'clock_in' => '09:00',
                            'clock_out' => '18:00',
                            'break_start' => ['19:00'],
                            'break_end'   => ['19:30'],
                            'reason' => '修正',
                        ]);

        $response->assertSessionHasErrors([
            'break_start.0' => '休憩時間が不適切な値です',
        ]);
    }

    public function test_admin_sees_error_if_break_end_after_clock_out()
    {
        $attendance = Attendance::factory()->for($this->user)->create([
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'work_date' => '2025-11-19',
        ]);

        $response = $this->actingAs($this->admin)
                        ->from(route('attendances.detail', $attendance->id))
                        ->patch(route('admin.attendances.update', $attendance->id), [
                            'clock_in' => '09:00',
                            'clock_out' => '18:00',
                            'break_start' => ['17:00'],
                            'break_end'   => ['19:00'],
                            'reason' => '修正',
                        ]);

        $response->assertSessionHasErrors([
            'break_end.0' => '休憩時間もしくは退勤時間が不適切な値です',
        ]);
    }

    public function test_admin_sees_error_if_reason_empty()
    {
        $user = User::factory()->create();
        $attendance = Attendance::factory()->for($user)->create([
            'clock_in' => '09:00',
            'clock_out' => '18:00',
        ]);

        $response = $this->actingAs($this->admin)
                        ->patch(route('attendances.request', $attendance->id), [
                            'clock_in' => '09:00',
                            'clock_out' => '18:00',
                            'reason' => ''
                        ]);

        $response->assertSessionHasErrors(['reason' => '備考を記入してください']);
    }
}




