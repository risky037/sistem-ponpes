<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>@yield('title') | DIGITREN</title>
    <link rel="icon" href="{{ url('assets/images/favicon-32x32.png') }}" type="image/png" />
    <link rel="stylesheet" href="{{ url('assets/css/bootstrap.min.css') }}" />
    <link rel="stylesheet" href="{{ url('assets/css/icons.css') }}" />
    <link rel="stylesheet" href="{{ url('assets/css/app.css') }}" />
</head>
<body class="bg-light">
    <div class="d-flex align-items-center justify-content-center min-vh-100 p-4">
        <div class="container" style="max-width: 620px;">
            <div class="card radius-15 border-0 shadow-sm text-center p-4 p-md-5">
                <div class="card-body">
                    <div class="mb-4">
                        <img src="{{ url('assets/images/logo-icon.png') }}" width="72" alt="DIGITREN" class="mb-2">
                        <h6 class="text-muted small text-uppercase font-weight-bold mb-0">Sistem Informasi Pesantren Fatimah Az Zahra</h6>
                    </div>

                    <div class="mb-3">
                        <span class="display-1 font-weight-bold text-primary">@yield('code', 'Error')</span>
                    </div>

                    <h4 class="font-weight-bold text-dark mb-2">@yield('heading', 'Terjadi Kendala')</h4>
                    <p class="text-secondary mb-4">
                        @yield('message', 'Halaman atau tindakan yang Anda minta tidak dapat diproses.')
                    </p>

                    <div class="d-flex flex-wrap justify-content-center gap-2 mb-4">
                        <a href="{{ url('/dashboard') }}" class="btn btn-primary d-inline-flex align-items-center gap-1">
                            <i class="bx bx-home-alt"></i> Kembali ke Dashboard
                        </a>
                        <button onclick="window.history.back()" class="btn btn-outline-secondary d-inline-flex align-items-center gap-1">
                            <i class="bx bx-arrow-back"></i> Halaman Sebelumnya
                        </button>
                    </div>

                    <hr class="my-3 opacity-25">

                    <small class="text-muted d-block">
                        Butuh bantuan teknis? Hubungi <a href="https://wa.me/{{ config('pesantren.admin_whatsapp', '6281234567890') }}" target="_blank" class="text-success font-weight-bold"><i class="bx bxl-whatsapp"></i> Admin Pesantren</a>
                    </small>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
