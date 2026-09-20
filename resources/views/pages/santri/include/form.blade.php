<div class="row mb-2">
    <div class="col-md-4">
        <div class="mb-2">
            <x-input type='text' name='no_induk' id="no_induk" label='No Induk' placeholder='No Induk'
                attribute='readonly'
                value="{{ isset($item) ? $item->no_induk : 'No induk otomatis sesuai tahun masuk' }}"></x-input>
        </div>
    </div>
    <div class="col-md-4">
        <div class="mb-2">
            <x-input type="date" label='Tahun masuk' id="tahun_masuk" name='tahun_masuk' placeholder='Tahun masuk'
                value="{{ isset($item) ? $item->tahun_masuk : old('tahun_masuk') }}" attribute="required"></x-input>
        </div>
    </div>
    <div class="col-md-4">
        <div class="mb-2">
            <label class="form-label font-weight-bold" for="student_batch_id">Angkatan Santri</label>
            <select name="student_batch_id" id="student_batch_id" class="form-select">
                <option value="">-- Pilih Angkatan (Opsional) --</option>
                @foreach ($studentBatches ?? [] as $batch)
                    <option value="{{ $batch->id }}"
                        {{ (old('student_batch_id') == $batch->id) || (isset($item) && $item->student_batch_id == $batch->id) ? 'selected' : '' }}>
                        {{ $batch->name }} ({{ $batch->year }})
                    </option>
                @endforeach
            </select>
        </div>
    </div>
</div>
<div class="row mb-2">
    <div class="col">
        <div class="mb-2">
            <x-input type='text' name='nama_lengkap' id="nama_lengkap" label='Nama Lengkap'
                placeholder='Nama Lengkap' value="{{ isset($item) ? $item->user->name : old('nama_lengkap') }}"
                attribute="required"></x-input>
        </div>
    </div>
    <div class="col">
        <div class="mb-2">
            <x-select-option label='Jenis Kelamin' id="jenis_kelamin" name='jenis_kelamin' attribute="required">
                <option value="">Pilih jenis kelamin</option>
                <option value="Laki-Laki"
                    {{ old('jenis_kelamin') != null
                        ? (old('jenis_kelamin') === 'Laki-Laki'
                            ? 'selected'
                            : '')
                        : (isset($item)
                            ? ($item->jenis_kelamin === 'Laki-Laki'
                                ? 'selected'
                                : '')
                            : '') }}>
                    Laki-Laki</option>
                <option value="Perempuan"
                    {{ old('jenis_kelamin') != null
                        ? (old('jenis_kelamin') === 'Perempuan'
                            ? 'selected'
                            : '')
                        : (isset($item)
                            ? ($item->jenis_kelamin === 'Perempuan'
                                ? 'selected'
                                : '')
                            : '') }}>
                    Perempuan</option>
            </x-select-option>
        </div>
    </div>
</div>
<div class="row mb-2">
    <div class="col-12">
        <div class="mb-2">
            <x-input type='text' name='alamat_lengkap' id="alamat_lengkap" label='Alamat Lengkap'
                placeholder='Alamat Lengkap (Jl, Dusun, RT/RW, Desa, Kec, Kab, Prov)'
                value="{{ isset($item->alamat_santri) ? $item->alamat_santri->alamat_lengkap : old('alamat_lengkap') }}"
                attribute="required"></x-input>
        </div>
    </div>
</div>
<div class="row mb-2">
    <div class="col-6">
        <div class="mb-2">
            <x-input type='text' name='tempat_lahir' id="tempat_lahir" label='Tempat Lahir'
                placeholder='Tempat Lahir' value="{{ isset($item) ? $item->tempat_lahir : old('tempat_lahir') }}"
                attribute="required"></x-input>
        </div>
    </div>
    <div class="col-6">
        <div class="mb-2">
            <x-input type='date' name='tanggal_lahir' id="tanggal_lahir" label='Tanggal Lahir'
                placeholder='Tanggal Lahir' value="{{ isset($item) ? $item->tanggal_lahir : old('tanggal_lahir') }}"
                attribute="required"></x-input>
        </div>
    </div>
</div>
<div class="row mb-2">
    <div class="col-xs-12 col-lg col-md">
        <div class="mb-2">
            <x-input type='number' name='nik' id="nik" label='NIK' placeholder='NIK'
                value="{{ isset($item) ? $item->nik : old('nik') }}"></x-input>
        </div>
    </div>
    <div class="col-xs-12 col-lg col-md">
        <div class="mb-2">
            <x-input type='number' name='kk' id="kk" label='KK' placeholder='KK'
                value="{{ isset($item) ? $item->kk : old('kk') }}"></x-input>
        </div>
    </div>
