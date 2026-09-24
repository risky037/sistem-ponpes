@extends('layouts.app')

@section('title', 'Jadwal Pelajaran | DIGITREN')

@section('content')
    <div class="page-wrapper">
        <div class="page-content-wrapper">
            <div class="page-content">
                <x-breadcrumb url="{{ route('dashboard') }}" path='Jadwal Pelajaran'></x-breadcrumb>
                <div class="card radius-15 border shadow-sm">
                    <div class="card-body">
                        <x-card-toolbar title="Jadwal Pelajaran Kelas">
                            <div>
                                <select id="filter_academic_year" class="form-select form-select-sm">
                                    <option value="">Semua Tahun Ajaran</option>
                                    @foreach ($academicYears as $ay)
                                        <option value="{{ $ay->id }}" {{ $activeYear?->id === $ay->id ? 'selected' : '' }}>
                                            {{ $ay->name }} ({{ $ay->semester }}) {{ $ay->is_active ? '★' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <select id="filter_kelas" class="form-select form-select-sm">
                                    <option value="">Semua Kelas</option>
                                    @foreach ($kelasList as $k)
                                        <option value="{{ $k->id }}">
                                            {{ $k->tingkatan }} - {{ $k->kelas }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <select id="filter_day" class="form-select form-select-sm">
                                    <option value="">Semua Hari</option>
                                    @foreach ($days as $day)
                                        <option value="{{ $day }}">{{ $day }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <select id="filter_teacher" class="form-select form-select-sm">
                                    <option value="">Semua Pengajar</option>
                                    @foreach ($teachers ?? [] as $t)
                                        <option value="{{ $t->id }}">{{ $t->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <select id="filter_mapel" class="form-select form-select-sm">
                                    <option value="">Semua Mapel</option>
                                    @foreach ($mapels ?? [] as $m)
                                        <option value="{{ $m->id }}">{{ $m->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal"
                                data-bs-target="#createModal">
                                <i class="bx bx-plus"></i>
                                Tambah Jadwal
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
                                                <th>Hari</th>
                                                <th>Jam</th>
                                                <th>Kelas</th>
                                                <th>Mata Pelajaran</th>
                                                <th>Pengajar</th>
                                                <th>Ruangan</th>
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

    <x-modal-form id='createModal' title='Tambah Jadwal Pelajaran' fn="{{ route('class-schedule.store') }}" method="POST">
        @csrf
        <div class="mb-3">
            <label class="form-label font-weight-bold">Tahun Ajaran <span class="text-danger">*</span></label>
            <select name="academic_year_id" id="modal_academic_year_id" class="form-select" required>
                <option value="">Pilih Tahun Ajaran</option>
                @foreach ($academicYears as $ay)
                    <option value="{{ $ay->id }}" {{ $activeYear?->id === $ay->id ? 'selected' : '' }}>
                        {{ $ay->name }} ({{ $ay->semester }}) {{ $ay->is_active ? '(Aktif)' : '' }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label font-weight-bold">Kelas <span class="text-danger">*</span></label>
            <select name="kelas_id" id="modal_kelas_id" class="form-select" required>
                <option value="">Pilih Kelas</option>
                @foreach ($kelasList as $k)
                    <option value="{{ $k->id }}">{{ $k->tingkatan }} - {{ $k->kelas }}</option>
                @endforeach
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label font-weight-bold">Mata Pelajaran & Pengajar <span class="text-danger">*</span></label>
            <select name="teaching_assignment_id" id="modal_teaching_assignment_id" class="form-select" required>
                <option value="">Pilih Mata Pelajaran & Pengajar</option>
                @foreach ($teachingAssignments as $ta)
                    <option value="{{ $ta->id }}" data-kelas="{{ $ta->kelas_id }}" data-year="{{ $ta->academic_year_id }}">
                        {{ $ta->mapel?->name }} ({{ $ta->mapel?->code }}) - Ustadz {{ $ta->user?->name }} [{{ $ta->kelas?->kelas }}]
                    </option>
                @endforeach
            </select>
        </div>
        <div class="row">
            <div class="col-md-4 mb-3">
                <label class="form-label font-weight-bold">Hari <span class="text-danger">*</span></label>
                <select name="day_of_week" class="form-select" required>
                    @foreach ($days as $day)
                        <option value="{{ $day }}">{{ $day }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label font-weight-bold">Waktu Mulai <span class="text-danger">*</span></label>
                <input type="time" name="start_time" class="form-control" required value="07:30">
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label font-weight-bold">Waktu Selesai <span class="text-danger">*</span></label>
                <input type="time" name="end_time" class="form-control" required value="09:00">
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label font-weight-bold">Ruangan / Tempat</label>
            <input type="text" name="room" class="form-control" placeholder="contoh: Gedung A Lt. 2 / Masjid">
        </div>
        <div class="mb-3">
            <label class="form-label font-weight-bold">Catatan</label>
            <textarea name="notes" class="form-control" rows="2" placeholder="Catatan tambahan opsional"></textarea>
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
                    url: "{{ route('class-schedule.index') }}",
                    data: function(d) {
                        d.academic_year_id = $('#filter_academic_year').val();
                        d.kelas_id = $('#filter_kelas').val();
                        d.day_of_week = $('#filter_day').val();
                        d.user_id = $('#filter_teacher').val();
                        d.mapel_id = $('#filter_mapel').val();
                    }
                },
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'day_badge', name: 'class_schedules.day_of_week' },
                    { data: 'time_range', name: 'class_schedules.start_time' },
                    { data: 'kelas_name', name: 'kelas_name' },
                    { data: 'mapel_name', name: 'mapel_name' },
                    { data: 'teacher_name', name: 'teacher_name' },
                    { data: 'room', name: 'class_schedules.room' },
                    { data: 'action', name: 'action', orderable: false, searchable: false },
                ]
            });

            $('#filter_academic_year, #filter_kelas, #filter_day, #filter_teacher, #filter_mapel').on('change', function() {
                table.ajax.reload();
            });

            // Filter teaching assignment options based on selected kelas & academic year
            function filterTeachingAssignments() {
                var selectedKelas = $('#modal_kelas_id').val();
                var selectedYear = $('#modal_academic_year_id').val();

                $('#modal_teaching_assignment_id option').each(function() {
                    var optKelas = $(this).data('kelas');
                    var optYear = $(this).data('year');

                    if (!optKelas && !optYear) {
                        $(this).show();
                        return;
                    }

                    var matchKelas = !selectedKelas || optKelas == selectedKelas;
                    var matchYear = !selectedYear || optYear == selectedYear;

                    if (matchKelas && matchYear) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            }

            $('#modal_kelas_id, #modal_academic_year_id').on('change', filterTeachingAssignments);
        });
    </script>
@endpush
