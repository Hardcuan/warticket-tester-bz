<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Saldo akun · WARTICKET TESTER</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <main class="container py-4 py-lg-5">
        <header class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
            <div>
                <a href="{{ route('home') }}" class="text-decoration-none fw-bold">WARTICKET TESTER</a>
                <h1 class="h2 fw-bold mt-2 mb-0">Saldo akun</h1>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('account.profile.edit') }}" class="btn btn-outline-secondary">Profil</a>
                <a href="{{ route('home') }}" class="btn btn-outline-primary">Katalog event</a>
            </div>
        </header>

        @if (session('success'))
            <div class="alert alert-success" role="status">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-warning" role="alert">{{ session('error') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger" role="alert">
                <ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        <div class="row g-4">
            <div class="col-lg-5">
                <section class="card border-0 shadow-sm rounded-4 h-100">
                    <div class="card-body p-4">
                        <p class="text-muted mb-1">SALDO TERSEDIA</p>
                        <div class="display-6 fw-bold text-primary mb-4">Rp {{ number_format($wallet->balance, 0, ',', '.') }}</div>
                        <h2 class="h5 fw-bold">Ajukan isi saldo</h2>
                        <p class="small text-muted">Transfer bank diverifikasi manual. Saldo hanya dikreditkan setelah admin mencocokkan transfer.</p>
                        @if ($transferInstructions['bank_name'] && $transferInstructions['account_name'] && $transferInstructions['account_number'])
                            <div class="alert alert-light border">
                                <div class="small text-muted">TRANSFER KE REKENING RESMI</div>
                                <strong>{{ $transferInstructions['bank_name'] }}</strong>
                                <div>{{ $transferInstructions['account_number'] }}</div>
                                <div>a.n. {{ $transferInstructions['account_name'] }}</div>
                            </div>
                        @else
                            <div class="alert alert-warning small">
                                Informasi rekening belum disetel. Admin perlu mengisi WALLET_BANK_NAME, WALLET_BANK_ACCOUNT_NAME, dan WALLET_BANK_ACCOUNT_NUMBER di .env sebelum menerima transfer pengguna.
                            </div>
                        @endif
                        <form method="POST" action="{{ route('account.wallet.top-ups.store') }}" class="d-grid gap-3">
                            @csrf
                            <div>
                                <label for="amount" class="form-label">Nominal (Rp 10.000–10.000.000)</label>
                                <input id="amount" name="amount" type="number" min="10000" max="10000000" step="1000" class="form-control" value="{{ old('amount') }}" required>
                            </div>
                            <div>
                                <label for="transfer_reference" class="form-label">Referensi transfer</label>
                                <input id="transfer_reference" name="transfer_reference" class="form-control" maxlength="120" value="{{ old('transfer_reference') }}" placeholder="Nama pengirim / nomor referensi" required>
                            </div>
                            <button class="btn btn-primary" type="submit">Kirim permintaan verifikasi</button>
                        </form>
                    </div>
                </section>
            </div>

            <div class="col-lg-7">
                <section class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-body">
                        <h2 class="h5 fw-bold">Riwayat saldo</h2>
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead><tr><th>Waktu</th><th>Aktivitas</th><th>Perubahan</th><th>Saldo</th></tr></thead>
                                <tbody>
                                    @forelse ($transactions as $transaction)
                                        <tr>
                                            <td class="small text-nowrap">{{ $transaction->created_at->timezone(config('app.timezone'))->format('d-m-Y H:i') }}</td>
                                            <td>{{ $transaction->description }}</td>
                                            <td class="{{ $transaction->type === 'order_payment' ? 'text-danger' : 'text-success' }}">
                                                {{ $transaction->type === 'order_payment' ? '−' : '+' }}Rp {{ number_format($transaction->amount, 0, ',', '.') }}
                                            </td>
                                            <td>Rp {{ number_format($transaction->balance_after, 0, ',', '.') }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="4" class="text-center text-muted py-4">Belum ada transaksi saldo.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        {{ $transactions->links() }}
                    </div>
                </section>

                <section class="card border-0 shadow-sm rounded-4">
                    <div class="card-body">
                        <h2 class="h5 fw-bold">Permintaan isi saldo</h2>
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead><tr><th>Diajukan</th><th>Referensi transfer</th><th>Nominal</th><th>Status</th></tr></thead>
                                <tbody>
                                    @forelse ($topUpRequests as $topUp)
                                        <tr>
                                            <td class="text-nowrap">{{ $topUp->created_at->timezone(config('app.timezone'))->format('d-m-Y H:i') }}</td>
                                            <td>{{ $topUp->transfer_reference }}</td>
                                            <td>Rp {{ number_format($topUp->amount, 0, ',', '.') }}</td>
                                            <td><span class="badge {{ $topUp->status === 'approved' ? 'text-bg-success' : ($topUp->status === 'rejected' ? 'text-bg-danger' : 'text-bg-warning') }}">{{ ucfirst($topUp->status) }}</span></td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="4" class="text-center text-muted py-4">Belum ada permintaan isi saldo.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        {{ $topUpRequests->links() }}
                    </div>
                </section>
            </div>
        </div>
    </main>
</body>
</html>
