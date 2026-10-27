<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard Admin · WARTICKET TESTER</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root { --panel-ink: #211735; --panel-purple: #5528b5; }
        body { color: var(--panel-ink); background: #f6f5fa; }
        .side-panel { background: #24183a; }
        .side-panel a { color: #ddd4ef; text-decoration: none; }
        .side-panel a:hover, .side-panel a.active { color: white; }
        .metric-card { border: 0; border-radius: 1rem; }
        .metric-value { color: var(--panel-purple); }
        @media (min-width: 992px) { .side-panel { min-height: calc(100vh - 2rem); } }
        @media (max-width: 991.98px) {
            body { padding-bottom: 5rem; }
            .side-panel .d-grid, .side-panel > .border-top { display: none !important; }
            .side-panel { padding: .75rem 1rem !important; }
            .side-panel a.fs-6 { margin-bottom: 0 !important; }
        }
    </style>
</head>
<body>
    <main class="container-fluid py-3 py-lg-4">
        <div class="row g-3 g-lg-4">
            <aside class="col-lg-2">
                <div class="side-panel rounded-4 p-3 p-lg-4 h-100">
                    <a href="{{ route('admin.dashboard') }}" class="fs-6 fw-bold d-block mb-4">WARTICKET TESTER<span class="text-danger">.</span></a>
                    <div class="d-grid gap-2">
                        <a class="active rounded p-2" href="{{ route('admin.dashboard') }}">▦ &nbsp; Ringkasan</a>
                        <a class="rounded p-2" href="{{ route('admin.tickets.index') }}">♫ &nbsp; Event war</a>
                        <a class="rounded p-2" href="{{ route('admin.users.index') }}">♙ &nbsp; Pengguna</a>
                        <a class="rounded p-2" href="{{ route('admin.top-ups.index') }}">◉ &nbsp; Verifikasi saldo</a>
                        <a class="rounded p-2" href="{{ route('account.password.edit') }}">⚙ &nbsp; Keamanan akun</a>
                        <a class="rounded p-2" href="{{ route('home') }}">↗ &nbsp; Lihat katalog</a>
                    </div>
                    <div class="border-top border-light border-opacity-25 mt-4 pt-3">
                        <div class="small text-white-50 mb-2">Masuk sebagai</div>
                        <div class="fw-semibold text-white text-break">{{ auth()->user()->name }}</div>
                        <form method="POST" action="{{ route('logout') }}" class="mt-3">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-light w-100">Keluar</button>
                        </form>
                    </div>
                </div>
            </aside>

            <section class="col-lg-10">
                <header class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
                    <div>
                        <p class="text-primary fw-semibold mb-1">PANEL PENGELOLA</p>
                        <h1 class="h2 fw-bold mb-0">Selamat datang, {{ auth()->user()->name }}</h1>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ route('admin.top-ups.index') }}" class="btn btn-outline-primary">Verifikasi saldo</a>
                        <a href="{{ route('admin.tickets.create') }}" class="btn btn-primary">+ Buat event war</a>
                    </div>
                </header>

                @if (session('success'))
                    <div class="alert alert-success" role="status">{{ session('success') }}</div>
                @endif

                <div class="row g-3 mb-4">
                    <div class="col-6 col-xl-3"><article class="card metric-card shadow-sm h-100"><div class="card-body"><div class="small text-muted">Pengguna</div><div class="metric-value fs-2 fw-bold">{{ number_format($metrics['users']) }}</div></div></article></div>
                    <div class="col-6 col-xl-3"><article class="card metric-card shadow-sm h-100"><div class="card-body"><div class="small text-muted">Aktif 5 menit</div><div class="metric-value fs-2 fw-bold">{{ number_format($metrics['active_users']) }}</div></div></article></div>
                    <div class="col-6 col-xl-3"><article class="card metric-card shadow-sm h-100"><div class="card-body"><div class="small text-muted">Semua event</div><div class="metric-value fs-2 fw-bold">{{ number_format($metrics['events']) }}</div></div></article></div>
                    <div class="col-6 col-xl-3"><article class="card metric-card shadow-sm h-100"><div class="card-body"><div class="small text-muted">Pesanan</div><div class="metric-value fs-2 fw-bold">{{ number_format($metrics['orders']) }}</div></div></article></div>
                    <div class="col-6 col-xl-3"><article class="card metric-card shadow-sm h-100"><div class="card-body"><div class="small text-muted">Slot dipesan</div><div class="metric-value fs-2 fw-bold">{{ number_format($metrics['tickets_sold']) }}</div></div></article></div>
                    <div class="col-6 col-xl-3"><article class="card metric-card shadow-sm h-100"><div class="card-body"><div class="small text-muted">Pendapatan dibayar</div><div class="metric-value fs-6 fw-bold">Rp {{ number_format($metrics['revenue'], 0, ',', '.') }}</div></div></article></div>
                    <div class="col-6 col-xl-3"><article class="card metric-card shadow-sm h-100"><div class="card-body"><div class="small text-muted">Pesanan kedaluwarsa</div><div class="metric-value fs-2 fw-bold">{{ number_format($metrics['expired_orders']) }}</div></div></article></div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-xl-6">
                        <section class="card border-0 shadow-sm h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div><h2 class="h5 fw-bold mb-1">Event paling diminati</h2><p class="small text-muted mb-0">Berdasarkan slot dalam pesanan</p></div>
                                    <a href="{{ route('admin.tickets.index') }}" class="small">Semua event</a>
                                </div>
                                @forelse ($popularTickets as $ticket)
                                    @php($ordered = (int) ($ticket->tickets_ordered ?? 0))
                                    @php($percentage = $popularTickets->max('tickets_ordered') > 0 ? min(100, (int) round($ordered / $popularTickets->max('tickets_ordered') * 100)) : 0)
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between gap-2 small mb-1">
                                            <span class="fw-semibold text-truncate">{{ $ticket->name }}</span>
                                            <span>{{ number_format($ordered) }} slot</span>
                                        </div>
                                        <div class="progress" role="progressbar" aria-label="Slot dipesan" aria-valuenow="{{ $percentage }}" aria-valuemin="0" aria-valuemax="100" style="height:8px">
                                            <div class="progress-bar" style="width:{{ $percentage }}%;background:#6d3ed1"></div>
                                        </div>
                                    </div>
                                @empty
                                    <p class="text-muted mb-0">Belum ada data event.</p>
                                @endforelse
                            </div>
                        </section>
                    </div>

                    <div class="col-xl-6">
                        <section class="card border-0 shadow-sm h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div><h2 class="h5 fw-bold mb-1">Peserta paling aktif</h2><p class="small text-muted mb-0">Peringkat berdasarkan jumlah slot yang dipesan</p></div>
                                    <a href="{{ route('admin.users.index') }}" class="small">Lihat pengguna</a>
                                </div>
                                @forelse ($frequentUsers as $user)
                                    <div class="d-flex justify-content-between align-items-center gap-2 border-bottom py-2">
                                        <div class="text-truncate"><div class="fw-semibold text-truncate">{{ $user->name }}</div><div class="small text-muted text-truncate">{{ $user->email }}</div></div>
                                        <div class="text-end text-nowrap"><div class="fw-semibold">{{ number_format($user->tickets_ordered ?? 0) }} slot</div><div class="small text-muted">{{ $user->orders_count }} pesanan</div></div>
                                    </div>
                                @empty
                                    <p class="text-muted mb-0">Belum ada peserta yang memesan.</p>
                                @endforelse
                            </div>
                        </section>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-xl-7">
                        <section class="card border-0 shadow-sm h-100">
                            <div class="card-body">
                                <h2 class="h5 fw-bold mb-3">Pesanan terbaru</h2>
                                <div class="table-responsive">
                                    <table class="table align-middle mb-0">
                                        <thead><tr><th>Peserta</th><th>Event</th><th>Jumlah</th><th>Status</th></tr></thead>
                                        <tbody>
                                            @forelse ($recentOrders as $order)
                                                <tr>
                                                    <td><div class="fw-semibold">{{ $order->user->name }}</div><div class="small text-muted">{{ $order->created_at->diffForHumans() }}</div></td>
                                                    <td>{{ $order->ticket_name }}</td>
                                                    <td>{{ $order->quantity }}</td>
                                                    <td>
                                                        @if ($order->late_gateway_payment_at)
                                                            <span class="badge text-bg-danger">Perlu pemeriksaan/refund</span>
                                                        @else
                                                            <span class="badge {{ $order->status === 'paid' ? 'text-bg-success' : ($order->status === 'expired' ? 'text-bg-secondary' : 'text-bg-warning') }}">{{ ucfirst($order->status) }}</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr><td colspan="4" class="text-center text-muted py-4">Belum ada pesanan.</td></tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </section>
                    </div>

                    <div class="col-xl-5">
                        <section class="card border-0 shadow-sm h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div><h2 class="h5 fw-bold mb-1">Sedang aktif</h2><p class="small text-muted mb-0">Aktivitas dalam 5 menit terakhir</p></div>
                                    <span class="badge text-bg-success">{{ $metrics['active_users'] }} online</span>
                                </div>
                                @forelse ($activeUsers as $user)
                                    <div class="d-flex align-items-center gap-2 border-bottom py-2">
                                        <span class="rounded-circle bg-success" style="width:9px;height:9px" aria-label="Online"></span>
                                        <div class="text-truncate"><div class="small fw-semibold">{{ $user->name }}</div><div class="small text-muted text-truncate">{{ $user->email }}</div></div>
                                    </div>
                                @empty
                                    <p class="text-muted mb-0">Belum ada akun aktif.</p>
                                @endforelse
                            </div>
                        </section>
                    </div>
                </div>
            </section>
        </div>
    </main>
    <nav class="fixed-bottom bg-white border-top d-lg-none d-flex justify-content-around py-2">
        <a class="small text-decoration-none text-center fw-bold text-primary" href="{{ route('admin.dashboard') }}">▦<br>Panel</a>
        <a class="small text-decoration-none text-center" href="{{ route('admin.tickets.index') }}">♫<br>Event</a>
        <a class="small text-decoration-none text-center" href="{{ route('admin.users.index') }}">♙<br>User</a>
        <a class="small text-decoration-none text-center" href="{{ route('admin.top-ups.index') }}">◉<br>Saldo</a>
    </nav>
</body>
</html>
