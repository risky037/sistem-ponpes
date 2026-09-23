@extends('layouts.app')

@section('title', 'Lupa Password | DIGITREN')

@section('content')
    <div class="section-authentication-login d-flex align-items-center justify-content-center my-5 my-lg-0 py-5">
        <div class="container">
            <div class="row row-cols-1 row-cols-lg-2 row-cols-xl-2">
                <div class="col mx-auto" style="max-width: 680px;">
                    <div class="card radius-15 shadow-sm overflow-hidden border-0">
                        <div class="card-body p-4 p-md-5">
                            <div class="text-center mb-4">
                                <img src="{{ url('assets/images/logo-icon.png') }}" width="72" alt="DIGITREN Logo" class="mb-2">
                                <h4 class="font-weight-bold text-primary mb-1">Bantuan Pemulihan Akun</h4>
                                <p class="text-muted small mb-0">{{ $namaPesantren }} — Sistem Informasi Terpadu</p>
                            </div>

                            @if (session('status'))
                                <div class="alert alert-success border-0 bg-success alert-dismissible fade show py-2">
                                    <div class="d-flex align-items-center">
                                        <div class="font-35 text-white"><i class="bx bxs-check-circle"></i></div>
                                        <div class="ms-3 text-white">
                                            <div class="font-weight-bold">{{ session('status') }}</div>
                                        </div>
                                    </div>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            @endif

                            {{-- PRIMARY OPTION: WhatsApp Admin Direct Reset Template --}}
                            <div class="card border border-success bg-light-success radius-10 mb-4 shadow-none">
                                <div class="card-body p-3 p-md-4">
                                    <div class="d-flex align-items-center mb-2">
                                        <div class="widgets-icons-2 rounded-circle bg-success text-white me-3 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                                            <i class="bx bxl-whatsapp font-24"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-0 text-success font-weight-bold">Bantuan Cepat via WhatsApp Admin</h6>
                                            <small class="text-muted">Rekomendasi Utama untuk Santri, Wali, dan Tenaga Pendidik</small>
                                        </div>
                                    </div>
                                    <p class="text-secondary small mb-3">
                                        Hubungi langsung admin pesantren melalui WhatsApp resmi dengan template permohonan reset yang terisi otomatis.
                                    </p>

                                    <div class="row g-2 mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label small text-muted">Nama Lengkap</label>
                                            <input type="text" id="wa_user_name" class="form-control form-control-sm" placeholder="Contoh: Ahmad Fauzi">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small text-muted">Email / Nomor Induk (NIS)</label>
                                            <input type="text" id="wa_user_id" class="form-control form-control-sm" placeholder="Contoh: 2024001 / email@...">
                                        </div>
                                    </div>

                                    <div class="d-grid">
                                        <a href="{{ $defaultWaUrl }}" id="btn_wa_reset" target="_blank" rel="noopener noreferrer" class="btn btn-success d-flex align-items-center justify-content-center gap-2">
                                            <i class="bx bxl-whatsapp font-20"></i>
                                            <span class="font-weight-bold">Kirim Permohonan Reset ke Admin ({{ $adminWhatsapp }})</span>
                                        </a>
                                    </div>
                                    <div class="text-center mt-2">
                                        <small class="text-muted font-11">
                                            <i class="bx bx-time-five"></i> Layanan Admin Pesantren Aktif: Setiap Hari Kerja (08.00 - 16.00 WIB)
                                        </small>
                                    </div>
                                </div>
                            </div>

                            <div class="position-relative text-center my-4">
                                <hr class="border-secondary opacity-25">
                                <span class="position-absolute top-50 start-50 translate-middle bg-white px-3 text-muted small">
                                    atau opsi reset email mandiri
                                </span>
                            </div>

                            {{-- SECONDARY OPTION: Laravel Native Email Reset --}}
                            <div class="mb-3">
                                <form action="{{ route('password.email') }}" method="POST" id="form_email_reset">
                                    @csrf
                                    <div class="mb-3">
                                        <label for="email" class="form-label small font-weight-bold">Email Akun Terdaftar</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-transparent"><i class="bx bx-envelope"></i></span>
                                            <input type="email" name="email" id="email" class="form-control @error('email') is-invalid @enderror"
                                                value="{{ old('email') }}" placeholder="masukkan.email@anda.com" required>
                                            @error('email')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <small class="text-muted font-11">
                                            Tautan pemulihan akan dikirimkan ke email yang terdaftar jika server surat aktif.
                                        </small>
                                    </div>

                                    <div class="d-grid mb-2">
                                        <button type="submit" class="btn btn-outline-primary" id="btn_submit_email">
                                            <i class="bx bx-send me-1"></i> Kirim Tautan Reset ke Email
                                        </button>
                                    </div>
                                </form>
                            </div>

                            <hr class="my-3">

                            <div class="text-center">
                                <a href="{{ route('login') }}" class="text-decoration-none text-secondary d-inline-flex align-items-center gap-1 font-13">
                                    <i class="bx bx-arrow-back font-16"></i> Kembali ke Halaman Login
                                </a>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('js')
    <script>
        $(document).ready(function() {
            var adminWhatsapp = "{{ $adminWhatsapp }}";
            var namaPesantren = "{{ $namaPesantren }}";

            function buildWaLink() {
                var name = $('#wa_user_name').val().trim();
                var identifier = $('#wa_user_id').val().trim();

                var cleanNumber = adminWhatsapp.replace(/[^0-9]/g, '');
                if (cleanNumber.startsWith('0')) {
                    cleanNumber = '62' + cleanNumber.substring(1);
                } else if (cleanNumber.startsWith('8')) {
                    cleanNumber = '62' + cleanNumber;
                }

                var message = "Assalamu'alaikum Admin " + namaPesantren + ",\n" +
                              "Saya membutuhkan bantuan reset password akun DIGITREN.";
                if (name) {
                    message += "\nNama: " + name;
                }
                if (identifier) {
                    message += "\nEmail / No. Induk: " + identifier;
                }
                message += "\nMohon bantuan untuk reset kredensial saya. Terima kasih.";

                var waUrl = "https://wa.me/" + cleanNumber + "?text=" + encodeURIComponent(message);
                $('#btn_wa_reset').attr('href', waUrl);
            }

            $('#wa_user_name, #wa_user_id').on('input change', function() {
                buildWaLink();
            });

            $('#form_email_reset').on('submit', function() {
                var btn = $('#btn_submit_email');
                btn.prop('disabled', true);
                btn.html('<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Mengirim...');
            });
        });
    </script>
@endpush
