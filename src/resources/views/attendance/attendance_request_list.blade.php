@extends('layouts.default')

@section('title','申請一覧')

@section('css')
<link rel="stylesheet" href="{{ asset('/css/common/attendance_request_list.css')  }}">
@endsection

@section('content')
<div class="requests__list-container">
    <h1 class="page__title-left">申請一覧</h1>

    <div class="requests">
        <ul class="requests__list">
            <li class="{{ $tab === 'pending' ? 'active' : '' }}">
                <a href="{{ route( 'attendances.request.list', ['tab' => 'pending']) }}">承認待ち</a>
            </li>
            <li class="{{ $tab === 'approved' ? 'active' : '' }}">
                <a href="{{ route( 'attendances.request.list', ['tab'=>'approved']) }}">承認済み</a>
            </li>
        </ul>
        <div class="requests__tab-underline"></div>
    </div>

    <div class="requests__table-container">
        <table class="requests__table">
            <thead>
                <tr>
                    <th>状態</th>
                    <th>名前</th>
                    <th>対象日時</th>
                    <th>申請理由</th>
                    <th>申請日時</th>
                    <th>詳細</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $requests = $tab === 'pending' ? $pendingRequests : $approvedRequests;
                @endphp

                @foreach ($requests as $request)
                <tr>
                    <td>{{ $request->status_label }}</td>
                    <td>{{ $request->user->name }}</td>
                    <td>{{ \Carbon\Carbon::parse($request->attendance->work_date)->format('Y/m/d') }}</td>
                    <td>{{ $request->reason }}</td>
                    <td>{{ \Carbon\Carbon::parse($request->created_at)->format('Y/m/d')}}</td>
                    <td>
                        @if(Auth::user()->role === 'admin')
                            <a href="{{ route('admin.attendances.approve', ['attendance_correct_request_id' => $request->id]) }}" class="detail_link">詳細</a>
                        @else
                            <a href="{{ route('attendances.detail', ['id' => $request->attendance->id]) }}" class="detail_link">詳細</a>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection