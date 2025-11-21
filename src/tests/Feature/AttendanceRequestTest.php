<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\AttendanceRequest;

class AttendanceRequestTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $attendance;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'role' => 'user'
        ]);

        $this->attendance = Attendance::factory()->create([
            'user_id' => $this->user->id
        ]);
    }

    public function test_clock_in_invalid()
    {
        $response = $this->actingAs($this->user)
            ->patch(route('attendances.request', ['id' => $this->attendance->id]), [
                'clock_in' => '18:00',
                'clock_out' => '09:00',
                'reason' => 'テスト',
            ]);

        $response->assertSessionHasErrors([
            'clock_in' => '出勤時間もしくは退勤時間が不適切な値です'
        ]);
    }

    public function test_break_start_invalid()
    {
        $response = $this->actingAs($this->user)
            ->patch(route('attendances.request', ['id' => $this->attendance->id]), [
                'clock_in' => '09:00',
                'clock_out' => '18:00',
                'break_start' => ['19:00'],
                'break_end' => ['10:00'],
                'reason' => 'テスト',
            ]);

        $response->assertSessionHasErrors(['break_start.0']);
    }

    public function test_break_end_invalid()
    {
        $response = $this->actingAs($this->user)
            ->patch(route('attendances.request', ['id' => $this->attendance->id]), [
                'clock_in' => '09:00',
                'clock_out' => '18:00',
                'break_start' => ['12:00'],
                'break_end' => ['11:00'],
                'reason' => 'テスト',
            ]);

        $response->assertSessionHasErrors(['break_start.0']);
    }

    public function test_empty_reason()
    {
        $response = $this->actingAs($this->user)
            ->patch(route('attendances.request', ['id' => $this->attendance->id]), [
                'clock_in' => '09:00',
                'clock_out' => '18:00',
                'reason' => '',
            ]);

        $response->assertSessionHasErrors(['reason']);
    }

    public function test_submit_request()
    {
        $response = $this->actingAs($this->user)
            ->patch(route('attendances.request', ['id' => $this->attendance->id]), [
                'clock_in' => '09:00',
                'clock_out' => '18:00',
                'reason' => 'テスト申請'
            ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('attendance_requests', [
            'reason' => 'テスト申請',
            'status' => 'pending'
        ]);
    }

    public function test_pending_requests_list()
    {
        AttendanceRequest::factory()->create([
            'attendance_id' => $this->attendance->id,
            'user_id' => $this->user->id,
            'status' => 'pending',
            'reason' => '承認待ちテスト'
        ]);

        AttendanceRequest::factory()->create([
            'attendance_id' => $this->attendance->id,
            'user_id' => $this->user->id,
            'status' => 'approved',
            'reason' => '承認済みテスト'
        ]);

        $response = $this->actingAs($this->user)->get(
            route('attendances.request.list', ['tab' => 'pending'])
        );

        $response->assertStatus(200);
        $response->assertSee('承認待ちテスト');
        $response->assertDontSee('承認済みテスト');
    }

    public function test_approved_requests_list()
    {
        AttendanceRequest::factory()->create([
            'attendance_id' => $this->attendance->id,
            'user_id' => $this->user->id,
            'status' => 'approved',
            'reason' => '承認済みテスト'
        ]);

        AttendanceRequest::factory()->create([
            'attendance_id' => $this->attendance->id,
            'user_id' => $this->user->id,
            'status' => 'pending',
            'reason' => '承認待ちテスト'
        ]);

        $response = $this->actingAs($this->user)->get(
            route('attendances.request.list', ['tab' => 'approved'])
        );

        $response->assertStatus(200);
        $response->assertSee('承認済みテスト');
        $response->assertDontSee('承認待ちテスト');
    }

    public function test_request_detail_redirect_to_attendance_detail()
    {
        $req = AttendanceRequest::factory()->create([
            'attendance_id' => $this->attendance->id,
            'user_id' => $this->user->id,
            'status' => 'pending',
            'reason' => '詳細テスト'
        ]);

        $response = $this->actingAs($this->user)->get(
            route('attendances.detail', ['id' => $req->attendance->id])
        );

        $response->assertStatus(200);

        $response->assertSee($req->attendance->work_date->format('Y年'));
        $response->assertSee($req->attendance->work_date->format('n月j日'));
    }

}



























