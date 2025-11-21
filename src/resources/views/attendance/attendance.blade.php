@extends('layouts.default')

@section('title','勤怠登録')

@section('css')
<link rel="stylesheet" href="{{ asset('/css/common/attendance.css')  }}">
@endsection

@php
    $userId = auth()->id();
    $attendance = \App\Models\Attendance::where('user_id', $userId)
                    ->where('work_date', now()->toDateString())
                    ->first();
    $status = $attendance->status ?? '勤務外';
@endphp

@section('content')
<div class="attendance__container">
    <div class="attendance__status">
        <span id="current-status">{{ $status }}</span>
    </div>

    <div class="attendance__info">
        <div class="attendance__date">{{ \Carbon\Carbon::now()->format('Y年m月d日 (D) ') }}</div>
        <div class="attendance__time">{{ \Carbon\Carbon::now()->format('H:i') }}</div>
    </div>

    <form action="{{ route('attendances.store') }}" method="POST">
        @csrf
        <div class="attendance__buttons">
            @if($status === '勤務外')
                <button type="submit" name="action" value="clock_in" class="btn btn_attendance">出勤</button>
            @elseif($status === '出勤中')
                <button type="submit" name="action" value="clock_out" class="btn btn_attendance">退勤</button>
                <button type="submit" name="action" value="break_start" class="btn btn_rest">休憩入</button>
            @elseif($status === '休憩中')
                <button type="submit" name="action" value="break_end" class="btn btn_rest">休憩戻</button>
            @elseif($status === '退勤済')
                <p class="attendance__end-message">お疲れ様でした。</p>
            @endif
        </div>
    </form>
</div>

@endsection