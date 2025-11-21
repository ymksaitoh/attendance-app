<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class AttendanceStaffListTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $this->user = User::factory()->create([
            'role' => 'user',
        ]);

        Attendance::factory()->create([
            'user_id' => $this->user->id,
            'work_date' => '2025-11-15',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);
    }

    public function test_admin_can_view_all_users_name_and_email()
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.staff.list'));

        $response->assertStatus(200);
        $response->assertSee($this->user->name);
        $response->assertSee($this->user->email);
    }

    public function test_admin_can_view_user_attendance_details()
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.attendances.staff', ['id' => $this->user->id]));

        $response->assertStatus(200);
        $response->assertSee('2025/11/15 (土)');
        $response->assertSee('09:00');
        $response->assertSee('18:00');

        $response->assertSee('09:00');
        $response->assertSee('18:00');
    }

    public function test_admin_can_navigate_to_previous_month()
    {
        $month = Carbon::parse('2025-11-01');

        $previousMonth = $month->copy()->subMonth()->format('Y-m');

        $response = $this->actingAs($this->admin)
            ->get(route('admin.attendances.staff', [
                'id' => $this->user->id,
                'month' => $previousMonth
            ]));

        $response->assertStatus(200);
        $response->assertSee(Carbon::createFromFormat('Y-m', $previousMonth)->format('Y/m'));
    }

    public function test_admin_can_navigate_to_next_month()
    {
        $month = Carbon::parse('2025-11-01');

        $nextMonth = $month->copy()->addMonth()->format('Y-m');

        $response = $this->actingAs($this->admin)
            ->get(route('admin.attendances.staff', [
                'id' => $this->user->id,
                'month' => $nextMonth
            ]));

        $response->assertStatus(200);
        $response->assertSee(Carbon::createFromFormat('Y-m', $nextMonth)->format('Y/m'));
    }

    public function test_clicking_detail_redirects_to_attendance_detail()
    {
        $attendance = Attendance::first();

        $response = $this->actingAs($this->admin)
            ->get(route('attendances.detail', $attendance->id));

        $response->assertStatus(200);
        $response->assertSee('2025年');
        $response->assertSee('11月15日');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
    }
}



