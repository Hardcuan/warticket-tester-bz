<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verifikasi saldo · WARTICKET TESTER</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f5f4fa; color: #211735; }
        .brand { color: #5528b5; text-decoration: none; font-weight: 800; }
        @media (max-width: 991.98px) { body { padding-bottom: 5rem; } }
    </style>
</head>
<body>
    <main class="container-fluid container-xl py-4 py-lg-5">
        <header class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
            <div><a href="{{ route('admin.dashboard') }}" class="brand">WARTICKET TESTER</a><p class="text-primary fw-semibold small mt-2 mb-1">KEUANGAN</p><h1 class="h2 fw-bold mb-0">Verifikasi isi saldo</h1></div>
            <div class="d-flex gap-2"><a href="{{ route('admin.dashboard') }}" class="btn btn-outline-primary">Dashboard</a><a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">Pengguna</a><a href="{{ route('account.profile.edit') }}" class="btn btn-outline-secondary">Profil</a></div>
        </header>
        @if (session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif
        @if (session('error'))<div class="alert alert-warning" role="alert">{{ session('error') }}</div>@endif
        @if ($errors->any())<div class="alert alert-danger" role="alert"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        <div class="alert alert-info">Periksa mutasi bank di luar aplikasi sebelum menyetujui. Pengajuan yang disetujui menambah saldo tepat satu kali.</div>
        <section class="card border-0 shadow-sm rounded-4">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light"><tr><th>Pengguna</th><th>Nominal</th><th>Referensi transfer</th><th>Diajukan</th><th>Status</th><th>Aksi admin</th></tr></thead>
                    <tbody>
                        @forelse ($requests as $topUp)
                            <tr>
                                <td><strong>{{ $topUp->user->name }}</strong><div class="small text-muted">{{ $topUp->user->email }}</div></td>
                                <td class="text-nowrap">Rp {{ number_format($topUp->amount, 0, ',', '.') }}</td>
                                <td>{{ $topUp->transfer_reference }}</td>
                                <td class="text-nowrap">{{ $topUp->created_at->timezone(config('app.timezone'))->format('d-m-Y H:i') }}</td>
                                <td><span class="badge {{ $topUp->status === 'approved' ? 'text-bg-success' : ($topUp->status === 'rejected' ? 'text-bg-danger' : 'text-bg-warning') }}">{{ ucfirst($topUp->status) }}</span>
                                    @if ($topUp->reviewer)<div class="small text-muted">oleh {{ $topUp->reviewer->name }}</div>@endif
                                </td>
                                <td>
                                    @if ($topUp->status === 'pending')
                                        <div class="d-flex flex-wrap gap-2">
                                            <form method="POST" action="{{ route('admin.top-ups.approve', $topUp) }}">@csrf<button class="btn btn-sm btn-success" type="submit">Verifikasi & setujui</button></form>
                                            <form method="POST" action="{{ route('admin.top-ups.reject', $topUp) }}" class="d-flex gap-1">@csrf<input name="admin_note" class="form-control form-control-sm" maxlength="1000" placeholder="Alasan (opsional)" aria-label="Alasan penolakan"><button class="btn btn-sm btn-outline-danger" type="submit">Tolak</button></form>
                                        </div>
                                    @else
                                        <span class="small text-muted">{{ $topUp->admin_note }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-5">Belum ada permintaan isi saldo.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer bg-white">{{ $requests->links() }}</div>
        </section>
    </main>
    <nav class="fixed-bottom bg-white border-top d-lg-none d-flex justify-content-around py-2">
        <a class="small text-decoration-none text-center" href="{{ route('admin.dashboard') }}">▦<br>Panel</a>
        <a class="small text-decoration-none text-center" href="{{ route('admin.tickets.index') }}">♫<br>Event</a>
        <a class="small text-decoration-none text-center" href="{{ route('admin.users.index') }}">♙<br>User</a>
        <a class="small text-decoration-none text-center fw-bold text-primary" href="{{ route('admin.top-ups.index') }}">◉<br>Saldo</a>
    </nav>
</body>
</html>
