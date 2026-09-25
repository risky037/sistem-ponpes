<div class="tab-pane fade show active" id="Edit-Profile">
    <div class="card shadow-none border mb-0 radius-15">
        <div class="card-body">
            <div class="form-body">
                <div class="row">
                    <div class="col-12 col-lg-5 border-right">
                        <h5 class="fw-bold">Pengaturan Akun</h5>
                        <form class="row g-3" action="{{ route('profil.account', $user->id) }}" method="POST">
                            @csrf
                            <div class="col-12">
                                <x-input type='text' label='Nama Lengkap' id="name" name='name'
                                    placeholder="Nama Lengkap"
                                    value="{{ old('name', $user->name) }}"></x-input>
                            </div>
                            <div class="col-12">
                                <x-input type='email' label='Email' id="email" name='email' placeholder="Email"
                                    value="{{ old('email', $user->email) }}"></x-input>
                            </div>
                            <div class="col-6">
                                <x-input type='password' label='Password' id="password" name='password'
                                    placeholder="Password"></x-input>
                            </div>
                            <div class="col-6">
                                <x-input type='password' label='Konfirmasi Password' id="password_confirmation"
                                    name='password_confirmation' placeholder="Konfirmasi Password"></x-input>
                            </div>
                            <div class="col-4">
                                <button type="submit" class="btn btn-primary">Simpan Akun</button>
                            </div>
                        </form>
                    </div>
                    <div class="col-12 col-lg-7">
                        @if ($user->santri)
                            <h5 class="fw-bold">Pengaturan Biodata</h5>
                            <form class="row g-3" action="{{ route('profil.biodata', $user->id) }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <div class="col-12">
                                    <p class="mb-0">Tempat/Tanggal Lahir</p>
                                </div>
                                <div class="col-12 col-lg-6">
                                    <x-input type='text' label='Tempat Lahir' id="tempat_lahir" name='tempat_lahir'
                                        placeholder="Tempat Lahir"
                                        value="{{ old('tempat_lahir', $user->santri?->tempat_lahir ?? '') }}"></x-input>
                                </div>
                                <div class="col-12 col-lg-6">
                                    <x-input type='date' label='Tanggal Lahir' id="tanggal_lahir"
                                        name='tanggal_lahir' placeholder="Tanggal Lahir"
                                        value="{{ old('tanggal_lahir', $user->santri?->tanggal_lahir ?? '') }}"></x-input>
                                </div>
                                <div class="col-6">
                                    <x-select-option label='Jenis Kelamin' id="jenis_kelamin" name='jenis_kelamin'
                                        attribute="required">
                                        <option value="">Pilih jenis kelamin</option>
                                        <option value="Laki-Laki"
                                            {{ old('jenis_kelamin', $user->santri?->jenis_kelamin) === 'Laki-Laki' ? 'selected' : '' }}>
                                            Laki-Laki</option>
                                        <option value="Perempuan"
                                            {{ old('jenis_kelamin', $user->santri?->jenis_kelamin) === 'Perempuan' ? 'selected' : '' }}>
                                            Perempuan</option>
                                    </x-select-option>
                                </div>
                                <div class="col-6">
                                    <x-input type='text' label='Whatsapp' id="whatsapp" name='whatsapp'
                                        placeholder="08xxxxxxxxxx"
                                        value="{{ old('whatsapp', $user->santri?->whatsapp ?? '') }}"></x-input>
                                </div>
                                <div class="col-12">
                                    <x-input type='text' label='Alamat Lengkap' id="alamat_lengkap" name='alamat_lengkap'
                                        placeholder="Alamat Lengkap"
                                        value="{{ old('alamat_lengkap', $user->santri?->alamat_santri?->alamat_lengkap ?? '') }}"
                                        attribute="required"></x-input>
                                </div>
                                <div class="col-6">
                                    <x-input type='text' label='NIK' id="nik" name='nik'
                                        placeholder="Nomor Induk Kependudukan (16 digit)"
                                        value="{{ old('nik', $user->santri?->nik ?? '') }}"></x-input>
                                </div>
                                <div class="col-6">
                                    <x-input type='text' label='KK' id="kk" name='kk'
                                        placeholder="Nomor Kartu Keluarga (16 digit)"
                                        value="{{ old('kk', $user->santri?->kk ?? '') }}"></x-input>
                                </div>
                                <div class="col-6">
                                    <label class="form-label">Tahun Masuk Masehi</label>
                                    <input type="text" class="form-control"
                                        value="{{ $user->santri?->tahun_masuk ?? '-' }}" readonly>
                                </div>
                                <div class="col-6">
                                    <label class="form-label">Tahun Masuk Hijriyah</label>
                                    <input type="text" class="form-control"
                                        value="{{ $user->santri?->tahun_masuk_hijriyah ?? '-' }}" readonly>
                                </div>
                                @if ($user->santri?->status == 'Santri Alumni')
                                    <div class="col-6">
                                        <label class="form-label">Tanggal Boyong Masehi</label>
                                        <input type="text" class="form-control"
                                            value="{{ $user->santri?->tanggal_boyong ?? '-' }}" readonly>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label">Tanggal Boyong Hijriyah</label>
                                        <input type="text" class="form-control"
                                            value="{{ $user->santri?->tanggal_boyong_hijriyah ?? '-' }}" readonly>
                                    </div>
                                @endif
                                <div class="col-12">
                                    <x-input type='file' label='Foto Profil' id="foto" name='foto' accept=".jpg,.jpeg,.png,.webp" helper="Format: JPG, JPEG, PNG, WEBP (Maks 2MB)"></x-input>
                                    <div id="foto-preview-container" class="mt-2 d-none">
                                        <p class="font-12 text-muted mb-1">Pratinjau Foto Baru:</p>
                                        <img id="foto-preview-img" src="#" alt="Pratinjau Foto" class="rounded border p-1" style="max-height: 120px; object-fit: cover;">
                                    </div>
                                </div>
                                <div class="row mt-4">
                                    <div class="col-6">
                                        <button type="submit" class="btn btn-primary">Simpan Biodata</button>
                                    </div>
                                </div>
                            </form>
                        @else
                            <h5 class="fw-bold">Informasi Profil Pegawai</h5>
                            <div class="alert alert-light border radius-10 p-3 mt-3">
                                <div class="d-flex align-items-center">
                                    <div class="font-30 text-primary me-3"><i class='bx bx-id-card'></i></div>
                                    <div>
                                        <h6 class="mb-1 text-primary fw-bold">Akun Terdaftar: {{ $user->roles->first()?->name ?? 'Pengguna' }}</h6>
                                        <p class="mb-0 text-muted font-13">Pengaturan biodata santri khusus untuk santri terdaftar. Sebagai tenaga pengajar/staf, Anda dapat memperbarui nama, email, dan kata sandi akun pada formulir Pengaturan Akun di sebelah kiri.</p>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('js')
<script>
    $(document).ready(function() {
        $('#foto').on('change', function() {
            var file = this.files && this.files[0];
            if (file) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    $('#foto-preview-img').attr('src', e.target.result);
                    $('#foto-preview-container').removeClass('d-none');
                }
                reader.readAsDataURL(file);
            } else {
                $('#foto-preview-container').addClass('d-none');
            }
        });
    });
</script>
@endpush
