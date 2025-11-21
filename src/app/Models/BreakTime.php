<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BreakTime extends Model
{
    use HasFactory;

    protected $table = 'breaks';
    protected $fillable = [
        'attendance_id',
        'break_start',
        'break_end',
        'total_break',
    ];

    protected $casts = [
        'break_start' => 'datetime',
        'break_end' => 'datetime',
    ];

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    public function formattedTotalBreak()
    {
        $total = $this->total_break ?? 0;
        $hours = floor($total / 60);
        $minutes = $total % 60;

        return sprintf('%d:%02d', $hours, $minutes);
    }

}
