@extends('layouts.default')

@section('title','勤怠一覧')

@section('css')
<link rel="stylesheet" href="{{ asset('/css/common/attendance_list.css')  }}">
@endsection

@section('content')
<div class="attendance__list-container">
    <h1 class="page__title-left">勤怠一覧</h1>

    <div class="month__selector">
        <a href="{{ route('attendances.list', ['month' => $previousMonth->format('Y-m')]) }}" class="month_nav prev">&lt; 前月</a>
        <div class="month__display">
            <i class="fa-regular fa-calendar"></i>
            <span>{{ $month->format('Y/m') }}</span>
        </div>
        <a href="{{ route('attendances.list', ['month' => $nextMonth->format('Y-m')]) }}" class="month_nav next">翌月 &gt;</a>
    </div>

    <div class="attendance__table-container">
        <table class="attendance__table">
            <thead>
                <tr>
                    <th>日付</th>
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
                        <td>{{ $attendance->formattedDate() }}</td>
                        <td>{{ $attendance->clock_in ? \Carbon\Carbon::parse($attendance->clock_in)->format('H:i') : '-' }}</td>
                        <td>{{ $attendance->clock_out ? \Carbon\Carbon::parse($attendance->clock_out)->format('H:i') : '-'}}</td>
                        <td>{{ $attendance->total_break_time }}</td>
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
