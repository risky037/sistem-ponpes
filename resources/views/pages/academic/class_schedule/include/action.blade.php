<div class="btn-group pull-right">
    <button data-bs-toggle="modal" data-bs-target="#editModal-{{ $model->id }}" class="btn btn-sm btn-primary" title="Edit Jadwal">
        <span class="bx bx-edit"></span>
    </button>

    <x-edit-modal title="Edit Jadwal Pelajaran" id="{{ $model->id }}" fn="{{ route('class-schedule.update', $model->id) }}"
        method="POST">
        @csrf
        @method('PATCH')
        <div class="mb-3">
            <label class="form-label font-weight-bold">Kelas & Mata Pelajaran</label>
            <input type="text" class="form-control" value="{{ $model->kelas ? $model->kelas->tingkatan.' - '.$model->kelas->kelas : '-' }} | {{ $model->teachingAssignment?->mapel?->name }} (Ustadz {{ $model->teachingAssignment?->user?->name }})" readonly disabled>
        </div>
        <div class="row">
            <div class="col-md-4 mb-3">
                <label class="form-label font-weight-bold">Hari <span class="text-danger">*</span></label>
                <select name="day_of_week" class="form-select" required>
                    @foreach ($days as $day)
                        <option value="{{ $day }}" {{ $model->day_of_week === $day ? 'selected' : '' }}>{{ $day }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label font-weight-bold">Waktu Mulai <span class="text-danger">*</span></label>
                <input type="time" name="start_time" class="form-control" required value="{{ substr($model->start_time, 0, 5) }}">
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label font-weight-bold">Waktu Selesai <span class="text-danger">*</span></label>
                <input type="time" name="end_time" class="form-control" required value="{{ substr($model->end_time, 0, 5) }}">
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label font-weight-bold">Ruangan / Tempat</label>
            <input type="text" name="room" class="form-control" value="{{ $model->room }}" placeholder="Ruangan / Tempat">
        </div>
        <div class="mb-3">
            <label class="form-label font-weight-bold">Catatan</label>
            <textarea name="notes" class="form-control" rows="2">{{ $model->notes }}</textarea>
        </div>
    </x-edit-modal>

    <button data-bs-toggle="modal" data-bs-target="#deleteModal-{{ $model->id }}" class="btn btn-sm btn-danger" title="Hapus Jadwal">
        <span class="bx bx-trash"></span>
    </button>

    <x-delete-modal title='Hapus Jadwal Pelajaran' id="{{ $model->id }}" fn="{{ route('class-schedule.destroy', $model->id) }}"
        entity="Jadwal: {{ $model->day_of_week }} ({{ substr($model->start_time, 0, 5) }}-{{ substr($model->end_time, 0, 5) }}) - {{ $model->teachingAssignment?->mapel?->name }} [{{ $model->kelas?->kelas }}]" method="POST">
        @csrf
        @method('DELETE')
    </x-delete-modal>
</div>
