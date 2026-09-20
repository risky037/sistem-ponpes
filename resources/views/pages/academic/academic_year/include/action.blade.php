<div class="btn-group pull-right">
    <button data-bs-toggle="modal" data-bs-target="#editModal-{{ $model->id }}" class="btn btn-sm btn-primary">
        <span class="bx bx-edit"></span>
    </button>

    <x-edit-modal title="Edit Tahun Ajaran" id="{{ $model->id }}" fn="{{ route('academic-year.update', $model->id) }}"
        method="POST">
        @csrf
        @method('PATCH')
        <div class="mb-3">
            <x-input type='text' name='name' id="name-{{ $model->id }}" label='Tahun Ajaran' placeholder='contoh: 2026/2027'
                value="{{ $model->name }}"></x-input>
        </div>
        <div class="mb-3">
            <label class="form-label">Semester</label>
            <select name="semester" class="form-select" required>
                <option value="Ganjil" {{ $model->semester === 'Ganjil' ? 'selected' : '' }}>Ganjil</option>
                <option value="Genap" {{ $model->semester === 'Genap' ? 'selected' : '' }}>Genap</option>
            </select>
        </div>
        <div class="mb-3">
            <x-input type='date' name='start_date' id="start_date-{{ $model->id }}" label='Tanggal Mulai'
                value="{{ $model->start_date?->format('Y-m-d') }}"></x-input>
        </div>
        <div class="mb-3">
            <x-input type='date' name='end_date' id="end_date-{{ $model->id }}" label='Tanggal Selesai'
                value="{{ $model->end_date?->format('Y-m-d') }}"></x-input>
        </div>
        <div class="mb-3 form-check">
            <input type="checkbox" class="form-check-input" id="is_active-{{ $model->id }}" name="is_active" value="1"
                {{ $model->is_active ? 'checked' : '' }}>
            <label class="form-check-label" for="is_active-{{ $model->id }}">Tahun Ajaran Aktif</label>
        </div>
    </x-edit-modal>

    <button data-bs-toggle="modal" data-bs-target="#deleteModal-{{ $model->id }}" class="btn btn-sm btn-danger">
        <span class="bx bx-trash"></span>
    </button>

    <x-delete-modal title='Hapus Tahun Ajaran' id="{{ $model->id }}" fn="{{ route('academic-year.destroy', $model->id) }}"
        method="POST">
        @csrf
        @method('DELETE')
    </x-delete-modal>
</div>
