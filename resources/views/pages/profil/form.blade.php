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
                                    value="{{ old('name') ?? (old('name') ? old('name') : $user->name) }}"></x-input>
                            </div>
                            <div class="col-12">
                                <x-input type='email' label='Email' id="email" name='email' placeholder="Email"
                                    value="{{ old('email') ?? (old('email') ? old('email') : $user->email) }}"></x-input>
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
                                <button type="submit" class="btn btn-primary">Simpan</button>
                            </div>
                        </form>
                    </div>
                    <div class="col-12 col-lg-7">
                        @if (Auth::user()->roles->first()->name !== 'Administrator' && Auth::user()->roles->first()->name != 'Keuangan')
                            <h5 class="fw-bold">Pengaturan Biodata</h5>
                            <form class="row g-3" action="{{ route('profil.biodata', $user->id) }}" method="POST">
                                @csrf
                                <div class="col-12">
                                    <p class="mb-0">Tempat/Tanggal Lahir</p>
                                </div>
                                <div class="col-12 col-lg-6">
                                    <x-input type='text' label='Tempat Lahir' id="tempat_lahir" name='tempat_lahir'
                                        placeholder="tempat_lahir"
                                        value="{{ old('tempat_lahir') ?? (old('tempat_lahir') ? old('tempat_lahir') : $user->santri->tempat_lahir) }}"></x-input>
                                </div>
                                <div class="col-12 col-lg-6">
                                    <x-input type='date' label='Tanggal Lahir' id="tanggal_lahir"
                                        name='tanggal_lahir' placeholder="tanggal_lahir"
                                        value="{{ old('tanggal_lahir') ?? (old('tanggal_lahir') ? old('tanggal_lahir') : $user->santri->tanggal_lahir) }}"></x-input>
                                </div>
                                <div class="col-6">
                                    <x-select-option label='Jenis Kelamin' id="jenis_kelamin" name='jenis_kelamin'
                                        attribute="required">
                                        <option value="">Pilih jenis kelamin</option>
                                        <option value="Laki-Laki"
                                            {{ old('jenis_kelamin') != null ? (old('jenis_kelamin') === 'Laki-Laki' ? 'selected' : '') : ($user->santri->jenis_kelamin === 'Laki-Laki' ? 'selected' : '') }}>
                                            Laki-Laki</option>
                                        <option value="Perempuan"
                                            {{ old('jenis_kelamin') != null ? (old('jenis_kelamin') === 'Perempuan' ? 'selected' : '') : ($user->santri->jenis_kelamin === 'Perempuan' ? 'selected' : '') }}>
                                            Perempuan</option>
                                    </x-select-option>
                                </div>
                                <div class="col-6">
                                    <x-input type='number' label='Whatsapp' id="whatsapp" name='whatsapp'
                                        placeholder="whatsapp"
                                        value="{{ old('whatsapp') ?? (old('whatsapp') ? old('whatsapp') : $user->santri->whatsapp) }}"></x-input>
                                </div>
                                <div class="col-12">
                                    <x-input type='text' label='Alamat Lengkap' id="alamat_lengkap" name='alamat_lengkap'
                                        placeholder="Alamat Lengkap"
                                        value="{{ old('alamat_lengkap') ?? (old('alamat_lengkap') ? old('alamat_lengkap') : $user->santri?->alamat_santri?->alamat_lengkap) }}"
                                        attribute="required"></x-input>
                                </div>
                                <div class="col-6">
                                    <x-input type='number' label='NIK' id="nik" name='nik'
                                        placeholder="nik"
                                        value="{{ old('nik') ?? (old('nik') ? old('nik') : $user->santri->nik) }}"></x-input>
                                </div>
                                <div class="col-6">
                                    <x-input type='number' label='KK' id="kk" name='kk'
                                        placeholder="kk"
                                        value="{{ old('kk') ?? (old('kk') ? old('kk') : $user->santri->kk) }}"></x-input>
                                </div>
                                <div class="col-6">
                                    <label class="form-label">Tahun Masuk Masehi</label>
                                    <input type="date" class="form-control"
                                        value="{{ $user->santri->tahun_masuk }}" readonly>
                                </div>
                                <div class="col-6">
                                    <label class="form-label">Tahun Masuk Hijriyah</label>
                                    <input type="text" class="form-control"
                                        value="{{ $user->santri->tahun_masuk_hijriyah }}" readonly>
                                </div>
                                @if ($user->santri->status == 'Santri Alumni')
                                    <div class="col-6">
                                        <label class="form-label">Tanggal Boyong Masehi</label>
                                        <input type="date" class="form-control"
                                            value="{{ $user->santri->tanggal_boyong }}" readonly>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label">Tanggal Boyong Hijriyah</label>
                                        <input type="text" class="form-control"
                                            value="{{ $user->santri->tanggal_boyong_hijriyah }}" readonly>
                                    </div>
                                @endif
                                <div class="col-12">
                                    <x-input type='file' label='Foto' id="foto" name='foto'></x-input>
                                </div>
                                <div class="row mt-4">
                                    <div class="col-6">
                                        <button type="submit" class="btn btn-primary">Simpan</button>
                                    </div>
                                </div>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
