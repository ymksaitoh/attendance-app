@extends('layouts.default')

@section('title','管理者勤怠一覧')

@section('css')
<link rel="stylesheet" href="{{ asset('/css/common/attendance_list.css')  }}">
@endsection

@section('content')
<div class="attendance__list-container">
    <h1 class="page__title-left">
        {{ $targetDate->format('Y年m月d日') }}の勤怠
    </h1>

    <div class="month__selector">
        <a href="{{ route('admin.attendances.list', ['date' => $previousDay->format('Y-m-d')]) }}" class="month_nav prev">&lt; 前日</a>
        <div class="month__display">
            <i class="fa-regular fa-calendar"></i>
            <span>{{ $targetDate->format('Y/m/d') }}</span>
        </div>
        <a href="{{ route('admin.attendances.list', ['date' => $nextDay->format('Y-m-d')]) }}" class="month_nav next">翌日 &gt;</a>
    </div>

    <div class="attendance__table-container">
        <table class="attendance__table">
            <thead>
                <tr>
                    <th>名前</th>
                    <th>出勤</th>
                    <th>退勤</th>
                    <th>休憩</th>
                    <th>合計</th>
                    <th>詳細</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($attendances as $attendance)
                    <tr>
                        <td>{{ $attendance->user->name }}</td>
                        <td>{{ $attendance->clock_in ? \Carbon\Carbon::parse($attendance->clock_in)->format('H:i') : '-' }}</td>
                        <td>{{ $attendance->clock_out ? \Carbon\Carbon::parse($attendance->clock_out)->format('H:i') : '-'}}</td>
                        <td>{{ ($attendance->total_break_time) }}</td>
                        <td>{{ $attendance->formattedTotalWork() }}</td>
                        <td>
                            <a href="{{ route('attendances.detail', $attendance->id) }}" class="detail_link">詳細</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6"></td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
