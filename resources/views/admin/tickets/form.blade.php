<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $ticket->exists ? 'Ubah' : 'Buat' }} Event · WARTICKET TESTER</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <main class="container py-4 py-lg-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <section class="card border-0 shadow-sm">
                    <div class="card-body p-4 p-md-5">
                        <a href="{{ route('admin.dashboard') }}" class="text-decoration-none fw-bold">WARTICKET TESTER</a>
                        <div class="d-flex flex-wrap gap-2 my-3">
                            <a href="{{ route('admin.dashboard') }}" class="btn btn-sm btn-outline-primary">Panel</a>
                            <a href="{{ route('admin.tickets.index') }}" class="btn btn-sm btn-outline-secondary">Event</a>
                            <a href="{{ route('admin.users.index') }}" class="btn btn-sm btn-outline-secondary">Pengguna</a>
                            <a href="{{ route('admin.top-ups.index') }}" class="btn btn-sm btn-outline-secondary">Saldo</a>
                        </div>
                        <h1 class="h3 mb-4">{{ $ticket->exists ? 'Ubah Event' : 'Buat Event War Tiket' }}</h1>

                        @if ($errors->any())
                            <div class="alert alert-danger" role="alert">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form method="POST" action="{{ $ticket->exists ? route('admin.tickets.update', $ticket) : route('admin.tickets.store') }}">
                            @csrf
                            @if ($ticket->exists)
                                @method('PUT')
                            @endif

                            <div class="mb-3">
                                <label for="name" class="form-label">Nama event</label>
                                <input id="name" name="name" class="form-control" value="{{ old('name', $ticket->name) }}" maxlength="255" required>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">Deskripsi</label>
                                <textarea id="description" name="description" class="form-control" rows="4" maxlength="5000">{{ old('description', $ticket->description) }}</textarea>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="price" class="form-label">Harga per tiket (rupiah)</label>
                                    <input id="price" type="number" name="price" class="form-control" min="1" max="1000000000" value="{{ old('price', $ticket->price) }}" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="stock" class="form-label">Jumlah slot tersedia</label>
                                    <input id="stock" type="number" name="stock" class="form-control" min="0" max="1000000" value="{{ old('stock', $ticket->stock ?? 0) }}" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="sales_starts_at" class="form-label">Waktu mulai penjualan</label>
                                    <input
                                        id="sales_starts_at"
                                        type="datetime-local"
                                        name="sales_starts_at"
                                        class="form-control"
                                        value="{{ old('sales_starts_at', $ticket->sales_starts_at?->timezone(config('app.timezone'))->format('Y-m-d\TH:i')) }}"
                                        required
                                    >
                                </div>
                                <div class="col-md-6">
                                    <label for="sales_ends_at" class="form-label">Waktu tutup penjualan</label>
                                    <input
                                        id="sales_ends_at"
                                        type="datetime-local"
                                        name="sales_ends_at"
                                        class="form-control"
                                        value="{{ old('sales_ends_at', $ticket->sales_ends_at?->timezone(config('app.timezone'))->format('Y-m-d\TH:i')) }}"
                                        required
                                    >
                                </div>
                            </div>

                            <div class="form-check my-4">
                                <input
                                    id="is_published"
                                    type="checkbox"
                                    name="is_published"
                                    value="1"
                                    class="form-check-input"
                                    @checked(old('is_published', $ticket->is_published))
                                >
                                <label for="is_published" class="form-check-label">Terbitkan event dan kirim email pemberitahuan kepada peserta yang memilih ikut email</label>
                            </div>

                            <div class="d-flex flex-wrap gap-2">
                                <button type="submit" class="btn btn-primary">{{ $ticket->exists ? 'Simpan perubahan' : 'Simpan event' }}</button>
                                <a href="{{ route('admin.tickets.index') }}" class="btn btn-outline-secondary">Batal</a>
                            </div>
                            <p class="form-text mt-3 mb-0">Zona waktu jadwal: {{ config('app.timezone') }}. Email dikirim melalui antrean Laravel.</p>
                        </form>
                    </div>
                </section>
            </div>
        </div>
    </main>
    <nav class="fixed-bottom bg-white border-top d-lg-none d-flex justify-content-around py-2">
        <a class="small text-decoration-none text-center" href="{{ route('admin.dashboard') }}">▦<br>Panel</a>
        <a class="small text-decoration-none text-center fw-bold text-primary" href="{{ route('admin.tickets.index') }}">♫<br>Event</a>
        <a class="small text-decoration-none text-center" href="{{ route('admin.users.index') }}">♙<br>User</a>
        <a class="small text-decoration-none text-center" href="{{ route('admin.top-ups.index') }}">◉<br>Saldo</a>
    </nav>
</body>
</html>
