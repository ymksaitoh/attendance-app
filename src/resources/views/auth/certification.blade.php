@extends('layouts.default')

@section('css')
<link rel="stylesheet" href="{{ asset('css/auth/certification.css') }}">
@endsection

@section('content')
<div class="certification__form">
    <p class="certification__form-message">
        登録していただいたメールアドレスに認証メールを送付しました。<br>
        メール認証を完了して下さい
    </p>

    <div class="certification__form-btn">
        <form action="{{ route('verification.send') }}" method="post">
            @csrf
            <input type="submit" class="certification__form-btn" value="認証はこちらから">
        </form>
    </div>

    <div class="certification__form-mail">
        <form action="{{ route('verification.send') }}" method="post">
            @csrf
            <input type="submit" class="certification__form-btn resend" value="認証メールを再送する">
        </form>
    </div>
</div>
@endsection