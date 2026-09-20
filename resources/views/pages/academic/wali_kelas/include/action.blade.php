<div class="btn-group pull-right">
    <button data-bs-toggle="modal" data-bs-target="#editModal-{{ $model->id }}" class="btn btn-sm btn-primary" title="Edit">
        <span class="bx bx-edit"></span>
    </button>

    <x-edit-modal title="Edit Wali Kelas" id="{{ $model->id }}" fn="{{ route('wali-kelas-assignment.update', $model->id) }}"
        method="POST">
        @csrf
        @method('PATCH')
        <div class="mb-3">
            <label class="form-label font-weight-bold">Tahun Ajaran</label>
            <input type="text" class="form-control" value="{{ $model->academic_year ? $model->academic_year->name.' ('.$model->academic_year->semester.')' : '-' }}" readonly disabled>
        </div>
        <div class="mb-3">
            <label class="form-label font-weight-bold">Kelas</label>
            <input type="text" class="form-control" value="{{ $model->kelas ? $model->kelas->tingkatan.' - '.$model->kelas->kelas : '-' }}" readonly disabled>
        </div>
        <div class="mb-3">
            <label class="form-label font-weight-bold">Wali Kelas <span class="text-danger">*</span></label>
            <select name="user_id" class="form-select" required>
                @foreach ($teachers as $t)
                    <option value="{{ $t->id }}" {{ $model->user_id == $t->id ? 'selected' : '' }}>
                        {{ $t->name }} ({{ $t->roles->pluck('name')->implode(', ') }})
                    </option>
                @endforeach
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label font-weight-bold">Catatan Penugasan</label>
            <textarea name="notes" class="form-control" rows="3">{{ $model->notes }}</textarea>
        </div>
    </x-edit-modal>

    <button data-bs-toggle="modal" data-bs-target="#deleteModal-{{ $model->id }}" class="btn btn-sm btn-danger" title="Hapus">
        <span class="bx bx-trash"></span>
    </button>

    <x-delete-modal title='Hapus Penugasan Wali Kelas' id="{{ $model->id }}" fn="{{ route('wali-kelas-assignment.destroy', $model->id) }}"
        entity="Wali Kelas: {{ $model->user?->name }} (Kelas {{ $model->kelas?->kelas }} - {{ $model->academic_year?->name }})" method="POST">
        @csrf
        @method('DELETE')
    </x-delete-modal>
</div>
