<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kelola Event · WARTICKET TESTER</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <main class="container py-4 py-lg-5">
        <header class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
            <div>
                <a href="{{ route('admin.dashboard') }}" class="text-decoration-none fw-bold">WARTICKET TESTER</a>
                <p class="text-primary fw-semibold mb-1">Panel admin</p>
                <h1 class="h2 mb-0">Kelola Event War Tiket</h1>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-primary">Dashboard</a>
                <a href="{{ route('admin.tickets.create') }}" class="btn btn-primary">Buat event</a>
                <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">Data pengguna</a>
                <a href="{{ route('admin.top-ups.index') }}" class="btn btn-outline-secondary">Saldo</a>
                <a href="{{ route('account.password.edit') }}" class="btn btn-outline-secondary">Akun</a>
                <a href="{{ route('home') }}" class="btn btn-outline-secondary">Lihat katalog</a>
            </div>
        </header>

        @if (session('success'))
            <div class="alert alert-success" role="status">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger" role="alert">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <section class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Event</th>
                            <th>Harga</th>
                            <th>Stok tersisa</th>
                            <th>Waktu penjualan</th>
                            <th>Status</th>
                            <th><span class="visually-hidden">Aksi</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($tickets as $ticket)
                            <tr>
                                <td>
                                    <strong>{{ $ticket->name }}</strong>
                                    <div class="small text-muted">{{ $ticket->orders_count }} pesanan</div>
                                </td>
                                <td>Rp {{ number_format($ticket->price, 0, ',', '.') }}</td>
                                <td>{{ $ticket->stock }}</td>
                                <td class="small">
                                    {{ $ticket->sales_starts_at?->timezone(config('app.timezone'))->format('d-m-Y H:i') }}
                                    <br>s/d {{ $ticket->sales_ends_at?->timezone(config('app.timezone'))->format('d-m-Y H:i') }}
                                </td>
                                <td>
                                    @if ($ticket->is_published)
                                        <span class="badge text-bg-success">Terbit</span>
                                    @else
                                        <span class="badge text-bg-secondary">Draft</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <a href="{{ route('admin.tickets.edit', $ticket) }}" class="btn btn-sm btn-outline-primary">Ubah</a>
                                        <form method="POST" action="{{ route('admin.tickets.destroy', $ticket) }}" onsubmit="return confirm('Hapus event ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Hapus</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-5">Belum ada event. Buat event pertama Anda.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($tickets->hasPages())
                <div class="card-footer bg-white">{{ $tickets->links() }}</div>
            @endif
        </section>
    </main>
    <nav class="fixed-bottom bg-white border-top d-lg-none d-flex justify-content-around py-2">
        <a class="small text-decoration-none text-center" href="{{ route('admin.dashboard') }}">▦<br>Panel</a>
        <a class="small text-decoration-none text-center fw-bold text-primary" href="{{ route('admin.tickets.index') }}">♫<br>Event</a>
        <a class="small text-decoration-none text-center" href="{{ route('admin.users.index') }}">♙<br>User</a>
        <a class="small text-decoration-none text-center" href="{{ route('admin.top-ups.index') }}">◉<br>Saldo</a>
    </nav>
</body>
</html>
