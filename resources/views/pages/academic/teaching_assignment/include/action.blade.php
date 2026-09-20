<div class="btn-group pull-right">
    <button data-bs-toggle="modal" data-bs-target="#editModal-{{ $model->id }}" class="btn btn-sm btn-primary" title="Edit Penugasan">
        <span class="bx bx-edit"></span>
    </button>

    <x-edit-modal title="Edit Penugasan Mengajar" id="{{ $model->id }}" fn="{{ route('teaching-assignment.update', $model->id) }}"
        method="POST">
        @csrf
        @method('PATCH')
        <div class="mb-3">
            <label class="form-label font-weight-bold">Tahun Ajaran</label>
            <input type="text" class="form-control" value="{{ $model->academic_year ? $model->academic_year->name.' ('.$model->academic_year->semester.')' : '-' }}" readonly disabled>
        </div>
        <div class="mb-3">
            <label class="form-label font-weight-bold">Kelas & Mata Pelajaran</label>
            <input type="text" class="form-control" value="{{ $model->kelas ? $model->kelas->tingkatan.' - '.$model->kelas->kelas : '-' }} | {{ $model->mapel?->name }} ({{ $model->mapel?->code }})" readonly disabled>
        </div>
        <div class="mb-3">
            <label class="form-label font-weight-bold">Pengajar (Ustadz / Ustadzah) <span class="text-danger">*</span></label>
            <select name="user_id" class="form-select" required>
                @foreach ($teachers as $t)
                    <option value="{{ $t->id }}" {{ $model->user_id == $t->id ? 'selected' : '' }}>
                        {{ $t->name }} ({{ $t->roles->pluck('name')->implode(', ') }})
                    </option>
                @endforeach
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label font-weight-bold">Status Penugasan <span class="text-danger">*</span></label>
            <select name="status" class="form-select" required>
                <option value="Aktif" {{ $model->status === 'Aktif' ? 'selected' : '' }}>Aktif</option>
                <option value="Nonaktif" {{ $model->status === 'Nonaktif' ? 'selected' : '' }}>Nonaktif</option>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label font-weight-bold">Catatan</label>
            <textarea name="notes" class="form-control" rows="3">{{ $model->notes }}</textarea>
        </div>
    </x-edit-modal>

    @if ($model->status === 'Aktif')
        <button data-bs-toggle="modal" data-bs-target="#deleteModal-{{ $model->id }}" class="btn btn-sm btn-outline-warning" title="Nonaktifkan Penugasan">
            <span class="bx bx-block"></span>
        </button>

        <x-delete-modal
            title="Nonaktifkan Penugasan Mengajar"
            subtitle="Konfirmasi perubahan status penugasan"
            id="{{ $model->id }}"
            fn="{{ route('teaching-assignment.destroy', $model->id) }}"
            entity="Pengajar: {{ $model->user?->name }} - {{ $model->mapel?->name }} (Kelas {{ $model->kelas?->kelas }})"
            message="Apakah Anda yakin ingin menonaktifkan penugasan mengajar ini? Status akan diubah menjadi Nonaktif dan riwayat kurikulum tetap dipertahankan."
            btnText="Ya, Nonaktifkan"
            btnClass="btn-warning text-dark"
            btnIcon="bx-block"
            iconColor="warning"
            icon="bx-info-circle"
            method="POST">
            @csrf
            @method('DELETE')
        </x-delete-modal>
    @endif
</div>