</div>
<div class="row mb-2">
    <div class="col">
        <label for="whatsapp">Nomor Whatsapp Aktif</label>
        <div class="input-group mb-2">
            <span class="input-group-text">62</span>
            <input type="text" class="form-control" name="whatsapp" id="whatsapp"
                value="{{ isset($item) ? str_replace('62', '', $item->whatsapp) : old('whatsapp') }}" required
                placeholder='Nomor Whatsapp Aktif'>
        </div>
    </div>
</div>
<div class="row mb-2">
    <div class="col">
        <x-input type='text' label='Nama Ayah' id="nama_ayah" name='nama_ayah' placeholder='Nama Ayah'
            value="{{ isset($item) ? ($item->wali_santri ? $item->wali_santri->nama_ayah : '') : old('nama_ayah') }}"
            attribute="required"></x-input>
    </div>
    <div class="col">
        <x-input type='text' label='Nama Ibu' id="nama_ibu" name='nama_ibu' placeholder='Nama Ibu'
            value="{{ isset($item) ? ($item->wali_santri ? $item->wali_santri->nama_ibu : '') : old('nama_ibu') }}"
            attribute="required"></x-input>
    </div>
</div>
<div class="row mb-2 mt-3">
    <div class="col">
        @if (isset($item) && isset($item->kelas_santri))
            <div class="mb-2">
                <x-select-option name='kelas' id="kelas" label='Kelas'>
                    @foreach ($classes as $kls)
                        <option value="{{ $kls->id }}" attribute="required"
                            {{ $item->kelas_santri->kelas_id == $kls->id ? 'selected' : '' }}>
                            {{ $kls->tingkatan . ' - ' . $kls->kelas }}
                        </option>
                    @endforeach
                </x-select-option>
            </div>
        @else
            <div class="mb-2">
                <x-select-option name='kelas' id="kelas" label='Kelas'>
                    @foreach ($classes as $kls)
                        <option value="{{ $kls->id }}" attribute="required"
                            {{ old('kelas') != null ? (old('kelas') == $kls->id ? 'selected' : '') : '' }}>
                            {{ $kls->tingkatan . ' - ' . $kls->kelas }}
                        </option>
                    @endforeach
                </x-select-option>
            </div>
        @endif
    </div>
    <div class="col">
        @if (isset($item) && isset($item->kamar_santri))
            <div class="mb-2">
                <x-select-option name='kamar' id="kamar" label='Kamar'>
                    @foreach ($badroom as $kmr)
                        <option value="{{ $kmr->id }}" attribute="required"
                            {{ $item->kamar_santri->kamar_id == $kmr->id ? 'selected' : '' }}
                            {{ $kmr->jumlah_santri == $kmr->maksimal_santri ? 'disabled' : '' }}>
                            {{ $kmr->nama . ' - BLK ' . $kmr->blok . ' - ' . $kmr->jumlah_santri . ' SNTR' }}
                        </option>
                    @endforeach
                </x-select-option>
                <small class="text-muted" style="font-style: italic">Disabled jika sudah full</small>
            </div>
        @else
            <div class="mb-2">
                <x-select-option name='kamar' id="kamar" label='Kamar'>
                    @foreach ($badroom as $kmr)
                        <option value="{{ $kmr->id }}" attribute="required"
                            {{ old('kamar') != null ? (old('kamar') === $kmr->id ? 'selected' : '') : '' }}
                            {{ $kmr->jumlah_santri == $kmr->maksimal_santri ? 'disabled' : '' }}>
                            {{ $kmr->nama . ' - BLK ' . $kmr->blok . ' - ' . $kmr->jumlah_santri . ' SNTR' }}
                        </option>
                    @endforeach
                </x-select-option>
                <small class="text-muted" style="font-style: italic">Disabled jika sudah full</small>
            </div>
        @endif
    </div>
</div>
@isset($item)
    <div class="row">
        <div class="col">
            <img src="{{ url($item->foto == 'santri.png' ? 'img' : '/storage/uploads/santri') . '/' . $item->foto }}"
                alt="santri" width="70px" class="img-fluid">
        </div>
        <div class="col"></div>
    </div>
@endisset
<div class="row mb-2">
    <div class="col">
        <div class="mb-3">
            <x-input type='file' label='Foto' id="foto" name='foto'></x-input>
        </div>
    </div>
</div>
