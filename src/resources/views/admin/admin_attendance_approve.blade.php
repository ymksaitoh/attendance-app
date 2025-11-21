@extends('layouts.default')

@section('title', '管理者申請詳細')

@section('css')
<link rel="stylesheet" href="{{ asset('/css/common/attendance_detail.css')  }}">
@endsection

@section('content')
<div class="attendance__detail-container">
    <h1 class="page__title-left">勤怠詳細</h1>

    <div class="attendance__detail-wrapper">
        <div class="attendance__detail-item">
            <label>名前</label>
            <span class="user_name">{{ $request->user->name }}</span>
        </div>

        <div class="attendance__detail-item">
            <label>日付</label>
            <div class="attendance_date">
                <span class="year">{{ \Carbon\Carbon::parse($request->attendance->work_date)->format('Y年') }}</span>
                <span class="month_day">{{ \Carbon\Carbon::parse($request->attendance->work_date)->format('n月j日') }}</span>
            </div>
        </div>

        <div class="attendance__detail-item">
            <label>出勤・退勤</label>
            <div class="attendance_times">
                <span>{{ $request->attendance->clock_in ? \Carbon\Carbon::parse($request->attendance->clock_in)->format('H:i') : '-' }}</span>
                〜
                <span>{{ $request->attendance->clock_out ? \Carbon\Carbon::parse($request->attendance->clock_out)->format('H:i') : '-' }}</span>
            </div>
        </div>

        @foreach ($request->attendance->breaks as $index => $break)
            <div class="attendance__detail-item break">
                <label>休憩 {{ $index + 1 }}</label>
                <div class="break_time">
                    <span>{{ $break->break_start ? \Carbon\Carbon::parse($break->break_start)->format('H:i') : '-' }}</span>
                    〜
                    <span>{{ $break->break_end ? \Carbon\Carbon::parse($break->break_end)->format('H:i') : '-' }}</span>
                </div>
            </div>
        @endforeach

        <div class="attendance__detail-item reason">
            <label>備考</label>
            <p>{{ $request->reason }}</p>
        </div>
    </div>

    <div class="attendance__detail--btn-wrapper">
        @if($request->status === 'pending')
            <form action="{{ route('admin.attendances.approveUpdate', ['attendance_correct_request_id' => $request->id]) }}" method="POST">
                @csrf
                @method('PATCH')
                <button type="submit" class="btn btn__approve">承認</button>
            </form>
        @else
            <button class="btn btn__approved" disabled>承認済み</button>
        @endif
    </div>
</div>
@endsection