<div class="btn-group pull-right">
    <button data-bs-toggle="modal" data-bs-target="#editModal-{{ $model->id }}" class="btn btn-sm btn-primary" title="Edit">
        <span class="bx bx-edit"></span>
    </button>

    <x-edit-modal title="Edit Mata Pelajaran" id="{{ $model->id }}" fn="{{ route('mapel.update', $model->id) }}"
        method="POST">
        @csrf
        @method('PATCH')
        <div class="mb-3">
            <label class="form-label font-weight-bold">Kelas <span class="text-danger">*</span></label>
            <select name="kelas_id" class="form-select" required>
                @foreach ($kelasList as $k)
                    <option value="{{ $k->id }}" {{ $model->kelas_id == $k->id ? 'selected' : '' }}>
                        {{ $k->tingkatan }} - {{ $k->kelas }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="mb-3">
            <x-input type='text' name='code' id="code-{{ $model->id }}" label='Kode Mata Pelajaran'
                value="{{ $model->code }}" attribute="required"></x-input>
        </div>
        <div class="mb-3">
            <x-input type='text' name='name' id="name-{{ $model->id }}" label='Nama Mata Pelajaran'
                value="{{ $model->name }}" attribute="required"></x-input>
        </div>
        <div class="mb-3">
            <label class="form-label font-weight-bold">Keterangan / Deskripsi</label>
            <textarea name="description" class="form-control" rows="3">{{ $model->description }}</textarea>
        </div>
        <div class="form-check form-switch mb-3">
            <input class="form-check-input" type="checkbox" name="is_active" id="is_active-{{ $model->id }}" value="1"
                {{ $model->is_active ? 'checked' : '' }}>
            <label class="form-check-label" for="is_active-{{ $model->id }}">Status Aktif</label>
        </div>
    </x-edit-modal>

    <button data-bs-toggle="modal" data-bs-target="#deleteModal-{{ $model->id }}" class="btn btn-sm btn-danger" title="Hapus">
        <span class="bx bx-trash"></span>
    </button>

    <x-delete-modal title='Hapus Mata Pelajaran' id="{{ $model->id }}" fn="{{ route('mapel.destroy', $model->id) }}"
        entity="Mata Pelajaran: {{ $model->name }} ({{ $model->code }})" method="POST">
        @csrf
        @method('DELETE')
    </x-delete-modal>
</div>
