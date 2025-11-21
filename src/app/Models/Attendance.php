<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'work_date',
        'clock_in',
        'clock_out',
        'status',
        'total_work',
    ];

    protected $casts = [
        'clock_in' => 'datetime',
        'clock_out' => 'datetime',
        'work_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function breaks()
    {
        return $this->hasMany(BreakTime::class, 'attendance_id');
    }

    public function calculateTotalWork()
    {
        if (!$this->clock_in || !$this->clock_out) {
            return 0;
        }

        $clockIn = Carbon::parse($this->clock_in);
        $clockOut = Carbon::parse($this->clock_out);

        $totalMinutes = $clockIn->diffInMinutes($clockOut);

        foreach ($this->breaks as $break) {
            if ($break->break_start && $break->break_end) {
                $totalMinutes -= Carbon::parse($break->break_start)
                    ->diffInMinutes(Carbon::parse($break->break_end));
            }
        }

        $this->total_work = $totalMinutes;
        return $totalMinutes;
    }

    public function requests()
    {
        return $this->hasMany(AttendanceRequest::class);
    }

    public function formattedTotalWork()
    {
        if ($this->total_work === null) {
            return '-';
        }

        $hours = floor($this->total_work / 60);
        $minutes = $this->total_work % 60;

        return sprintf('%d:%02d', $hours, $minutes);
    }

    public function formattedDate()
    {
        return Carbon::parse($this->work_date)->locale('ja')->translatedFormat('Y/m/d (D)');
    }

    public function previousDate()
    {
        return Carbon::parse($this->work_date)->subDay()->format('Y-m-d');
    }

    public function nextDate()
    {
        return Carbon::parse($this->work_date)->addDay()->format('Y-m-d');
    }

    public function getTotalBreakTimeAttribute()
    {
        $total = $this->display_breaks->sum('total_break');

        $hours = floor($total / 60);
        $minutes = $total % 60;

        return sprintf('%d:%02d', $hours, $minutes);
    }

    public function getDisplayClockInAttribute()
    {
        $pendingRequest = $this->requests()->where('status', 'pending')->latest()->first();

        if ($pendingRequest && isset($pendingRequest->after_value)) {
            $after = json_decode($pendingRequest->after_value, true);
            return isset($after['clock_in'])
                ? \Carbon\Carbon::parse($after['clock_in'])->setTimezone('Asia/Tokyo')
                : ($this->clock_in ? \Carbon\Carbon::parse($this->clock_in)->setTimezone('Asia/Tokyo') : null);
        }

        return $this->clock_in ? Carbon::parse($this->clock_in) : null;
    }

    public function getDisplayClockOutAttribute()
    {
        $pendingRequest = $this->requests()->where('status', 'pending')->latest()->first();

        if ($pendingRequest && isset($pendingRequest->after_value)) {
            $after = json_decode($pendingRequest->after_value, true);
            return isset($after['clock_out'])
                ? \Carbon\Carbon::parse($after['clock_out'])->setTimezone('Asia/Tokyo')
                : ($this->clock_out ? \Carbon\Carbon::parse($this->clock_out)->setTImezone('Asia/Tokyo') :null);
        }

        return $this->clock_out ? Carbon::parse($this->clock_out) : null;
    }


    public function getDisplayBreaksAttribute()
    {
        $pendingRequest = $this->requests()->where('status', 'pending')->latest()->first();

        if ($pendingRequest && isset($pendingRequest->after_value)) {
            $after = json_decode($pendingRequest->after_value, true);

            if (!empty($after['breaks'])) {
                return collect($after['breaks'])->map(function ($b) {
                    $breakStartRaw = $b['break_start'] ?? null;
                    $breakEndRaw = $b['break_end'] ?? null;

                    $start = $breakStartRaw ? \Carbon\Carbon::parse($breakStartRaw)->setTimezone('Asia/Tokyo') : null;
                    $end = $breakEndRaw ? \Carbon\Carbon::parse($breakEndRaw)->setTimezone('Asia/Tokyo') : null;

                    return [
                        'break_start' => $start,
                        'break_end' => $end,
                        'total_break' => ($start && $end) ? $start->diffInMinutes($end) : 0,
                    ];
                });

            }
        }

        return $this->breaks->map(function ($b) {
            $start = !empty($b->break_start)
                ? \Carbon\Carbon::parse($b->break_start)->setTimezone('Asia/Tokyo')
                : null;
            $end = !empty($b->break_end)
                ? \Carbon\Carbon::parse($b->break_end)->setTimezone('Asia/Tokyo')
                : null;

            return [
                'break_start' => $start,
                'break_end' => $end,
                'total_break' => ($start && $end) ? $start->diffInMinutes($end) : 0,
            ];
        });
    }

}
