@extends('layouts.app')

@section('title', 'Login | DIGITREN')

@section('content')
    <div>
        <div class="section-authentication-login d-flex align-items-center justify-content-center mt-4">
            <div class="row">
                <div class="col-12 col-lg-8 mx-auto">
                    <div class="card radius-15 overflow-hidden">
                        <div class="row g-0">
                            <div class="col-xl-6 col-md-6">
                                <div class="card-body p-5">
                                    <div class="text-center">
                                        <img src="{{ url('assets/images/logo-icon.png') }}" width="80" alt="">
                                        <h3 class="mt-4 font-weight-bold">Welcome Back</h3>
                                    </div>
                                    <div>
                                        <div class="form-body">
                                            <form class="row g-3" id="form_login" action="{{ route('login.auth') }}" method="POST">
                                                @csrf
                                                <div class="col-md-12">
                                                    <x-input name='email' id='email' label='Email' type='email'
                                                        value="{{ old('email') }}" placeholder='example@example.com'>
                                                    </x-input>
                                                </div>
                                                <div class="col-md-12">
                                                    <x-input-password label='Enter Password' id='password'>
                                                    </x-input-password>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox"
                                                            name="remember" id="flexSwitchCheckChecked" value="1" {{ old('remember') ? 'checked' : 'checked' }}>
                                                        <label class="form-check-label font-13"
                                                            for="flexSwitchCheckChecked">Ingat Saya</label>
                                                    </div>
                                                </div>
                                                <div class="col-md-6 text-end">
                                                    <a href="{{ route('password.request') }}" class="font-13 text-primary text-decoration-none">Lupa Password?</a>
                                                </div>
                                                <div class="col-12">
                                                    <div class="d-grid">
                                                        <button type="submit" id="btn_submit_login" class="btn btn-primary d-flex align-items-center justify-content-center gap-2">
                                                            <i class="bx bxs-lock-open"></i>
                                                            <span>Masuk Sistem</span>
                                                        </button>
                                                    </div>
                                                </div>
                                                <div class="col-12 text-center mt-3">
                                                    <button type="button" class="btn btn-link btn-sm text-secondary text-decoration-none p-0" data-bs-toggle="modal" data-bs-target="#modalHelpOnboarding">
                                                        <i class="bx bx-help-circle me-1"></i> Petunjuk Akses Akun & Bantuan
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-6 col-md-6 bg-login-color d-flex align-items-center justify-content-center">
                                <img src="{{ url('assets/images/login-images/login-frent-img.jpg') }}" loading="lazy" class="img-fluid"
                                    alt="Ilustrasi Login DIGITREN">
                            </div>
                        </div>
                        <!--end row-->
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- PUBLIC ONBOARDING & HELP MODAL --}}
    <div class="modal fade" id="modalHelpOnboarding" tabindex="-1" aria-labelledby="modalHelpOnboardingLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content radius-10">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title font-weight-bold" id="modalHelpOnboardingLabel">
                        <i class="bx bx-info-circle text-primary me-1"></i> Petunjuk Masuk Sistem DIGITREN
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body py-3">
                    <p class="text-secondary small mb-3">
                        Sistem Informasi Pesantren Fatimah Az Zahra (DIGITREN) menggunakan satu gerbang login terpadu berbasis peran (role-based access control).
                    </p>

                    <div class="list-group list-group-flush mb-3">
                        <div class="list-group-item px-0">
                            <h6 class="mb-1 font-weight-bold text-dark font-13"><i class="bx bx-user me-1 text-primary"></i> Santri & Wali</h6>
                            <p class="text-muted small mb-0">Gunakan akun email yang telah didaftarkan saat registrasi santri atau hubungi sekretariat madrasah.</p>
                        </div>
                        <div class="list-group-item px-0">
                            <h6 class="mb-1 font-weight-bold text-dark font-13"><i class="bx bx-book-reader me-1 text-info"></i> Guru / Asatidz</h6>
                            <p class="text-muted small mb-0">Akses jadwal mengajar, presensi halaqah/kelas, dan input capaian nilai melalui akun pengajar resmi.</p>
                        </div>
                        <div class="list-group-item px-0">
                            <h6 class="mb-1 font-weight-bold text-dark font-13"><i class="bx bx-wallet me-1 text-success"></i> Operator Keuangan & Admin</h6>
                            <p class="text-muted small mb-0">Operasional tabungan santri, penarikan harian, dan manajemen data akademik madrasah.</p>
                        </div>
                    </div>

                    <div class="alert alert-light-warning border-warning p-2 mb-0 radius-10">
                        <small class="text-dark">
                            <strong>Lupa kata sandi?</strong> Silakan gunakan menu <a href="{{ route('password.request') }}" class="text-primary font-weight-bold">Lupa Password</a> untuk permohonan reset via WhatsApp Admin resmi.
                        </small>
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
                    <a href="{{ route('password.request') }}" class="btn btn-primary btn-sm">Buka Menu Bantuan Reset</a>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('js')
    @if (flash()->message)
        <!--notification js -->
        <script src="{{ url('assets/plugins/notifications/js/lobibox.min.js') }}"></script>
        <script src="{{ url('assets/plugins/notifications/js/notifications.min.js') }}"></script>
        <script>
            Lobibox.notify("{{ flash()->class }}", {
                pauseDelayOnHover: true,
                icon: 'bx bx-x-circle',
                size: 'mini',
                continueDelayOnInactiveTab: false,
                position: 'top right',
                msg: "{{ flash()->message }}"
            });
        </script>
    @endif
    <script>
        $(document).ready(function() {
            $('#form_login').on('submit', function() {
                var btn = $('#btn_submit_login');
                btn.prop('disabled', true);
                btn.html('<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Memproses...');
            });
        });
    </script>
@endpush
