@extends('layouts.app')

@section('title', 'Komponen Penilaian | DIGITREN')

@section('content')
    <div class="page-wrapper">
        <div class="page-content-wrapper">
            <div class="page-content">
                <x-breadcrumb url="{{ route('dashboard') }}" path='Komponen Penilaian'></x-breadcrumb>
                <div class="card radius-15 border shadow-sm">
                    <div class="card-body">
                        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 pb-2 border-bottom gap-2">
                            <div>
                                <h5 class="mb-0 fw-bold">Komponen Penilaian Mata Pelajaran</h5>
                                <p class="text-muted small mb-0">Kelola komponen penilaian dan pembobotan spesifik per pengajar dan kelas</p>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <div class="me-2">
                                    <select id="filter_academic_year" class="form-select form-select-sm">
                                        <option value="">Semua Tahun Ajaran</option>
                                        @foreach ($academicYears as $ay)
                                            <option value="{{ $ay->id }}" {{ $activeYear && $activeYear->id === $ay->id ? 'selected' : '' }}>
                                                {{ $ay->name }} ({{ $ay->semester }}) {{ $ay->is_active ? '★' : '' }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="me-2">
                                    <select id="filter_kelas" class="form-select form-select-sm">
                                        <option value="">Semua Kelas</option>
                                        @foreach ($kelasList as $k)
                                            <option value="{{ $k->id }}">
                                                {{ $k->tingkatan }} - {{ $k->kelas }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal"
                                    data-bs-target="#createComponentModal">
                                    <i class="bx bx-plus"></i> Tambah Komponen
                                </button>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col">
                                <div class="table-responsive">
                                    <table class="table table-hover table-bordered align-middle dataTable" style="width:100%" id="table">
                                        <thead>
                                            <tr>
                                                <th style="width: 5%">#</th>
                                                <th>Kelas</th>
                                                <th>Mata Pelajaran</th>
                                                <th>Pengajar</th>
                                                <th>Definisi Penilaian</th>
                                                <th>Bobot (%)</th>
                                                <th>Progres Nilai</th>
                                                <th style="width: 18%">Aksi</th>
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

    <!-- Create Component Modal -->
    <x-modal-form id='createComponentModal' title='Tambah Komponen Penilaian' fn="{{ route('assessment.component.store') }}" method="POST">
        @csrf
        <div class="mb-3">
            <label class="form-label font-weight-bold">Penugasan Mengajar <span class="text-danger">*</span></label>
            <select name="teaching_assignment_id" id="modal_teaching_assignment_id" class="form-select" required>
                <option value="">Pilih Penugasan Mengajar</option>
                @foreach ($teachingAssignments as $ta)
                    <option value="{{ $ta->id }}" data-year="{{ $ta->academic_year_id }}">
                        {{ $ta->kelas?->tingkatan }} {{ $ta->kelas?->kelas }} - {{ $ta->mapel?->name }} ({{ $ta->user?->name }}) [{{ $ta->academicYear?->name }} {{ $ta->academicYear?->semester }}]
                    </option>
                @endforeach
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label font-weight-bold">Definisi Penilaian <span class="text-danger">*</span></label>
            <select name="assessment_definition_id" id="modal_assessment_definition_id" class="form-select" required>
                <option value="">Pilih Definisi Penilaian</option>
                @foreach ($definitions as $def)
                    <option value="{{ $def->id }}" data-year="{{ $def->academic_year_id }}" data-default-weight="{{ $def->weight }}">
                        {{ $def->name }} ({{ $def->type }}) - Bobot Default: {{ $def->weight }}% [{{ $def->academicYear?->name }}]
                    </option>
                @endforeach
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label font-weight-bold">Bobot Komponen (%)</label>
            <input type="number" name="weight" id="modal_component_weight" class="form-control" min="0" max="100" placeholder="Kosongkan untuk mengikuti bobot default definisi">
            <small class="text-muted">Biarkan kosong jika ingin menggunakan bobot default dari definisi penilaian.</small>
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
                    url: "{{ route('assessment.component.index') }}",
                    data: function(d) {
                        d.academic_year_id = $('#filter_academic_year').val();
                        d.kelas_id = $('#filter_kelas').val();
                    }
                },
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'kelas_name', name: 'teachingAssignment.kelas.kelas' },
                    { data: 'mapel_name', name: 'teachingAssignment.mapel.name' },
                    { data: 'teacher_name', name: 'teachingAssignment.user.name' },
                    { data: 'definition_name', name: 'assessmentDefinition.name' },
                    { data: 'weight_formatted', name: 'weight' },
                    { data: 'scores_count_badge', name: 'scores_count', orderable: false, searchable: false },
                    { data: 'action', name: 'action', orderable: false, searchable: false },
                ]
            });

            $('#filter_academic_year, #filter_kelas').on('change', function() {
                table.ajax.reload();
            });

            // Filter definition options by year of selected teaching assignment
            $('#modal_teaching_assignment_id').on('change', function() {
                var selectedYear = $(this).find(':selected').data('year');
                $('#modal_assessment_definition_id option').each(function() {
                    var defYear = $(this).data('year');
                    if (!selectedYear || !defYear || defYear == selectedYear) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            });

            $('#modal_assessment_definition_id').on('change', function() {
                var defaultWeight = $(this).find(':selected').data('default-weight');
                if (defaultWeight !== undefined && $('#modal_component_weight').val() === '') {
                    $('#modal_component_weight').attr('placeholder', 'Bobot default: ' + defaultWeight + '%');
                }
            });
        });
    </script>
@endpush
