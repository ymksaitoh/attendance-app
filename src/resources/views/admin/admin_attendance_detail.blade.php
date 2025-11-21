@extends('layouts.default')

@section('title','管理者勤怠詳細')

@section('css')
<link rel="stylesheet" href="{{ asset('/css/common/attendance_detail.css')  }}">
@endsection

@section('content')
<div class="attendance__detail-container">
    <h1 class="page__title-left">勤怠詳細</h1>

    <form action="{{ route('admin.attendances.update', $attendance->id) }}" method="POST">
        @csrf
        @method('PATCH')
        <div class="attendance__detail-wrapper">
            <div class="attendance__detail-item">
                <label>名前</label>
                <span class="user_name">{{ $attendance->user->name }}</span>
            </div>

            <div class="attendance__detail-item">
                <label>日付</label>
                <div class="attendance_date">
                    <span class="year">{{ \Carbon\Carbon::parse($attendance->work_date)->format('Y年') }}</span>
                    <span class="month_day">{{ \Carbon\Carbon::parse($attendance->work_date)->format('n月j日') }}</span>
                </div>
            </div>

            <div class="attendance__detail-item">
                <label>出勤・退勤</label>
                <div class="attendance_times">
                    <input type="text" name="clock_in" value="{{ old('clock_in', $displayDate['clock_in'] ? \Carbon\Carbon::parse($displayDate['clock_in'])->format('H:i') : '') }}">
                    〜
                    <input type="text" name="clock_out" value="{{ old('clock_out', $displayDate['clock_out'] ? \Carbon\Carbon::parse($displayDate['clock_out'])->format('H:i') : '') }}">
                </div>
                @error('clock_in')
                    <div class="error">{{ $message }}</div>
                @enderror
                @error('clock_out')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>

            @php
                $breaksRaw = is_string($attendance->breaks)
                    ? json_decode($attendance->breaks, true)
                    : $attendance->breaks;

                $breaksData = collect($breaksRaw ?? [])->map(function($b) {
                    return [
                        'break_start' => $b['break_start'] ?? null,
                        'break_end' => $b['break_end'] ?? null,
                    ];
                })->values();

                if ($breaksData->isEmpty() || $breaksData->last()['break_start'] !== null || $breaksData->last()['break_end'] !== null) {
                    $breaksData->push(['break_start' => null, 'break_end' => null]);
                }

                $breaksForLabels = $breaksData->all();
            @endphp

            @foreach ($breaksForLabels as $index => $break)
                <div class="attendance__detail-item break">
                    <label>
                        @if($index === 0)
                            休憩
                        @else
                            休憩 {{ $index + 1 }}
                        @endif
                    </label>

                    <div class="break_time">
                        <input type="text" name="break_start[]" value="{{ old('break_start.' . $index, $break['break_start'] ? \Carbon\Carbon::parse($break['break_start'])->format('H:i') : '') }}">
                        〜
                        <input type="text" name="break_end[]" value="{{ old('break_end.' . $index, $break['break_end'] ? \Carbon\Carbon::parse($break['break_start'])->format('H:i') : '') }}">
                    </div>
                    @foreach ($errors->get('break_start.*') as $msgList)
                        @foreach ($msgList as $msg)
                            <div class="error">{{ $msg }}</div>
                        @endforeach
                    @endforeach

                    @foreach ($errors->get('break_end.*') as $msgList)
                        @foreach ($msgList as $msg)
                            <div class="error">{{ $msg }}</div>
                        @endforeach
                    @endforeach
                </div>
            @endforeach

            <div class="attendance__detail-item reason">
                <label>備考</label>
                <textarea name="reason" rows="4" cols="40"></textarea>
            </div>
            @error('reason')
                <div class="error">{{ $message }}</div>
            @enderror
        </div>

        <div class="attendance__detail--btn-wrapper">
            @if($pendingRequest)
                <p>※承認待ちのため修正できません。</p>
            @else
                <button type="submit" class="btn btn__edit">修正</button>
            @endif
        </div>
    </form>
</div>
@endsection