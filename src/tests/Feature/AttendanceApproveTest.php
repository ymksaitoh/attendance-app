<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\AttendanceRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class AttendanceApproveTest extends TestCase
{
    use RefreshDatabase;

    private $admin;
    private $user;
    private $attendance;
    private $request;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $this->user = User::factory()->create([
            'role' => 'user',
        ]);

        $this->attendance = Attendance::factory()->create([
            'user_id' => $this->user->id,
            'work_date' => '2025-11-19',
            'clock_in' => '2025-11-19 09:00:00',
            'clock_out' => '2025-11-19 18:00:00',
            'total_work' => 540,
            'status' => 'pending',
        ]);

        $before = [
            'clock_in' => '2025-11-19 09:00:00',
            'clock_out' => '2025-11-19 18:00:00',
            'reason' => '遅刻の修正',
        ];

        $after = [
            'clock_in' => '2025-11-19 10:00:00',
            'clock_out' => '2025-11-19 19:00:00',
            'reason' => '遅刻の修正',
        ];

        $this->request = AttendanceRequest::factory()->create([
            'user_id' => $this->user->id,
            'attendance_id' => $this->attendance->id,
            'request_type' => 'time_correction',
            'before_value' => json_encode($before),
            'after_value' => json_encode($after),
            'reason' => $after['reason'],
            'status' => 'pending',
        ]);
    }

    public function test_pending_request_displays_correctly()
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.attendances.approve', ['attendance_correct_request_id' => $this->request->id]));

        $response->assertStatus(200);
        $response->assertSee('承認');
        $response->assertSee($this->user->name);
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('遅刻の修正');
    }

    public function test_approved_request_displays_as_approved()
    {
        $this->request->update(['status' => 'approved']);

        $after = json_decode($this->request->after_value, true);
        $this->attendance->update([
            'clock_in' => $after['clock_in'],
            'clock_out' => $after['clock_out'],
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.attendances.approve', ['attendance_correct_request_id' => $this->request->id]));

        $response->assertStatus(200);
        $response->assertSee('承認済み');
        $response->assertSee('10:00');
        $response->assertSee('19:00');
        $response->assertSee('遅刻の修正');
    }

    public function test_can_approve_pending_request()
    {
        $response = $this->actingAs($this->admin)
            ->patch(route('admin.attendances.approveUpdate', ['attendance_correct_request_id' => $this->request->id]));

        $response->assertRedirect(route('attendances.request.list', ['tab' => 'approved']));
        $response->assertSessionHas('success', '申請を承認しました。');

        $this->request->refresh();
        $this->attendance->refresh();

        $this->assertEquals('approved', $this->request->status);
        $this->assertEquals('10:00:00', Carbon::parse($this->attendance->clock_in)->format('H:i:s'));
        $this->assertEquals('19:00:00', Carbon::parse($this->attendance->clock_out)->format('H:i:s'));
    }

    public function test_cannot_reapprove_already_approved_request()
    {
        $this->request->update(['status' => 'approved']);

        $response = $this->actingAs($this->admin)
            ->patch(route('admin.attendances.approveUpdate', ['attendance_correct_request_id' => $this->request->id]));

        $response->assertSessionHas('info', 'この申請はすでに承認されています。');
    }
}































