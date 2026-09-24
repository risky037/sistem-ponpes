@extends('layouts.app')

@section('title', 'Bantuan Pemulihan Akun | DIGITREN')

@section('content')
    <div class="section-authentication-login d-flex align-items-center justify-content-center my-5 my-lg-0 py-5">
        <div class="container">
            <div class="row row-cols-1 row-cols-lg-2 row-cols-xl-2">
                <div class="col mx-auto" style="max-width: 620px;">
                    <div class="card radius-15 shadow-sm overflow-hidden border-0">
                        <div class="card-body p-4 p-md-5">
                            <div class="text-center mb-4">
                                <img src="{{ url('assets/images/logo-icon.png') }}" width="72" alt="DIGITREN Logo" class="mb-2">
                                <h4 class="font-weight-bold text-primary mb-1">Bantuan Pemulihan Akun</h4>
                                <p class="text-muted small mb-0">{{ $namaPesantren }} — Layanan Terpadu</p>
                            </div>

                            {{-- WhatsApp Admin Direct Reset Template --}}
                            <div class="card border border-success bg-light-success radius-10 mb-4 shadow-none">
                                <div class="card-body p-3 p-md-4">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="widgets-icons-2 rounded-circle bg-success text-white me-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 46px; height: 46px;">
                                            <i class="bx bxl-whatsapp font-26"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-0 text-success font-weight-bold">Bantuan Langsung via WhatsApp Admin</h6>
                                            <small class="text-muted">Prosedur Resmi untuk Santri, Wali Santri, dan Tenaga Pendidik</small>
                                        </div>
                                    </div>
                                    <p class="text-secondary small mb-3">
                                        Demi keamanan dan verifikasi data, permohonan reset kata sandi dilayani langsung oleh tim Sekretariat & Administrasi Pesantren melalui WhatsApp resmi.
                                    </p>

                                    <div class="row g-2 mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label small text-muted font-weight-bold">Nama Lengkap</label>
                                            <input type="text" id="wa_user_name" class="form-control form-control-sm" placeholder="Contoh: Ahmad Fauzi">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small text-muted font-weight-bold">Email / Nomor Induk (NIS)</label>
                                            <input type="text" id="wa_user_id" class="form-control form-control-sm" placeholder="Contoh: 2024001 / nama@email.com">
                                        </div>
                                    </div>

                                    <div class="d-grid mb-2">
                                        <a href="{{ $defaultWaUrl }}" id="btn_wa_reset" target="_blank" rel="noopener noreferrer" class="btn btn-success d-flex align-items-center justify-content-center gap-2 py-2">
                                            <i class="bx bxl-whatsapp font-22"></i>
                                            <span class="font-weight-bold">Kirim Permohonan ke Admin ({{ $adminWhatsapp }})</span>
                                        </a>
                                    </div>
                                    <div class="text-center mt-2">
                                        <small class="text-muted font-11">
                                            <i class="bx bx-time-five"></i> Layanan Admin Pesantren Aktif: Setiap Hari Kerja (08.00 - 16.00 WIB)
                                        </small>
                                    </div>
                                </div>
                            </div>

                            <div class="card bg-light border-0 radius-10 mb-4 shadow-none">
                                <div class="card-body p-3">
                                    <div class="d-flex gap-2">
                                        <div class="text-primary font-20"><i class="bx bx-info-circle"></i></div>
                                        <div>
                                            <h6 class="font-13 font-weight-bold text-dark mb-1">Ketentuan Keamanan Akun</h6>
                                            <p class="text-muted font-12 mb-0">
                                                Setelah admin melakukan verifikasi identitas, kredensial baru akan diberikan secara aman. Pastikan segera mengganti kata sandi setelah berhasil login kembali.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="text-center">
                                <a href="{{ route('login') }}" class="btn btn-outline-secondary btn-sm px-4 d-inline-flex align-items-center gap-2">
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
        });
    </script>
@endpush
