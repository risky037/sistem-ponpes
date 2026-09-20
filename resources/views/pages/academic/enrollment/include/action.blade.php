<div class="btn-group pull-right">
    <button data-bs-toggle="modal" data-bs-target="#editModal-{{ $model->id }}" class="btn btn-sm btn-primary" title="Ubah Penempatan / Status">
        <span class="bx bx-edit"></span>
    </button>

    <x-edit-modal title="Ubah Pendaftaran Akademik" id="{{ $model->id }}" fn="{{ route('academic-enrollment.update', $model->id) }}"
        method="POST">
        @csrf
        @method('PATCH')
        <div class="mb-3">
            <label class="form-label font-weight-bold">Santri</label>
            <input type="text" class="form-control" value="{{ $model->santri?->nama_lengkap }} ({{ $model->santri?->no_induk }})" disabled readonly>
        </div>
        <div class="mb-3">
            <label class="form-label font-weight-bold">Tahun Ajaran</label>
            <input type="text" class="form-control" value="{{ $model->academic_year?->name }} (Semester {{ $model->academic_year?->semester }})" disabled readonly>
        </div>
        <div class="mb-3">
            <label class="form-label font-weight-bold">Kelas Akademik <span class="text-danger">*</span></label>
            <select name="kelas_id" class="form-select" required>
                @foreach ($kelasList as $k)
                    <option value="{{ $k->id }}" {{ $model->kelas_id == $k->id ? 'selected' : '' }}>
                        {{ $k->tingkatan }} - {{ $k->kelas }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label font-weight-bold">Status <span class="text-danger">*</span></label>
            <select name="status" class="form-select" required>
                <option value="Aktif" {{ $model->status === 'Aktif' ? 'selected' : '' }}>Aktif</option>
                <option value="Nonaktif" {{ $model->status === 'Nonaktif' ? 'selected' : '' }}>Nonaktif</option>
                <option value="Lulus" {{ $model->status === 'Lulus' ? 'selected' : '' }}>Lulus</option>
                <option value="Pindah" {{ $model->status === 'Pindah' ? 'selected' : '' }}>Pindah</option>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label font-weight-bold">Catatan</label>
            <textarea name="notes" class="form-control" rows="2">{{ $model->notes }}</textarea>
        </div>
    </x-edit-modal>

    @if ($model->status === 'Aktif')
        <button data-bs-toggle="modal" data-bs-target="#deleteModal-{{ $model->id }}" class="btn btn-sm btn-outline-warning" title="Nonaktifkan Penempatan">
            <span class="bx bx-user-x"></span>
        </button>

        <x-delete-modal
            title="Nonaktifkan Penempatan Santri"
            subtitle="Konfirmasi perubahan status akademik"
            id="{{ $model->id }}"
            fn="{{ route('academic-enrollment.destroy', $model->id) }}"
            entity="Santri: {{ $model->santri?->user?->name ?? $model->santri?->nama_lengkap }} (Kelas: {{ $model->kelas?->kelas }})"
            message="Apakah Anda yakin ingin menonaktifkan penempatan akademik santri ini? Status akan diubah menjadi Nonaktif dan riwayat akademik tetap dipertahankan."
            btnText="Ya, Nonaktifkan"
            btnClass="btn-warning text-dark"
            btnIcon="bx-user-x"
            iconColor="warning"
            icon="bx-info-circle"
            method="POST">
            @csrf
            @method('DELETE')
        </x-delete-modal>
    @endif
</div>
