<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title')</title>
    <script src="https://kit.fontawesome.com/42694f25bf.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="{{ asset('/css/layouts/sanitize.css')  }}">
    <link rel="stylesheet" href="{{ asset('/css/layouts/default.css')  }}">
    @yield('css')
</head>

<body>
    <header class="default__header">
        <div class="header__inner">
            <div class="logo">
                <a href="{{ route('attendances.create') }}">
                    <img src="{{ asset('img/logo.svg') }}" alt="coachtechロゴ">
                </a>
            </div>

            @auth
                <nav class="nav_links">
                    <ul>
                        @can('admin')
                            <li><a href="{{ route('admin.attendances.list') }}">勤怠一覧</a></li>
                            <li><a href="{{ route('admin.staff.list') }}">スタッフ一覧</a></li>
                            <li><a href="{{ route('attendances.request.list') }}">申請一覧</a></li>
                            <li>
                                <form action="{{ route('admin.logout') }}" method="POST" style="display:inline;">
                                    @csrf
                                    <button type="submit">ログアウト</button>
                                </form>
                            </li>
                        @else
                            @php
                                $todayAttendance = \App\Models\Attendance::where('user_id', auth()->id())
                                    ->where('work_date', now()->toDateString())
                                    ->first();
                            @endphp

                            @if($todayAttendance && $todayAttendance->status === '退勤済')
                                <li><a href="{{ route('attendances.list') }}">今月の出勤一覧</a></li>
                                <li><a href="{{ route('attendances.request.list') }}">申請一覧</a></li>
                                <li>
                            @else
                                <li><a href="{{ route('attendances.create') }}">勤怠</a></li>
                                <li><a href="{{ route('attendances.list') }}">勤怠一覧</a></li>
                                <li><a href="{{ route('attendances.request.list') }}">申請</a></li>
                            @endif
                                <li>
                                    <form action="{{ route('logout') }}" method="POST" style="display:inline;">
                                        @csrf
                                        <button type="submit">ログアウト</button>
                                    </form>
                                </li>
                        @endcan
                    </ul>
                </nav>
            @endauth
        </div>
    </header>

    @yield('content')
</body>

</html>