<div class="btn-group pull-right">
    <button data-bs-toggle="modal" data-bs-target="#editModal-{{ $model->id }}" class="btn btn-sm btn-primary">
        <span class="bx bx-edit"></span>
    </button>

    <x-edit-modal title="Edit Angkatan Santri" id="{{ $model->id }}" fn="{{ route('student-batch.update', $model->id) }}"
        method="POST">
        @csrf
        @method('PATCH')
        <div class="mb-3">
            <x-input type='text' name='name' id="name-{{ $model->id }}" label='Nama Angkatan'
                value="{{ $model->name }}" attribute="required"></x-input>
        </div>
        <div class="mb-3">
            <x-input type='number' name='year' id="year-{{ $model->id }}" label='Tahun Masuk'
                value="{{ $model->year }}" attribute="required min=2000 max=2100"></x-input>
        </div>
        <div class="mb-3">
            <label class="form-label font-weight-bold">Keterangan</label>
            <textarea name="description" class="form-control" rows="3">{{ $model->description }}</textarea>
        </div>
    </x-edit-modal>

    <button data-bs-toggle="modal" data-bs-target="#deleteModal-{{ $model->id }}" class="btn btn-sm btn-danger">
        <span class="bx bx-trash"></span>
    </button>

    <x-delete-modal title='Hapus Angkatan Santri' id="{{ $model->id }}" fn="{{ route('student-batch.destroy', $model->id) }}"
        entity="Angkatan: {{ $model->name }} ({{ $model->year }})" method="POST">
        @csrf
        @method('DELETE')
    </x-delete-modal>
</div>
