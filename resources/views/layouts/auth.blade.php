<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') - WARTICKET TESTER</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <main class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <section class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <a href="{{ route('home') }}" class="small fw-bold text-decoration-none">WARTICKET TESTER</a>
                        <h1 class="h3 mt-2 mb-4">@yield('heading')</h1>
                        @if ($errors->any())
                            <div class="alert alert-danger" role="alert">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                        @yield('content')
                    </div>
                </section>
                <a href="{{ route('home') }}" class="btn btn-link w-100 mt-3">Kembali ke halaman war tiket</a>
            </div>
        </div>
    </main>
</body>
</html>
