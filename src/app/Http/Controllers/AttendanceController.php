<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\AttendanceRequest;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    public function create()
    {
        $status = '勤務外';
        return view('attendance.attendance', compact('status'));
    }

    public function store(Request $request)
    {

        $userID = auth()->id();
        $today = now()->toDateString();
        $action = $request->input('action');

        $attendance = Attendance::firstOrCreate(
            ['user_id' => $userID, 'work_date' => $today],
            ['status' => '勤務外']
        );

        switch ($action) {
            case 'clock_in':
                if (!$attendance->clock_in) {
                    $attendance->clock_in = now();
                    $attendance->status = '出勤中';
                }
                break;

            case 'clock_out':
                if (!$attendance->clock_out) {
                    $attendance->clock_out = now();

                    if ($attendance->clock_in) {
                        $workMinutes = \Carbon\Carbon::parse($attendance->clock_in)
                                        ->diffInMinutes(\Carbon\Carbon::parse($attendance->clock_out));

                        $totalBreak = optional($attendance->breaks)->sum('total_break') ?? 0;

                        $attendance->total_work = max(0, $workMinutes - $totalBreak);
                    }

                    $attendance->status = '退勤済';
                }
                break;

            case 'break_start':
                if ($attendance->status === '出勤中') {
                    $attendance->status = '休憩中';
                    $attendance->save();

                    BreakTime::create([
                        'attendance_id' => $attendance->id,
                        'break_start' => now(),
                    ]);
                }
                break;

            case 'break_end':
                if ($attendance->status === '休憩中') {
                    $attendance->status = '出勤中';
                    $attendance->save();

                    $lastBreak = BreakTime::where('attendance_id', $attendance->id)
                                        ->whereNull('break_end')
                                        ->latest()
                                        ->first();

                    if ($lastBreak) {
                        $lastBreak->break_end = now();

                        if ($lastBreak->break_start) {
                            $lastBreak->total_break = \Carbon\Carbon::parse($lastBreak->break_start)
                                ->diffInMinutes(\Carbon\Carbon::parse($lastBreak->break_end));
                        } else {
                            $lastBreak->total_break = 0;
                        }

                        $lastBreak->save();
                    }
                }
                break;
        }

        $attendance->save();
        return redirect()->route('attendances.create')->with('status', $attendance->status);
    }

    public function list(Request $request)
    {
        $userIdParam = $request->query('user_id');
        $staff = null;

        if ($userIdParam) {
            $staff = \App\Models\User::findOrFail($userIdParam);
            $userId = $staff->id;
        } else {
            $userId = auth()->id();
        }

        $month = $request->input('month')
            ? Carbon::createFromFormat('Y-m', $request->input('month'))
            : Carbon::now();


        $previousMonth = $month->copy()->subMonth();
        $nextMonth = $month->copy()->addMonth();

        $attendances = Attendance::where('user_id', auth()->id())
                                ->whereYear('work_date', $month->year)
                                ->whereMonth('work_date', $month->month)
                                ->orderBy('work_date')
                                ->get();

        return view('attendance.attendance_list', compact('attendances', 'month', 'previousMonth', 'nextMonth', 'staff'));
    }

    public function detail($id)
    {
        $attendance = Attendance::with(['user', 'breaks', 'requests'])->findOrFail($id);
        $isAdmin = auth()->user()->role === 'admin';

        $displayDate = [
            'clock_in' => $attendance->clock_in ? Carbon::parse($attendance->clock_in)->setTimezone('Asia/Tokyo') : null,
            'clock_out' => $attendance->clock_out ? Carbon::parse($attendance->clock_out)->setTimezone('Asia/Tokyo') : null,
            'breaks' => $attendance->breaks->map(function($b) {
                return [
                    'start' => $b->break_start ? Carbon::parse($b->break_start)->setTimezone('Asia/Tokyo') : null,
                    'end' => $b->break_end ? Carbon::parse($b->break_end)->setTimezone('Asia/Tokyo') : null,
                ];
            }),
        ];
        $pendingRequest = null;

        $latestApproved = $attendance->requests()->where('status', 'approved')->latest()->first();

        $latestPendingQuery = $attendance->requests()->where('status', 'pending');
        if (!$isAdmin) {
            $latestPendingQuery->where('user_id', auth()->id());
        }
        $latestPending = $latestPendingQuery->latest()->first();

        if ($isAdmin && $latestPending) {
            $before = json_decode($latestPending->before_value, true);

            $displayDate = [
                'clock_in' => isset($before['clock_in'])
                    ? Carbon::parse($before['clock_in'])->setTimezone('Asia/Tokyo')
                    : $displayDate['clock_in'],

                'clock_out' => isset($before['clock_out'])
                    ? Carbon::parse($before['clock_out'])->setTimezone('Asia/Tokyo')
                    : $displayDate['clock_out'],

                'breaks' => collect($before['breaks'] ?? [])->map(function($b) {
                    return [
                        'start' => isset($b['break_start']) ? Carbon::parse($b['break_start'])->setTimezone('Asia/Tokyo') : null,
                        'end'   => isset($b['break_end']) ? Carbon::parse($b['break_end'])->setTimezone('Asia/Tokyo') : null,
                    ];
                }),
            ];

            $pendingRequest = $latestPending;
        }

        if (!$isAdmin && $latestPending) {
            $after = json_decode($latestPending->after_value, true);

            $displayDate = [
                'clock_in' => isset($after['clock_in'])
                    ? Carbon::parse($after['clock_in'])->setTimezone('Asia/Tokyo')
                    : $displayDate['clock_in'],

                'clock_out' => isset($after['clock_out'])
                    ? Carbon::parse($after['clock_out'])->setTimezone('Asia/Tokyo')
                    : $displayDate['clock_out'],

                'breaks' => collect($after['breaks'] ?? [])->map(function($b) {
                    return [
                        'start' => isset($b['break_start']) ? Carbon::parse($b['break_start'])->setTimezone('Asia/Tokyo') : null,
                        'end'   => isset($b['break_end']) ? Carbon::parse($b['break_end'])->setTimezone('Asia/Tokyo') : null,
                    ];
                }),
            ];

            $pendingRequest = $latestPending;
        }

        if (!$pendingRequest && $latestApproved) {
            $after = json_decode($latestApproved->after_value, true);

            $displayDate = [
                'clock_in' => isset($after['clock_in'])
                    ? Carbon::parse($after['clock_in'])->setTimezone('Asia/Tokyo')
                    : $displayDate['clock_in'],

                'clock_out' => isset($after['clock_out'])
                    ? Carbon::parse($after['clock_out'])->setTimezone('Asia/Tokyo')
                    : $displayDate['clock_out'],

                'breaks' => collect($after['breaks'] ?? [])->map(function($b) {
                    return [
                        'start' => isset($b['break_start']) ? Carbon::parse($b['break_start'])->setTimezone('Asia/Tokyo') : null,
                        'end'   => isset($b['break_end']) ? Carbon::parse($b['break_end'])->setTimezone('Asia/Tokyo') : null,
                    ];
                }),
            ];
        }

        if ($isAdmin) {
            return view('admin.admin_attendance_detail', compact('attendance', 'displayDate', 'pendingRequest'));
        } else {
            return view('attendance.attendance_detail', compact('attendance', 'displayDate', 'pendingRequest'));
        }
    }

