@extends('layouts.auth')

@section('title', 'Masuk')
@section('heading', 'Masuk ke akun')

@section('content')
    <form method="POST" action="{{ route('login.store') }}">
        @csrf
        <div class="mb-3">
            <label class="form-label" for="email">Email</label>
            <input id="email" type="email" name="email" class="form-control" value="{{ old('email') }}" autocomplete="email" required autofocus>
        </div>
        <div class="mb-3">
            <label class="form-label" for="password">Kata sandi</label>
            <input id="password" type="password" name="password" class="form-control" autocomplete="current-password" required>
        </div>
        <div class="form-check mb-3">
            <input id="remember" type="checkbox" name="remember" value="1" class="form-check-input">
            <label class="form-check-label" for="remember">Ingat saya di perangkat ini</label>
        </div>
        <button type="submit" class="btn btn-primary w-100">Masuk</button>
    </form>
    <p class="text-center mt-3 mb-0">Belum punya akun? <a href="{{ route('register') }}">Daftar</a></p>
@endsection
