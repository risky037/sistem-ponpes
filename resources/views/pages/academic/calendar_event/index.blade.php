@extends('layouts.app')

@section('title', 'Kalender Akademik | DIGITREN')

@section('content')
    <div class="page-wrapper">
        <div class="page-content-wrapper">
            <div class="page-content">
                <x-breadcrumb url="{{ route('dashboard') }}" path='Kalender Akademik'></x-breadcrumb>
                <div class="card radius-15 border shadow-sm">
                    <div class="card-body">
                        <x-card-toolbar title="Agenda Kalender Akademik">
                            <div>
                                <select id="filter_academic_year" class="form-select form-select-sm">
                                    <option value="">Semua Tahun Ajaran</option>
                                    @foreach ($academicYears as $ay)
                                        <option value="{{ $ay->id }}" {{ $activeYear && $activeYear->id === $ay->id ? 'selected' : '' }}>
                                            {{ $ay->name }} ({{ $ay->semester }}) {{ $ay->is_active ? '★' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <select id="filter_type" class="form-select form-select-sm">
                                    <option value="">Semua Kategori</option>
                                    @foreach ($eventTypes as $type)
                                        <option value="{{ $type }}">{{ $type }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal"
                                data-bs-target="#createModal">
                                <i class="bx bx-plus"></i>
                                Tambah Agenda
                            </button>
                        </x-card-toolbar>

                        <div class="row">
                            <div class="col">
                                <div class="table-responsive">
                                    <table class="table table-hover table-bordered align-middle dataTable" style="width:100%" role="grid"
                                        id="table">
                                        <thead>
                                            <tr>
                                                <th style="width: 5%">#</th>
                                                <th>Tanggal</th>
                                                <th>Agenda Kegiatan</th>
                                                <th>Kategori</th>
                                                <th>Tahun Ajaran</th>
                                                <th>Keterangan</th>
                                                <th style="width: 15%">Aksi</th>
                                            </tr>
                                        </thead>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <x-modal-form id='createModal' title='Tambah Agenda Kalender Akademik' fn="{{ route('academic-calendar-event.store') }}" method="POST">
        @csrf
        <div class="mb-3">
            <label class="form-label font-weight-bold">Tahun Ajaran <span class="text-danger">*</span></label>
            <select name="academic_year_id" class="form-select" required>
                <option value="">Pilih Tahun Ajaran</option>
                @foreach ($academicYears as $ay)
                    <option value="{{ $ay->id }}" {{ $activeYear && $activeYear->id === $ay->id ? 'selected' : '' }}>
                        {{ $ay->name }} ({{ $ay->semester }}) {{ $ay->is_active ? '(Aktif)' : '' }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="mb-3">
            <x-input type='text' name='title' id="title" label='Nama Agenda / Kegiatan'
                placeholder='contoh: Libur Hari Raya Idul Fitri' attribute="required"></x-input>
        </div>
        <div class="mb-3">
            <label class="form-label font-weight-bold">Kategori Agenda <span class="text-danger">*</span></label>
            <select name="event_type" class="form-select" required>
                @foreach ($eventTypes as $type)
                    <option value="{{ $type }}">{{ $type }}</option>
                @endforeach
            </select>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label font-weight-bold">Tanggal Mulai <span class="text-danger">*</span></label>
                <input type="date" name="start_date" class="form-control" required value="{{ date('Y-m-d') }}">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label font-weight-bold">Tanggal Selesai <span class="text-danger">*</span></label>
                <input type="date" name="end_date" class="form-control" required value="{{ date('Y-m-d') }}">
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label font-weight-bold">Keterangan / Catatan</label>
            <textarea name="description" class="form-control" rows="3" placeholder="Keterangan agenda opsional"></textarea>
        </div>
    </x-modal-form>
@endsection

@push('js')
    <script>
        $(document).ready(function() {
            var table = $('#table').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                ajax: {
                    url: "{{ route('academic-calendar-event.index') }}",
                    data: function(d) {
                        d.academic_year_id = $('#filter_academic_year').val();
                        d.event_type = $('#filter_type').val();
                    }
                },
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'date_range', name: 'academic_calendar_events.start_date' },
                    { data: 'title', name: 'academic_calendar_events.title' },
                    { data: 'type_badge', name: 'academic_calendar_events.event_type' },
                    { data: 'academic_year_name', name: 'academic_year_name' },
                    { data: 'description', name: 'academic_calendar_events.description' },
                    { data: 'action', name: 'action', orderable: false, searchable: false },
                ]
            });

            $('#filter_academic_year, #filter_type').on('change', function() {
                table.ajax.reload();
            });
        });
    </script>
@endpush
