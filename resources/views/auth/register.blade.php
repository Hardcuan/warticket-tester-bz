@extends('layouts.auth')

@section('title', 'Daftar')
@section('heading', 'Buat akun peserta')

@section('content')
    <form method="POST" action="{{ route('register.store') }}">
        @csrf
        <div class="mb-3">
            <label class="form-label" for="nama_lengkap">Nama</label>
            <input id="nama_lengkap" type="text" name="name" class="form-control" value="{{ old('name') }}" autocomplete="name" required autofocus>
        </div>
        <div class="mb-3">
            <label class="form-label" for="email">Email</label>
            <input id="email" type="email" name="email" class="form-control" value="{{ old('email') }}" autocomplete="email" required>
        </div>
        <div class="mb-3">
            <label class="form-label" for="password">Kata sandi (minimal 8 karakter)</label>
            <input id="password" type="password" name="password" class="form-control" autocomplete="new-password" required>
        </div>
        <div class="mb-3">
            <label class="form-label" for="password_confirmation">Ulangi kata sandi</label>
            <input id="password_confirmation" type="password" name="password_confirmation" class="form-control" autocomplete="new-password" required>
        </div>
        <div class="form-check mb-3">
            <input id="email_notifications" type="checkbox" name="email_notifications" value="1" class="form-check-input">
            <label class="form-check-label" for="email_notifications">Kirim email saat event war baru diumumkan</label>
        </div>
        <button type="submit" class="btn btn-primary w-100">Daftar</button>
    </form>
    <p class="text-center mt-3 mb-0">Sudah punya akun? <a href="{{ route('login') }}">Masuk</a></p>
@endsection
