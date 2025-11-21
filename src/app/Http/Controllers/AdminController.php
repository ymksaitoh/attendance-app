<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\AttendanceRequest;
use App\Models\User;
use Carbon\Carbon;

class AdminController extends Controller
{
    public function showLoginForm()
    {
        return view('admin.admin_login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required',
            'password' => 'required',
        ], [
            'email.required' => 'メールアドレスを入力してください',
            'password.required' => 'パスワードを入力してください',
        ]);

        $credentials = $request->only('email', 'password');

        if (auth()->attempt($credentials)) {
            $user = auth()->user();
            if ($user->role !== 'admin') {
                auth()->logout();
                return back()->withErrors(['email' => 'ログイン情報が登録されていません']);
            }
            return redirect()->route('admin.attendances.list');
        }
        return back()->withErrors(['email' => 'ログイン情報が登録されていません']);
    }

    public function attendanceList(Request $request)
    {
        $targetDate = $request->route('date')
            ? Carbon::parse($request->route('date'))
            : Carbon::today();

        $previousDay = $targetDate->copy()->subDay();
        $nextDay = $targetDate->copy()->addDay();

        $attendances = Attendance::with('user', 'breaks')
            ->whereDate('work_date', $targetDate)
            ->get();

        return view('admin.admin_attendance_list', compact(
            'attendances', 'targetDate', 'previousDay', 'nextDay'
        ));

    }

    public function staffList()
    {
        $users = User::where('role', 'user')->get();

        return view('admin.admin_staff_list', compact('users'));
    }

    public function staffAttendance($id, Request $request)
    {
        $month = $request->input('month', now()->format('Y-m'));
        $monthCarbon = Carbon::createFromFormat('Y-m', $month);

        $attendances = Attendance::where('user_id', $id)
            ->whereYear('work_date', $monthCarbon->year)
            ->whereMonth('work_date', $monthCarbon->month)
            ->orderBy('work_date')
            ->get();

        $user = User::findOrFail($id);

        $previousMonth = $monthCarbon->copy()->subMonth()->format('Y-m');
        $nextMonth = $monthCarbon->copy()->addMonth()->format('Y-m');

        return view('admin.admin_attendance_staff', compact(
            'attendances', 'user', 'month', 'previousMonth', 'nextMonth'
        ) + ['isAdmin' => true]);
    }

    public function exportStaffAttendance(Request $request)
    {
        $userId = $request->input('user_id');
        $month = $request->input('month', now()->format('Y-m'));
        $monthCarbon = Carbon::createFromFormat('Y-m', $month);

        $attendances = Attendance::where('user_id', $userId)
            ->whereYear('work_date', $monthCarbon->year)
            ->whereMonth('work_date', $monthCarbon->month)
            ->orderBy('work_date')
            ->with('breaks')
            ->get();

        $user = User::findOrFail($userId);

        $csvHeader = ['日付', '出勤', '退勤', '休憩', '合計'];

        $callback = function() use ($attendances, $csvHeader) {
            $file = fopen('php://output', 'w');
            fwrite($file, "\xEF\xBB\xBF");
            fputcsv($file, $csvHeader);

            foreach ($attendances as $attendance) {
                $breaks = $attendance->breaks;

                $breakText = $breaks->isNotEmpty()
                    ? $breaks->map(fn($b) => $b->formattedTotalBreak() ?? '-')->implode(' / ') : '-';

                $totalWork = $attendance->formattedTotalWork() ?? '-';

                $row = [
                    $attendance->formattedDate(),
                    $attendance->clock_in ? Carbon::parse($attendance->clock_in)->format('H:i') : '-',
                    $attendance->clock_out ? Carbon::parse($attendance->clock_out)->format('H:i') : '-',
                    $breakText,
                    $totalWork
                ];
                fputcsv($file, $row);
            }

            fclose($file);
        };

        $fileName = sprintf('%s_勤怠_%s.csv', $user->name, $month);

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename={$fileName}"
        ]);
    }

    public function approve($attendance_correct_request_id)
    {
        if (auth()->user()->role !== 'admin') {
            abort(403, '権限がありません');
        }

        $request = AttendanceRequest::with(['user', 'attendance'])->findOrFail($attendance_correct_request_id);

        return view('admin.admin_attendance_approve', compact('request'));
    }

    public function approveUpdate($attendance_correct_request_id)
    {
        if (auth()->user()->role !== 'admin') {
            abort(403, '権限がありません');
        }

        $attendanceRequest = AttendanceRequest::with('attendance')->findOrFail($attendance_correct_request_id);

        if ($attendanceRequest->status === 'approved') {
            return back()->with('info', 'この申請はすでに承認されています。');
        }

        $attendance = $attendanceRequest->attendance;
        $after = (array) json_decode($attendanceRequest->after_value, true);

        $clockIn = isset($after['clock_in'])
            ? \Carbon\Carbon::parse($after['clock_in'])->setTimezone('Asia/Tokyo')->format('H:i:s')
            : null;

        $clockOut = isset($after['clock_out'])
            ? \Carbon\Carbon::parse($after['clock_out'])->setTimezone('Asia/Tokyo')->format('H:i:s')
            : null;

        $attendance->update([
            'clock_in' => $clockIn,
            'clock_out' => $clockOut,
            'reason' => $after['reason'] ?? null,
        ]);

        $attendance->breaks()->delete();

        if (!empty($after['breaks'])) {
            foreach ($after['breaks'] as $b) {
                if (!empty($b['break_start']) && !empty($b['break_end'])) {

                    $breakStart = preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $b['break_start'])
                        ? $attendance->work_date . ' ' . $b['break_start']
                        : $b['break_start'];

                    $breakEnd = preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $b['break_end'])
                        ? $attendance->work_date . ' ' . $b['break_end']
                        : $b['break_end'];

                    $attendance->breaks()->create([
                        'break_start' => \Carbon\Carbon::parse($breakStart)
                            ->setTimezone('Asia/Tokyo')
                            ->format('Y-m-d H:i:s'),
                        'break_end' => \Carbon\Carbon::parse($breakEnd)
                            ->setTimezone('Asia/Tokyo')
                            ->format('Y-m-d H:i:s'),
                    ]);
                }
            }
        }


        $attendanceRequest->update(['status' => 'approved']);

        return redirect()
            ->route('attendances.request.list', ['tab' => 'approved'])
            ->with('success', '申請を承認しました。');
    }

}
