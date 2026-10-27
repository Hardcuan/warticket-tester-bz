<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pengguna · WARTICKET TESTER</title>
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
            <div>
                <a class="brand" href="{{ route('admin.dashboard') }}">WARTICKET TESTER</a>
                <p class="text-primary fw-semibold small mb-1 mt-2">MANAJEMEN AKUN</p>
                <h1 class="h2 fw-bold mb-0">Pengguna & peran</h1>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-primary">Dashboard</a>
                <a href="{{ route('admin.tickets.index') }}" class="btn btn-outline-secondary">Event</a>
                <a href="{{ route('admin.top-ups.index') }}" class="btn btn-outline-secondary">Verifikasi saldo</a>
                <a href="{{ route('account.profile.edit') }}" class="btn btn-outline-secondary">Profil</a>
            </div>
        </header>

        @if (session('success'))
            <div class="alert alert-success" role="status">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger" role="alert">
                <ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        <section class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body p-4">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                    <div>
                        <h2 class="h5 fw-bold mb-1">Tambah akun</h2>
                        <p class="small text-muted mb-0">Admin baru mendapat akses penuh seperti admin yang membuat akun.</p>
                    </div>
                    <button class="btn btn-primary" type="button" data-bs-toggle="collapse" data-bs-target="#createUserForm" aria-expanded="false" aria-controls="createUserForm">+ Tambah pengguna/admin</button>
                </div>
                <div class="collapse mt-4" id="createUserForm">
                    <form method="POST" action="{{ route('admin.users.store') }}" class="row g-3">
                        @csrf
                        <div class="col-md-6"><label class="form-label" for="new_name">Nama</label><input id="new_name" name="name" class="form-control" value="{{ old('name') }}" required maxlength="255"></div>
                        <div class="col-md-6"><label class="form-label" for="new_email">Email</label><input id="new_email" name="email" type="email" class="form-control" value="{{ old('email') }}" required maxlength="255"></div>
                        <div class="col-md-6"><label class="form-label" for="new_password">Password awal</label><input id="new_password" name="password" type="password" class="form-control" required minlength="8" autocomplete="new-password"></div>
                        <div class="col-md-6"><label class="form-label" for="new_password_confirmation">Ulangi password</label><input id="new_password_confirmation" name="password_confirmation" type="password" class="form-control" required minlength="8" autocomplete="new-password"></div>
                        <div class="col-md-4"><label class="form-label" for="new_role">Peran</label><select id="new_role" name="role" class="form-select"><option value="user">Pengguna</option><option value="admin">Admin</option></select></div>
                        <div class="col-12"><button class="btn btn-primary" type="submit">Simpan akun</button></div>
                    </form>
                </div>
            </div>
        </section>

        <section class="card border-0 shadow-sm rounded-4">
            <div class="card-body border-bottom p-3 p-md-4">
                <form method="GET" action="{{ route('admin.users.index') }}" class="row g-2">
                    <div class="col"><label for="search" class="visually-hidden">Cari pengguna</label><input id="search" name="search" class="form-control" value="{{ $search }}" placeholder="Cari nama atau email"></div>
                    <div class="col-auto"><button class="btn btn-primary" type="submit">Cari</button></div>
                </form>
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light"><tr><th>Pengguna</th><th>Role</th><th>Status akun</th><th>Aktivitas</th><th>Pesanan</th><th>Slot dipesan</th><th>Email event</th><th>Aksi</th></tr></thead>
                    <tbody>
                        @forelse ($users as $user)
                            @php($isOnline = $user->is_active && ($user->last_seen_at?->greaterThanOrEqualTo(now()->subMinutes(5)) ?? false))
                            <tr>
                                <td class="min-w-200"><div class="fw-semibold">{{ $user->name }}</div><div class="small text-muted">{{ $user->email }}</div></td>
                                <td>
                                    @if ($user->id === auth()->id())
                                        <span class="badge text-bg-dark">{{ ucfirst($user->role) }} · Anda</span>
                                    @else
                                        <form method="POST" action="{{ route('admin.users.role', $user) }}" class="d-flex gap-1">
                                            @csrf
                                            @method('PATCH')
                                            <select name="role" class="form-select form-select-sm" aria-label="Ubah role {{ $user->name }}">
                                                <option value="user" @selected($user->role === 'user')>Pengguna</option>
                                                <option value="admin" @selected($user->role === 'admin')>Admin</option>
                                            </select>
                                            <button class="btn btn-sm btn-outline-primary" type="submit">Simpan</button>
                                        </form>
                                    @endif
                                </td>
                                <td><span class="badge {{ $user->is_active ? 'text-bg-success' : 'text-bg-danger' }}">{{ $user->is_active ? 'Aktif' : 'Dinonaktifkan' }}</span></td>
                                <td><span class="badge {{ $isOnline ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $isOnline ? 'Online' : 'Offline' }}</span><div class="small text-muted mt-1">{{ $user->last_seen_at?->diffForHumans() ?? 'Belum aktif' }}</div></td>
                                <td>{{ $user->orders_count }}</td>
                                <td>{{ number_format($user->tickets_ordered ?? 0) }}</td>
                                <td>{{ $user->email_notifications ? 'Ya' : 'Tidak' }}</td>
                                <td>
                                    @if ($user->id !== auth()->id())
                                        <div class="d-flex flex-wrap gap-1">
                                            <form method="POST" action="{{ route('admin.users.status', $user) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button class="btn btn-sm btn-outline-{{ $user->is_active ? 'warning' : 'success' }}" type="submit">{{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                                            </form>
                                            <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('Hapus akun ini? Akun dengan transaksi akan dinonaktifkan agar riwayat tetap utuh.')">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-sm btn-outline-danger" type="submit">Hapus</button>
                                            </form>
                                        </div>
                                    @else
                                        <span class="small text-muted">Akun aktif saat ini</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center text-muted py-5">Pengguna tidak ditemukan.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer bg-white">{{ $users->links() }}</div>
        </section>
    </main>

    <nav class="fixed-bottom bg-white border-top d-lg-none d-flex justify-content-around py-2">
        <a class="small text-decoration-none text-center" href="{{ route('admin.dashboard') }}">▦<br>Panel</a>
        <a class="small text-decoration-none text-center" href="{{ route('admin.tickets.index') }}">♫<br>Event</a>
        <a class="small text-decoration-none text-center fw-bold text-primary" href="{{ route('admin.users.index') }}">♙<br>User</a>
        <a class="small text-decoration-none text-center" href="{{ route('admin.top-ups.index') }}">◉<br>Saldo</a>
    </nav>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
