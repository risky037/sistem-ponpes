<div class="btn-group pull-right">
    <button data-bs-toggle="modal" data-bs-target="#editModal-{{ $model->id }}" class="btn btn-sm btn-primary" title="Edit Agenda">
        <span class="bx bx-edit"></span>
    </button>

    <x-edit-modal title="Edit Agenda Kalender Akademik" id="{{ $model->id }}" fn="{{ route('academic-calendar-event.update', $model->id) }}"
        method="POST">
        @csrf
        @method('PATCH')
        <div class="mb-3">
            <label class="form-label font-weight-bold">Tahun Ajaran</label>
            <input type="text" class="form-control" value="{{ $model->academicYear ? $model->academicYear->name.' ('.$model->academicYear->semester.')' : '-' }}" readonly disabled>
        </div>
        <div class="mb-3">
            <x-input type='text' name='title' id="title-{{ $model->id }}" label='Nama Agenda / Kegiatan'
                value="{{ $model->title }}" attribute="required"></x-input>
        </div>
        <div class="mb-3">
            <label class="form-label font-weight-bold">Kategori Agenda <span class="text-danger">*</span></label>
            <select name="event_type" class="form-select" required>
                @foreach ($eventTypes as $type)
                    <option value="{{ $type }}" {{ $model->event_type === $type ? 'selected' : '' }}>{{ $type }}</option>
                @endforeach
            </select>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label font-weight-bold">Tanggal Mulai <span class="text-danger">*</span></label>
                <input type="date" name="start_date" class="form-control" required value="{{ $model->start_date?->format('Y-m-d') }}">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label font-weight-bold">Tanggal Selesai <span class="text-danger">*</span></label>
                <input type="date" name="end_date" class="form-control" required value="{{ $model->end_date?->format('Y-m-d') }}">
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label font-weight-bold">Keterangan / Catatan</label>
            <textarea name="description" class="form-control" rows="3">{{ $model->description }}</textarea>
        </div>
    </x-edit-modal>

    <button data-bs-toggle="modal" data-bs-target="#deleteModal-{{ $model->id }}" class="btn btn-sm btn-danger" title="Hapus Agenda">
        <span class="bx bx-trash"></span>
    </button>

    <x-delete-modal title='Hapus Agenda Kalender Akademik' id="{{ $model->id }}" fn="{{ route('academic-calendar-event.destroy', $model->id) }}"
        entity="Agenda: {{ $model->title }} ({{ $model->event_type }})" method="POST">
        @csrf
        @method('DELETE')
    </x-delete-modal>
</div>
