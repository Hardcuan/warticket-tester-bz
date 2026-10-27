<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Profil akun · WARTICKET TESTER</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <main class="container py-4 py-lg-5">
        <div class="row justify-content-center">
            <div class="col-lg-7">
                <a href="{{ route('home') }}" class="text-decoration-none fw-bold">WARTICKET TESTER</a>
                <section class="card border-0 shadow-sm rounded-4 mt-3">
                    <div class="card-body p-4 p-md-5">
                        <p class="text-primary fw-semibold mb-1">PENGATURAN AKUN</p>
                        <h1 class="h2 fw-bold mb-4">Informasi pengguna</h1>
                        @if (session('success'))
                            <div class="alert alert-success" role="status">{{ session('success') }}</div>
                        @endif
                        @if ($errors->any())
                            <div class="alert alert-danger" role="alert">
                                <ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('account.profile.update') }}" class="d-grid gap-3">
                            @csrf
                            @method('PUT')
                            <div>
                                <label for="nama_lengkap" class="form-label">Nama lengkap</label>
                                <input id="nama_lengkap" name="name" class="form-control" value="{{ old('name', auth()->user()->name) }}" required maxlength="255">
                            </div>
                            <div>
                                <label for="email" class="form-label">Email</label>
                                <input id="email" name="email" type="email" class="form-control" value="{{ old('email', auth()->user()->email) }}" required maxlength="255">
                            </div>
                            <div>
                                <label for="nomor_telepon" class="form-label">Nomor telepon <span class="text-muted">(opsional)</span></label>
                                <input id="nomor_telepon" name="phone" type="tel" class="form-control" value="{{ old('phone', auth()->user()->phone) }}" maxlength="30">
                            </div>
                            <div class="form-check">
                                <input id="email_notifications" name="email_notifications" type="checkbox" value="1" class="form-check-input" @checked(old('email_notifications', auth()->user()->email_notifications))>
                                <label for="email_notifications" class="form-check-label">Terima pengumuman event melalui email</label>
                            </div>
                            <button class="btn btn-primary btn-lg" type="submit">Simpan informasi</button>
                        </form>
                        <div class="d-flex flex-wrap gap-2 mt-4">
                            <a href="{{ route('account.wallet') }}" class="btn btn-outline-primary">Saldo akun</a>
                            <a href="{{ route('account.password.edit') }}" class="btn btn-outline-secondary">Ganti password</a>
                            <a href="{{ route('home') }}" class="btn btn-link">Kembali ke katalog</a>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </main>
</body>
</html>
