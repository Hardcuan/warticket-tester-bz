<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pembayaran · WARTICKET TESTER</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f5f4fa; color: #211735; }
        .brand { color: #5528b5; text-decoration: none; font-weight: 800; letter-spacing: .02em; }
        .receipt { border: 0; border-radius: 1.25rem; }
    </style>
</head>
<body>
    <main class="container py-4 py-lg-5">
        <nav class="d-flex justify-content-between align-items-center mb-4">
            <a href="{{ route('home') }}" class="brand">WARTICKET TESTER</a>
            <div class="d-flex gap-2">
                <a href="{{ route('account.wallet') }}" class="btn btn-sm btn-outline-primary">Saldo akun</a>
                <a href="{{ route('home') }}" class="btn btn-sm btn-outline-secondary">Katalog</a>
            </div>
        </nav>

        <div class="row justify-content-center">
            <div class="col-lg-8 col-xl-7">
                <section class="card receipt shadow-sm">
                    <div class="card-body p-4 p-md-5">
                        <p class="text-primary fw-semibold mb-1">CHECKOUT TIKET</p>
                        <h1 class="h2 fw-bold mb-1">Ringkasan pembayaran</h1>
                        <p class="small text-muted mb-4">Nomor pesanan {{ $order->public_id }}</p>

                        @if (session('success'))
                            <div class="alert alert-success" role="status">{{ session('success') }}</div>
                        @endif
                        @if (session('error'))
                            <div class="alert alert-warning" role="alert">{{ session('error') }}</div>
                        @endif

                        <div class="rounded-4 bg-light p-3 p-md-4 mb-4">
                            <div class="d-flex justify-content-between gap-3 mb-3">
                                <span>Event</span><strong class="text-end">{{ $order->ticket_name }}</strong>
                            </div>
                            <div class="d-flex justify-content-between gap-3 mb-3">
                                <span>Harga satuan</span><strong>Rp {{ number_format($order->unit_price, 0, ',', '.') }}</strong>
                            </div>
                            <div class="d-flex justify-content-between gap-3 mb-3">
                                <span>Jumlah slot</span><strong>{{ $order->quantity }}</strong>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between gap-3 fs-5">
                                <span>Total tagihan</span><strong>Rp {{ number_format($order->total, 0, ',', '.') }}</strong>
                            </div>
                        </div>

                        @if ($order->status === 'paid')
                            <div class="alert alert-success">
                                Pembayaran berhasil melalui {{ $order->payment_method === 'wallet' ? 'saldo akun' : 'Midtrans' }} pada
                                {{ $order->paid_at?->timezone(config('app.timezone'))->format('d-m-Y H:i') }}.
                            </div>
                        @elseif ($order->status === 'expired')
                            <div class="alert alert-secondary">
                                Pesanan kedaluwarsa dan slot telah dikembalikan. Buat pesanan baru jika slot masih tersedia.
                            </div>
                            @if ($order->late_gateway_payment_at)
                                <div class="alert alert-danger">
                                    Pembayaran gateway tercatat setelah reservasi berakhir. Jangan membayar ulang; hubungi admin untuk pemeriksaan/refund.
                                </div>
                            @endif
                        @else
                            <div class="alert alert-info">
                                Reservasi tiket berlaku sampai
                                <strong>{{ $order->expires_at?->timezone(config('app.timezone'))->format('d-m-Y H:i') }}</strong>
                                (maksimal 24 jam setelah pemesanan).
                            </div>

                            @if ($order->gateway_payment_url)
                                <a href="{{ $order->gateway_payment_url }}" class="btn btn-primary btn-lg w-100 mb-3">
                                    Lanjutkan pembayaran Midtrans
                                </a>
                            @elseif ($order->gateway_session_requested_at)
                                <div class="alert alert-warning">
                                    Permintaan sesi Midtrans sedang diproses atau perlu pemeriksaan admin. Jangan meminta sesi berulang atau berpindah ke saldo agar tidak terjadi tagihan ganda.
                                </div>
                            @else
                                <form method="POST" action="{{ route('orders.payment.midtrans', $order) }}" class="mb-3">
                                    @csrf
                                    <button type="submit" class="btn btn-primary btn-lg w-100">
                                        Bayar dengan Midtrans
                                    </button>
                                </form>
                            @endif

                            @if (! $order->gateway_session_requested_at)
                                <form method="POST" action="{{ route('orders.payment.wallet', $order) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-outline-success btn-lg w-100">
                                        Bayar dengan saldo · Rp {{ number_format($walletBalance, 0, ',', '.') }}
                                    </button>
                                </form>
                            @endif
                            <p class="small text-muted mt-3 mb-0">
                                Status pembayaran gateway hanya diperbarui setelah notifikasi server Midtrans tervalidasi. Jangan menutup halaman sebelum transaksi selesai.
                            </p>
                        @endif

                        <a href="{{ route('home') }}" class="btn btn-link w-100 mt-3">Kembali ke katalog event</a>
                    </div>
                </section>
            </div>
        </div>
    </main>
</body>
</html>
