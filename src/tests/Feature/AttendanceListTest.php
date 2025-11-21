<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class AttendanceListTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['role' => 'user']);

        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    public function test_user_can_see_all_their_attendance()
    {
        $attendance = Attendance::factory()->create([
            'user_id' => $this->user->id,
            'work_date' => '2025-11-15',
            'clock_in' => '2025-11-15 09:00:00',
            'clock_out' => '2025-11-15 18:00:00',
            'total_work' => 540,
            'status' => '勤務外',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('attendances.list'));

        $response->assertStatus(200);
        $response->assertSee('2025/11/15 (土)');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
    }

    public function test_user_sees_current_month_on_list_page()
    {
        $response = $this->actingAs($this->user)
            ->get(route('attendances.list'));

        $response->assertStatus(200);
        $month = now()->format('Y/m');
        $response->assertSee($month);
    }

    public function test_user_can_navigate_previous_month()
    {
        $previousMonth = now()->subMonth()->format('Y-m');
        $previousMonthDisplay = now()->subMonth()->format('Y/m');

        $response = $this->actingAs($this->user)
            ->get(route('attendances.list', ['month' => $previousMonth]));

        $response->assertStatus(200);
        $response->assertSee($previousMonthDisplay);
    }

    public function test_user_can_navigate_next_month()
    {
        $nextMonth = now()->copy()->addMonth()->format('Y-m');
        $nextMonthDisplay = now()->copy()->addMonth()->format('Y/m');

        $response = $this->actingAs($this->user)
            ->get(route('attendances.list', ['month' => $nextMonth]));

        $response->assertStatus(200);
        $response->assertSee($nextMonthDisplay);
    }

    public function test_user_can_click_detail_and_see_attendance_detail()
    {
        $attendance = Attendance::factory()->create([
            'user_id' => $this->user->id,
            'work_date' => '2025-11-15',
            'clock_in' => '2025-11-15 09:00:00',
            'clock_out' => '2025-11-15 18:00:00',
            'total_work' => 540,
            'status' => '申請中',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('attendances.detail', $attendance->id));

        $response->assertStatus(200);
        $response->assertSee('2025年');
        $response->assertSee('11月15日');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
    }

    public function test_admin_can_see_all_users_attendance_for_the_day()
    {
        $attendance1 = Attendance::factory()->create([
            'user_id' => $this->user->id,
            'work_date' => '2025-11-15',
            'clock_in' => '2025-11-15 09:00:00',
            'clock_out' => '2025-11-15 18:00:00',
            'total_work' => 540,
            'status' => '勤務外',
        ]);

        $attendance2 = Attendance::factory()->create([
            'user_id' => $this->admin->id,
            'work_date' => '2025-11-15',
            'clock_in' => '2025-11-15 09:30:00',
            'clock_out' => '2025-11-15 17:30:00',
            'total_work' => 480,
            'status' => '勤務外',
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.attendances.list', ['date' => '2025-11-15']));

        $response->assertStatus(200);
        $response->assertSee('2025/11/15');
        $response->assertSee('09:00');
        $response->assertSee('09:30');
    }

    public function test_admin_sees_current_date_on_list_page()
    {
        $today = now()->format('Y-m-d');
        $response = $this->actingAs($this->admin)
            ->get(route('admin.attendances.list'));

        $response->assertStatus(200);
        $response->assertSee(now()->format('Y/m/d'));
    }

    public function test_admin_can_navigate_previous_day()
    {
        $previousDay = now()->subDay()->format('Y-m-d');
        $previousDayDisplay = now()->subDay()->format('Y/m/d');

        $response = $this->actingAs($this->admin)
            ->get(route('admin.attendances.list', ['date' => $previousDay]));

        $response->assertStatus(200);
        $response->assertSee($previousDayDisplay);
    }

    public function test_admin_can_navigate_next_day()
    {
        $nextDay = now()->addDay()->format('Y-m-d');
        $nextDayDisplay = now()->addDay()->format('Y/m/d');
        $response = $this->actingAs($this->admin)
            ->get(route('admin.attendances.list', ['date' => $nextDay]));

        $response->assertStatus(200);
        $response->assertSee($nextDayDisplay);
    }
}












