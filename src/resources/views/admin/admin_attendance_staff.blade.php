@extends('layouts.default')

@section('title','勤怠一覧')

@section('css')
<link rel="stylesheet" href="{{ asset('/css/common/attendance_list.css')  }}">
@endsection

@section('content')
<div class="attendance__list-container">
    <h1 class="page__title-left">{{ $user->name }}さんの勤怠</h1>

    <div class="month__selector">
        <a href="{{ route('admin.attendances.staff', ['id' => $user->id, 'month' => $previousMonth]) }}" class="month_nav prev">&lt; 前月</a>
        <div class="month__display">
            <i class="fa-regular fa-calendar"></i>
            <span>{{ \Carbon\Carbon::createFromFOrmat('Y-m', $month)->format('Y/m') }}</span>
        </div>
        <a href="{{ route('admin.attendances.staff', ['id' => $user->id, 'month' => $nextMonth]) }}" class="month_nav next">翌月 &gt;</a>
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
                        <td>{{ $attendance->total_break_time }}<br>
                        </td>
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

    <div class="csv-export-btn-container">
        <form action="{{ route('admin.attendances.export') }}" method="GET">
            <input type="hidden" name="user_id" value="{{ $user->id ?? '' }}">
            <input type="hidden" name="month" value="{{ \Carbon\Carbon::createFromFormat('Y-m',$month)->format('Y-m') }}">
            <button type="submit" class="csv-export-btn">CSV出力</button>
        </form>
    </div>
</div>

@endsection