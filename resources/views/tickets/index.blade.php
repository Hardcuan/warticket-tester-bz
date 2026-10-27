<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Event War Tiket · WARTICKET TESTER</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root { --war-ink: #20162f; --war-purple: #5b2bbf; --war-pink: #f04e98; }
        body { color: var(--war-ink); background: #f7f5fb !important; }
        .war-nav { background: rgb(255 255 255 / 94%); backdrop-filter: blur(12px); }
        .war-hero { color: white; background: radial-gradient(circle at 82% 12%, #f04e98 0, transparent 27%), linear-gradient(125deg, #241337, #5b2bbf 62%, #8353dc); }
        .event-card { transition: transform .2s ease, box-shadow .2s ease; }
        .event-card:hover { transform: translateY(-4px); box-shadow: 0 1rem 2rem rgb(38 22 65 / 12%) !important; }
        .event-art { min-height: 130px; background: linear-gradient(135deg, #22152e, #5b2bbf 68%, #f04e98); }
        .modal-content { border: 0; border-radius: 1.25rem; }
        @media (max-width: 991.98px) {
            body { padding-bottom: 5rem; }
            .mobile-tabs { min-height: 4.25rem; backdrop-filter: blur(14px); }
            .mobile-tabs a { color: #5c546d; font-size: .72rem; text-decoration: none; }
            .mobile-tabs a:hover { color: var(--war-purple); }
        }
        @media (max-width: 575.98px) {
            .event-art { min-height: 96px; }
            .container { padding-left: 1rem; padding-right: 1rem; }
        }
    </style>
</head>
<body class="bg-light">
    <main class="container py-5">
        <nav class="navbar war-nav rounded-4 shadow-sm px-3 px-md-4 mb-4">
            <a href="{{ route('home') }}" class="navbar-brand fw-bold mb-0 text-wrap fs-6" style="color:var(--war-purple)">WARTICKET TESTER<span style="color:var(--war-pink)">.</span></a>
            <nav class="d-flex flex-wrap justify-content-end align-items-center gap-2">
                @auth
                    <span class="text-muted d-none d-sm-inline">{{ auth()->user()->name }}</span>
                    @if (auth()->user()->isAdmin())
                        <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.dashboard') }}">Dashboard</a>
                    @endif
                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('account.password.edit') }}">Akun</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="btn btn-sm btn-outline-secondary" type="submit">Keluar</button>
                    </form>
                @else
                    <a class="btn btn-outline-primary" href="{{ route('login') }}">Masuk</a>
                    <a class="btn btn-primary" href="{{ route('register') }}">Daftar</a>
                @endauth
            </nav>
        </nav>

        <section class="war-hero rounded-4 p-4 p-md-5 mb-4 mb-md-5 shadow">
            <div class="row align-items-center g-4">
                <div class="col-lg-8">
                    <span class="badge rounded-pill bg-white text-dark mb-3 px-3 py-2">LIVE EVENT · TIKET RESMI</span>
                    <h1 class="display-5 fw-bold mb-3">Momen live.<br>Jangan sampai terlewat.</h1>
                            <p class="lead mb-0 text-white-50">Pilih event favoritmu, cek jadwal war, dan amankan tiket sebelum kehabisan.</p>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <a href="#events" class="btn btn-light btn-lg rounded-pill px-4 fw-semibold">Jelajahi event <span aria-hidden="true">↓</span></a>
                </div>
            </div>
        </section>

        @if (session('success'))
            <div class="alert alert-success" role="status">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger" role="alert">{{ session('error') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-warning" role="alert">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div id="events" class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-3">
            <div>
                <p class="text-uppercase small fw-bold mb-1" style="color:var(--war-pink)">Pilih pengalamanmu</p>
                <h2 class="h3 fw-bold mb-0">Event yang akan datang</h2>
            </div>
            <span class="text-muted small">{{ $tickets->count() }} event tersedia</span>
        </div>

        @if ($tickets->isEmpty())
            <section class="card border-0 shadow-sm">
                <div class="card-body p-5 text-center">
                    <h2 class="h4">Belum ada event tersedia</h2>
                    <p class="text-muted mb-0">Event yang diterbitkan admin akan muncul di sini.</p>
                </div>
            </section>
        @else
            <div class="row g-4">
                @foreach ($tickets as $ticket)
                    @php($saleStatus = $ticket->saleStatus())
                    <div
                        class="col-md-6 col-xl-4 ticket-item"
                        id="ticket-{{ $ticket->id }}"
                        data-status="{{ $saleStatus }}"
                        data-stock-url="{{ route('tickets.stock', $ticket) }}"
                        data-starts-at="{{ $ticket->sales_starts_at?->timestamp ?? '' }}"
                        data-ends-at="{{ $ticket->sales_ends_at?->timestamp ?? '' }}"
                    >
                        <article class="card event-card h-100 border-0 rounded-4 shadow-sm overflow-hidden">
                            <div class="event-art d-flex align-items-end p-3">
                                <span class="badge rounded-pill bg-white text-dark">LIVE · {{ strtoupper($ticket->name) }}</span>
                            </div>
                            <div class="card-body d-flex flex-column gap-3">
                                <div class="d-flex justify-content-between align-items-start gap-2">
                                    <h2 class="h4 mb-0">{{ $ticket->name }}</h2>
                                    <span class="badge {{ $saleStatus === 'open' ? 'text-bg-success' : ($saleStatus === 'upcoming' ? 'text-bg-primary' : 'text-bg-secondary') }}" data-ticket-status>
                                        @switch($saleStatus)
                                            @case('open') Sedang dibuka @break
                                            @case('upcoming') Segera dibuka @break
                                            @case('ended') Sudah berakhir @break
                                            @case('sold_out') Tiket habis @break
                                            @default Tidak tersedia
                                        @endswitch
                                    </span>
                                </div>

                                @if ($ticket->description)
                                    <p class="text-muted mb-0">{{ $ticket->description }}</p>
                                @endif

                                <dl class="row mb-0">
                                    <dt class="col-5">Harga</dt>
                                    <dd class="col-7">Rp {{ number_format($ticket->price, 0, ',', '.') }}</dd>
                                    <dt class="col-5">Stok tersedia</dt>
                                    <dd class="col-7"><span data-stock="{{ $ticket->id }}" data-stock-url="{{ route('tickets.stock', $ticket) }}">{{ $ticket->stock }}</span> slot</dd>
                                    <dt class="col-5">Mulai</dt>
                                    <dd class="col-7">{{ $ticket->sales_starts_at?->timezone(config('app.timezone'))->format('d-m-Y H:i') }}</dd>
                                    <dt class="col-5">Tutup</dt>
                                    <dd class="col-7">{{ $ticket->sales_ends_at?->timezone(config('app.timezone'))->format('d-m-Y H:i') }}</dd>
                                </dl>

                                <p class="small text-muted mb-0 {{ $saleStatus === 'upcoming' ? '' : 'd-none' }}" data-card-countdown>
                                    War dimulai dalam <strong data-countdown>memuat...</strong>
                                </p>

                                <div class="mt-auto">
                                    <button
                                        type="button"
                                        class="btn w-100 {{ $saleStatus === 'open' ? 'btn-primary' : ($saleStatus === 'upcoming' ? 'btn-outline-primary' : 'btn-secondary') }}"
                                        data-ticket-action
                                        data-ticket-name="{{ $ticket->name }}"
                                        data-ticket-price="{{ $ticket->price }}"
                                        data-ticket-stock="{{ $ticket->stock }}"
                                        data-buy-url="{{ route('tickets.buy', $ticket) }}"
                                        data-captcha-url="{{ route('captcha.image', $ticket) }}"
                                        data-status-url="{{ route('tickets.stock', $ticket) }}"
                                        data-registrations-url="{{ route('tickets.registrations', $ticket) }}"
                                        @disabled($saleStatus === 'unavailable')
                                    >
                                        @switch($saleStatus)
                                            @case('open') Pilih jumlah slot @break
                                            @case('upcoming') Lihat hitung mundur @break
                                            @case('ended') Sudah berakhir @break
                                            @case('sold_out') Tiket habis @break
                                            @default Tidak tersedia
                                        @endswitch
                                    </button>
                                </div>
                            </div>
                        </article>
                    </div>
                @endforeach
            </div>
        @endif
    </main>

    <div class="modal fade" id="checkoutModal" tabindex="-1" aria-labelledby="checkoutTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h2 class="modal-title h5" id="checkoutTitle">Detail event</h2>
                        <p class="text-muted small mb-0" id="modalTicketName"></p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body d-grid gap-3">
                    <div class="alert alert-primary mb-0 d-none" id="modalCountdown" role="status">
                        War dimulai dalam <strong id="modalCountdownValue"></strong>
                    </div>
                    <div class="alert alert-secondary mb-0 d-none" id="modalFinalStatus" role="status"></div>
                    <div class="alert alert-light border mb-0 d-none" id="registeredSummary" role="status">
                        <div class="fw-semibold mb-2">Peserta yang berhasil mendaftar</div>
                        <div><strong id="registeredCount">0</strong> slot terdaftar</div>
                        <div class="small text-muted mt-1">
                            Menunggu pembayaran: <strong id="pendingPaymentCount">0</strong>
                            · Lunas: <strong id="paidCount">0</strong>
                        </div>
                    </div>
                    <div class="small text-muted" id="modalStock"></div>
                    <div class="alert alert-warning mb-0 d-none" id="status_form" role="alert"></div>
                    @auth
                        <form id="form_pendaftaran" method="POST" action="#" class="d-grid gap-3">
                            @csrf
                            <div id="profileDetailsSection" class="d-grid gap-2 d-none">
                                <div>
                                    <label for="nama_lengkap" class="form-label mb-1">Nama lengkap</label>
                                    <input id="nama_lengkap" type="text" class="form-control" value="{{ auth()->user()->name }}" readonly>
                                </div>
                                <div>
                                    <label for="email" class="form-label mb-1">Email</label>
                                    <input id="email" type="email" class="form-control" value="{{ auth()->user()->email }}" readonly>
                                </div>
                                <div>
                                    <label for="nomor_telepon" class="form-label mb-1">Nomor telepon</label>
                                    <input id="nomor_telepon" type="tel" class="form-control" value="{{ auth()->user()->phone ?: 'Belum diisi' }}" readonly>
                                </div>
                            </div>
                            <div id="quantitySection">
                                <label for="jumlah_slot" class="form-label">Jumlah slot</label>
                                <select id="jumlah_slot" name="quantity" class="form-select" required disabled>
                                    <option value="" selected disabled>Jumlah slot</option>
                                </select>
                                <div class="form-text" id="quantityStock"></div>
                            </div>
                            <div id="orderTotalSection" class="rounded bg-light p-3 d-flex justify-content-between d-none">
                                <span>Total</span>
                                <strong id="orderTotal">Rp 0</strong>
                            </div>
                            <div id="captchaSection" class="d-grid gap-2 d-none">
                                <label class="form-label mb-0" for="kode_verifikasi">Masukkan teks pada gambar CAPTCHA</label>
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <img id="captchaImage" src="" width="240" height="88" alt="Captcha" class="border rounded bg-white">
                                    <button type="button" id="refreshCaptcha" class="btn btn-outline-secondary btn-sm">Ganti gambar</button>
                                </div>
                                <input
                                    id="kode_verifikasi"
                                    type="text"
                                    name="captcha"
                                    class="form-control text-uppercase"
                                    placeholder="Masukkan teks di atas"
                                    maxlength="6"
                                    minlength="6"
                                    pattern="[A-HJ-NP-Za-hj-np-z2-9]{6}"
                                    autocomplete="off"
                                    autocapitalize="characters"
                                    spellcheck="false"
                                    required
                                    disabled
                                >
                            </div>
                            <div class="d-flex justify-content-end gap-2">
                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
                                <button type="submit" id="btn_kirim" class="btn btn-primary" disabled>Registrasi</button>
                            </div>
                        </form>
                    @else
                        <div id="modalLoginPrompt" class="d-none">
                            <p class="text-muted mb-2">Masuk atau daftar setelah penjualan dibuka untuk memilih tiket dan mengisi CAPTCHA.</p>
                            <a href="{{ route('login') }}" class="btn btn-primary w-100">Masuk untuk ikut war</a>
                        </div>
                    @endauth
                </div>
            </div>
        </div>
    </div>
    @auth
        <nav class="mobile-tabs fixed-bottom bg-white border-top d-lg-none d-flex justify-content-around align-items-center px-2">
            <a href="{{ route('home') }}" class="text-center"><span class="d-block fs-5">⌂</span>Event</a>
            <a href="{{ route('account.wallet') }}" class="text-center"><span class="d-block fs-5">◉</span>Saldo</a>
            <a href="{{ route('account.profile.edit') }}" class="text-center"><span class="d-block fs-5">♙</span>Akun</a>
            @if (auth()->user()->isAdmin())
                <a href="{{ route('admin.dashboard') }}" class="text-center"><span class="d-block fs-5">▦</span>Admin</a>
            @endif
        </nav>
    @endauth

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const modalElement = document.getElementById('checkoutModal');
        const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
        const modalCountdown = document.getElementById('modalCountdown');
        const modalCountdownValue = document.getElementById('modalCountdownValue');
        const modalFinalStatus = document.getElementById('modalFinalStatus');
        const modalStock = document.getElementById('modalStock');
        const statusForm = document.getElementById('status_form');
        const ticketItems = [...document.querySelectorAll('.ticket-item')];
        let currentTicket = null;
        let previousStatus = null;
        let modalRefreshTimer = null;
        let captchaLoaded = false;
        let lastRegistrationStatsFetchedAt = 0;

        const statusLabels = {
            upcoming: 'Segera dibuka',
            open: 'Sedang dibuka',
            ended: 'Sudah berakhir',
            sold_out: 'Tiket habis',
            unavailable: 'Tidak tersedia',
        };

        function formatCountdown(seconds) {
            const days = Math.floor(seconds / 86400);
            const hours = Math.floor((seconds % 86400) / 3600);
            const minutes = Math.floor((seconds % 3600) / 60);
            const remainingSeconds = seconds % 60;

            return `${days} hari ${hours} jam ${minutes} menit ${remainingSeconds} detik`;
        }

        function countdownTo(timestamp) {
            return formatCountdown(Math.max(0, Number(timestamp) - Math.floor(Date.now() / 1000)));
        }

        function setActionState(item, status) {
            const button = item.querySelector('[data-ticket-action]');
            const badge = item.querySelector('[data-ticket-status]');
            const labels = {
                upcoming: 'Lihat hitung mundur',
                open: 'Pilih jumlah slot',
                ended: 'Sudah berakhir',
                sold_out: 'Tiket habis',
                unavailable: 'Tidak tersedia',
            };

            item.dataset.status = status;
            button.textContent = labels[status] ?? labels.unavailable;
            button.disabled = status === 'unavailable';
            button.classList.remove('btn-primary', 'btn-outline-primary', 'btn-secondary');
            button.classList.add(status === 'open' ? 'btn-primary' : (status === 'upcoming' ? 'btn-outline-primary' : 'btn-secondary'));
            badge.textContent = statusLabels[status] ?? statusLabels.unavailable;
            badge.classList.remove('text-bg-success', 'text-bg-primary', 'text-bg-secondary');
            badge.classList.add(status === 'open' ? 'text-bg-success' : (status === 'upcoming' ? 'text-bg-primary' : 'text-bg-secondary'));

            const countdown = item.querySelector('[data-card-countdown]');
            const countdownLabel = countdown.querySelector('[data-countdown]');
            countdown.classList.toggle('d-none', status !== 'upcoming');
            if (status === 'upcoming' && item.dataset.startsAt) {
                countdownLabel.textContent = countdownTo(item.dataset.startsAt);
            }

            const stockLabel = item.querySelector('[data-stock]');
            if (stockLabel && currentTicket === item) {
                modalStock.textContent = `Slot tersedia: ${stockLabel.textContent}.`;
            }
        }

        function updateModalStatus(data) {
            if (!currentTicket) {
                return;
            }

            const item = currentTicket;
            item.dataset.startsAt = data.starts_at ?? item.dataset.startsAt;
            item.dataset.endsAt = data.ends_at ?? item.dataset.endsAt;
            const stockLabel = item.querySelector('[data-stock]');
            if (stockLabel) {
                stockLabel.textContent = data.stock;
            }
            item.querySelector('[data-ticket-action]').dataset.ticketStock = String(data.stock);
            setActionState(item, data.status);
            modalStock.textContent = `Slot tersedia: ${data.stock}.`;
            modalCountdown.classList.toggle('d-none', data.status !== 'upcoming');
            modalFinalStatus.classList.toggle('d-none', !['ended', 'sold_out', 'unavailable'].includes(data.status));
            modalFinalStatus.textContent = data.status === 'ended'
                ? 'Waktu war telah berakhir.'
                : (data.status === 'sold_out' ? 'Tiket untuk event ini sudah habis.' : 'Event ini tidak tersedia.');
            const showRegistrationStats = ['ended', 'sold_out'].includes(data.status);
            const registeredSummary = document.getElementById('registeredSummary');
            registeredSummary.classList.toggle('d-none', !showRegistrationStats);
            if (!showRegistrationStats) {
                lastRegistrationStatsFetchedAt = 0;
            } else if (Date.now() - lastRegistrationStatsFetchedAt >= 15000) {
                loadRegistrationStats(item);
            }

            const canOrder = data.status === 'open';
            const quantitySection = document.getElementById('quantitySection');
            const totalSection = document.getElementById('orderTotalSection');
            const captchaSection = document.getElementById('captchaSection');
            const profileDetailsSection = document.getElementById('profileDetailsSection');
            const loginPrompt = document.getElementById('modalLoginPrompt');

            if (loginPrompt) {
                loginPrompt.classList.toggle('d-none', !canOrder);
            }

            if (quantitySection) {
                profileDetailsSection.classList.toggle('d-none', !canOrder);
                quantitySection.classList.toggle('d-none', !canOrder);
                totalSection.classList.toggle('d-none', !canOrder);
                captchaSection.classList.toggle('d-none', !canOrder);

                if (canOrder) {
                    const quantity = document.getElementById('jumlah_slot');
                    syncQuantityOptions(data.stock);
                    quantity.disabled = false;
                    document.getElementById('quantityStock').textContent = `Maksimal ${data.stock} slot tersedia.`;
                    updateOrderTotal();

                    if (previousStatus !== 'open') {
                        captchaLoaded = false;
                        const captchaInput = document.getElementById('kode_verifikasi');
                        captchaInput.disabled = false;
                        document.getElementById('btn_kirim').disabled = true;
                        loadCaptcha();
                    } else if (captchaLoaded) {
                        document.getElementById('btn_kirim').disabled = false;
                    }
                } else {
                    document.getElementById('jumlah_slot').disabled = true;
                    document.getElementById('kode_verifikasi').disabled = true;
                    document.getElementById('btn_kirim').disabled = true;
                    captchaLoaded = false;
                }
            }

            if (data.status === 'upcoming' && item.dataset.startsAt) {
                modalCountdownValue.textContent = countdownTo(item.dataset.startsAt);
            }

            previousStatus = data.status;
        }

        async function loadRegistrationStats(item) {
            const button = item.querySelector('[data-ticket-action]');
            const summary = document.getElementById('registeredSummary');
            lastRegistrationStatsFetchedAt = Date.now();

            try {
                const response = await fetch(button.dataset.registrationsUrl, {
                    headers: { 'Accept': 'application/json' },
                    cache: 'no-store',
                });
                if (!response.ok) {
                    throw new Error('Jumlah peserta terdaftar tidak dapat dimuat.');
                }

                const data = await response.json();
                document.getElementById('registeredCount').textContent = data.registered_count;
                document.getElementById('pendingPaymentCount').textContent = data.pending_payment_count;
                document.getElementById('paidCount').textContent = data.paid_count;
            } catch (error) {
                lastRegistrationStatsFetchedAt = 0;
                statusForm.textContent = error.message;
                statusForm.classList.remove('d-none');
                summary.classList.add('d-none');
            }
        }

        async function fetchTicketStatus(item) {
            const response = await fetch(item.dataset.stockUrl, {
                headers: { 'Accept': 'application/json' },
                cache: 'no-store',
            });

            if (!response.ok) {
                throw new Error('Tidak dapat memperbarui status event. Coba muat ulang halaman.');
            }

            const data = await response.json();
            setActionState(item, data.status);
            const stockLabel = item.querySelector('[data-stock]');
            if (stockLabel) {
                stockLabel.textContent = data.stock;
            }

            if (currentTicket === item) {
                updateModalStatus(data);
            }
        }

        function loadCaptcha() {
            const image = document.getElementById('captchaImage');
            const input = document.getElementById('kode_verifikasi');
            const button = document.getElementById('btn_kirim');
            statusForm.classList.add('d-none');
            input.value = '';
            button.disabled = true;
            image.onload = () => {
                captchaLoaded = true;
                button.disabled = false;
            };
            image.onerror = () => {
                statusForm.textContent = 'Gambar CAPTCHA gagal dimuat. Tekan Ganti gambar untuk mencoba lagi.';
                statusForm.classList.remove('d-none');
                button.disabled = true;
            };
            image.src = `${currentTicket.querySelector('[data-ticket-action]').dataset.captchaUrl}?t=${Date.now()}`;
        }

        function updateOrderTotal() {
            const price = Number(currentTicket?.querySelector('[data-ticket-action]').dataset.ticketPrice ?? 0);
            const quantity = Number(document.getElementById('jumlah_slot')?.value ?? 0);
            const total = document.getElementById('orderTotal');
            if (total) {
                total.textContent = `Rp ${new Intl.NumberFormat('id-ID').format(price * quantity)}`;
            }
        }

        function syncQuantityOptions(stock) {
            const quantity = document.getElementById('jumlah_slot');
            const availableSlots = Number(stock);

            if (!Number.isSafeInteger(availableSlots) || availableSlots < 1) {
                quantity.replaceChildren(new Option('Slot tidak tersedia', ''));
                quantity.dataset.availableStock = '0';
                return;
            }

            if (Number(quantity.dataset.availableStock) === availableSlots) {
                return;
            }

            const selectedQuantity = Math.min(Number(quantity.value) || 1, availableSlots);
            const options = document.createDocumentFragment();

            for (let slot = 1; slot <= availableSlots; slot++) {
                options.append(new Option(`${slot} slot`, String(slot)));
            }

            quantity.replaceChildren(options);
            quantity.value = String(selectedQuantity);
            quantity.dataset.availableStock = String(availableSlots);
        }

        document.querySelectorAll('[data-ticket-action]').forEach((button) => {
            button.addEventListener('click', async () => {
                currentTicket = button.closest('.ticket-item');
                previousStatus = null;
                captchaLoaded = false;
                lastRegistrationStatsFetchedAt = 0;
                document.getElementById('registeredSummary').classList.add('d-none');
                document.getElementById('modalTicketName').textContent = button.dataset.ticketName;
                modalFinalStatus.classList.add('d-none');
                modalCountdown.classList.add('d-none');
                statusForm.classList.add('d-none');

                const form = document.getElementById('form_pendaftaran');
                if (form) {
                    form.action = button.dataset.buyUrl;
                    document.getElementById('jumlah_slot').value = '1';
                    document.getElementById('jumlah_slot').disabled = true;
                    document.getElementById('kode_verifikasi').disabled = true;
                    document.getElementById('btn_kirim').disabled = true;
                    document.getElementById('captchaImage').removeAttribute('src');
                    document.getElementById('captchaSection').classList.add('d-none');
                    document.getElementById('quantitySection').classList.add('d-none');
                    document.getElementById('orderTotalSection').classList.add('d-none');
                }

                modal.show();
                try {
                    await fetchTicketStatus(currentTicket);
                } catch (error) {
                    statusForm.textContent = error.message;
                    statusForm.classList.remove('d-none');
                }
            });
        });

        if (document.getElementById('jumlah_slot')) {
            document.getElementById('jumlah_slot').addEventListener('change', updateOrderTotal);
            document.getElementById('refreshCaptcha').addEventListener('click', loadCaptcha);
        }

        modalElement.addEventListener('shown.bs.modal', () => {
            modalRefreshTimer = window.setInterval(async () => {
                if (!currentTicket) {
                    return;
                }

                try {
                    await fetchTicketStatus(currentTicket);
                    statusForm.classList.add('d-none');
                } catch (error) {
                    statusForm.textContent = error.message;
                    statusForm.classList.remove('d-none');
                }
            }, 3000);
        });

        modalElement.addEventListener('hidden.bs.modal', () => {
            window.clearInterval(modalRefreshTimer);
            currentTicket = null;
            previousStatus = null;
            captchaLoaded = false;
        });

        window.setInterval(() => {
            ticketItems.forEach((item) => {
                if (item.dataset.startsAt && item.dataset.endsAt) {
                    if (item.dataset.status === 'upcoming') {
                        const countdown = item.querySelector('[data-countdown]');
                        countdown.textContent = countdownTo(item.dataset.startsAt);
                    }
                }
            });
        }, 1000);

        window.setInterval(() => {
            ticketItems.forEach((item) => {
                fetchTicketStatus(item).catch((error) => console.error(error));
            });
        }, 15000);
    </script>
</body>
</html>
