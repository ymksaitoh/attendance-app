@extends('layouts.default')

@section('title','管理者スタッフ一覧')

@section('css')
<link rel="stylesheet" href="{{ asset('/css/admin/admin_staff_list.css')  }}">
@endsection

@section('content')
<div class="staff__list-container">
    <h1 class="page__title-left">
        スタッフ一欄
    </h1>

    <div class="staff__table-container">
        <table class="staff__table">
            <thead>
                <tr>
                    <th>名前</th>
                    <th>メールアドレス</th>
                    <th>月次勤怠</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($users as $user)
                    <tr>
                        <td>{{ $user->name }}</td>
                        <td>{{ $user->email }}</td>
                        <td>
                            <a href="{{ route('admin.attendances.staff', ['id' => $user->id]) }}" class="detail_link">詳細</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3"></td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection