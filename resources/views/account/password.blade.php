<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Keamanan Akun · WARTICKET TESTER</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <main class="container py-4 py-lg-5">
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <section class="card border-0 shadow-sm">
                    <div class="card-body p-4 p-md-5">
                        <a href="{{ route('home') }}" class="small fw-bold text-decoration-none">WARTICKET TESTER</a>
                        <p class="text-primary fw-semibold mt-2 mb-1">Keamanan akun</p>
                        <h1 class="h3 fw-bold mb-4">Ganti password</h1>
                        @if (session('success'))
                            <div class="alert alert-success" role="status">{{ session('success') }}</div>
                        @endif
                        @if ($errors->any())
                            <div class="alert alert-danger" role="alert">
                                <ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                            </div>
                        @endif
                        <form method="POST" action="{{ route('account.password.update') }}" class="d-grid gap-3">
                            @csrf
                            @method('PUT')
                            <div><label for="current_password" class="form-label">Password saat ini</label><input id="current_password" type="password" name="current_password" class="form-control" autocomplete="current-password" required></div>
                            <div><label for="password" class="form-label">Password baru</label><input id="password" type="password" name="password" class="form-control" minlength="8" autocomplete="new-password" required><div class="form-text">Minimal 8 karakter dan berbeda dari password sekarang.</div></div>
                            <div><label for="password_confirmation" class="form-label">Ulangi password baru</label><input id="password_confirmation" type="password" name="password_confirmation" class="form-control" autocomplete="new-password" required></div>
                            <button type="submit" class="btn btn-primary">Simpan password</button>
                        </form>
                        <div class="d-flex flex-wrap gap-2 mt-3">
                            <a href="{{ route('account.profile.edit') }}" class="btn btn-outline-primary">Profil</a>
                            <a href="{{ route('account.wallet') }}" class="btn btn-outline-secondary">Saldo</a>
                            <a href="{{ auth()->user()->isAdmin() ? route('admin.dashboard') : route('home') }}" class="btn btn-link">Kembali</a>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </main>
</body>
</html>