public function update(Request $request, $id)
{
    $attendance = Attendance::with('breaks')->findOrFail($id);
    $user = auth()->user();

    if ($attendance->requests()->where('status', 'pending')->exists()) {
        return redirect()->back()->with('error', '＊承認待ちのため修正はできません。');
    }

    $validated = $request->validate([
        'reason' => 'required|string|max:255',
    ], [
        'reason.required' => '備考を記入してください',
    ]);

    $workDate = $attendance->work_date
        ? Carbon::parse($attendance->work_date)->format('Y-m-d')
        : now()->format('Y-m-d');

    try {
        $clockIn = $request->clock_in
            ? Carbon::parse("$workDate {$request->clock_in}")
            : null;
    } catch (\Exception $e) {
        $clockIn = null;
    }

    try {
        $clockOut = $request->clock_out
            ? Carbon::parse("$workDate {$request->clock_out}")
            : null;
    } catch (\Exception $e) {
        $clockOut = null;
    }

    if ($clockIn && $clockOut && $clockIn->gt($clockOut)) {
        return back()->withInput()->withErrors([
            'clock_in' => '出勤時間もしくは退勤時間が不適切な値です'
        ]);
    }

    if ($request->break_start) {
        foreach ($request->break_start as $i => $start) {
            $end = $request->break_end[$i] ?? null;

            try {
                $breakStart = $start ? Carbon::parse("$workDate $start") : null;
            } catch (\Exception $e) {
                return back()->withInput()->withErrors([
                    "break_start.$i" => '休憩時間が不適切な値です'
                ]);
            }

            try {
                $breakEnd = $end ? Carbon::parse("$workDate $end") : null;
            } catch (\Exception $e) {
                return back()->withInput()->withErrors([
                    "break_end.$i" => '休憩時間が不適切な値です'
                ]);
            }

            if ($breakStart && $clockIn && $breakStart->lt($clockIn)) {
                return back()->withInput()->withErrors([
                    "break_start.$i" => '休憩時間が不適切な値です'
                ]);
            }

            if ($breakStart && $clockOut && $breakStart->gt($clockOut)) {
                return back()->withInput()->withErrors([
                    "break_start.$i" => '休憩時間が不適切な値です'
                ]);
            }

            if ($breakEnd && $clockOut && $breakEnd->gt($clockOut)) {
                return back()->withInput()->withErrors([
                    "break_end.$i" => '休憩時間もしくは退勤時間が不適切な値です'
                ]);
            }

            if ($breakStart && $breakEnd && $breakStart->gt($breakEnd)) {
                return back()->withInput()->withErrors([
                    "break_start.$i" => '休憩時間が不適切な値です'
                ]);
            }
        }
    }

    $before = [
        'clock_in' => $attendance->clock_in,
        'clock_out' => $attendance->clock_out,
        'breaks' => $attendance->breaks->map(fn($b) => [
            'break_start' => $b->break_start,
            'break_end' => $b->break_end,
        ]),
        'reason' => $attendance->reason,
    ];

    $after = [
        'clock_in' => $request->clock_in ? Carbon::parse("$workDate {$request->clock_in}") : null,
        'clock_out' => $request->clock_out ? Carbon::parse("$workDate {$request->clock_out}") : null,
        'breaks' => collect($request->break_start ?? [])->map(function ($start, $i) use ($request, $workDate) {
            return [
                'break_start' => $start ? Carbon::parse("$workDate $start") : null,
                'break_end' => !empty($request->break_end[$i])
                    ? Carbon::parse("$workDate {$request->break_end[$i]}")
                    : null,
            ];
        }),
        'reason' => $request->reason,
    ];

    if ($user->role !== 'admin') {
        $attendance->requests()->create([
            'user_id' => $user->id,
            'request_type' => 'attendance_edit',
            'before_value' => json_encode($before, JSON_UNESCAPED_UNICODE),
            'after_value' => json_encode($after, JSON_UNESCAPED_UNICODE),
            'reason' => $request->reason,
            'status' => 'pending',
        ]);

        return redirect()->route('attendances.detail', $id)
                        ->with('success', '修正申請を送信しました');
    }

    $attendance->update([
        'clock_in' => $after['clock_in'],
        'clock_out' => $after['clock_out'],
        'reason' => $after['reason'],
    ]);

    $existingBreaks = $attendance->breaks->values();
    $newBreaks = collect($after['breaks'])->values();
    $max = max($existingBreaks->count(), $newBreaks->count());

    for ($i = 0; $i < $max; $i++) {
        $new = $newBreaks[$i] ?? null;
        $existing = $existingBreaks[$i] ?? null;

        if ($new && $new['break_start'] && $new['break_end']) {
            $totalMinutes = $new['break_start']->diffInMinutes($new['break_end']);

            if ($existing) {
                $existing->update([
                    'break_start' => $new['break_start'],
                    'break_end' => $new['break_end'],
                    'total_break' => $totalMinutes,
                ]);
            } else {
                $attendance->breaks()->create([
                    'break_start' => $new['break_start'],
                    'break_end' => $new['break_end'],
                    'total_break' => $totalMinutes,
                ]);
            }
        }
    }

    $attendance->requests()->create([
        'user_id' => $attendance->user_id,
        'request_type' => 'attendance_edit',
        'before_value' => json_encode($before, JSON_UNESCAPED_UNICODE),
        'after_value' => json_encode($after, JSON_UNESCAPED_UNICODE),
        'reason' => $request->reason ?? '管理者による修正',
        'status' => 'approved',
    ]);

    $attendance->refresh();

    if ($attendance->clock_in && $attendance->clock_out) {
        $clockIn = $attendance->clock_in instanceof \Carbon\Carbon
            ? $attendance->clock_in
            : \Carbon\Carbon::parse($attendance->clock_in);

        $clockOut = $attendance->clock_out instanceof \Carbon\Carbon
            ? $attendance->clock_out
            : \Carbon\Carbon::parse($attendance->clock_out);

        $totalMinutes = $clockIn->diffInMinutes($clockOut);

        $breakMinutes = $attendance->breaks->sum(function ($b) {
            if ($b->break_start && $b->break_end) {
                $start = $b->break_start instanceof \Carbon\Carbon
                    ? $b->break_start
                    : \Carbon\Carbon::parse($b->break_start);
                $end = $b->break_end instanceof \Carbon\Carbon
                    ? $b->break_end
                    : \Carbon\Carbon::parse($b->break_end);

                return $start->diffInMinutes($end);
            }
            return 0;
        });

        $attendance->total_work = $totalMinutes - $breakMinutes;
    }

    $attendance->save();

    return redirect()->route('admin.attendances.detail', $attendance->id)
                    ->with('success', '勤怠情報を更新しました');
}


    public function request(Request $request)
    {
        $tab = $request->query('tab', 'pending');
        $user = auth()->user();

        if ($user->role === 'admin') {
            $pendingRequests = AttendanceRequest::with(['attendance.user'])
                            ->where('status', 'pending')
                            ->latest()
                            ->get();

            $approvedRequests = AttendanceRequest::with(['attendance.user'])
                            ->where('status', 'approved')
                            ->latest()
                            ->get();

        } else {

            $pendingRequests = AttendanceRequest::with(['attendance.user'])
                            ->whereHas('attendance', function ($query) use ($user) {
                                $query->where('user_id', $user->id);
                            })
                            ->where('status', 'pending')
                            ->latest()
                            ->get();

            $approvedRequests = AttendanceRequest::with(['attendance.user'])
                            ->whereHas('attendance', function ($query) use ($user) {
                                $query->where('user_id', $user->id);
                            })
                            ->where('status', 'approved')
                            ->latest()
                            ->get();
        }

        return view('attendance.attendance_request_list', compact('tab', 'pendingRequests', 'approvedRequests'));
    }

}
