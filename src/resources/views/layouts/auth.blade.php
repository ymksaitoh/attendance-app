<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title')</title>
    <link rel="stylesheet" href="{{ asset('/css/layouts/sanitize.css') }}">
    <link rel="stylesheet" href="{{ asset('/css/layouts/default.css') }}">
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
        </div>
    </header>

    @yield('content')
</body>
</html>
