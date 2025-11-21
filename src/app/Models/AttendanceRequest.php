<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'attendance_id',
        'request_type',
        'before_value',
        'after_value',
        'reason',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    public function getStatusLabelAttribute()
    {
        return match($this->status) {
            'pending' => '承認待ち',
            'approved' => '承認済み',
            default => $this->status,
        };
    }

}
