@extends('layouts.app')

@section('title', 'Definisi Penilaian | DIGITREN')

@section('content')
    <div class="page-wrapper">
        <div class="page-content-wrapper">
            <div class="page-content">
                <x-breadcrumb url="{{ route('dashboard') }}" path='Definisi Penilaian'></x-breadcrumb>
                <div class="card radius-15 border shadow-sm">
                    <div class="card-body">
                        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 pb-2 border-bottom gap-2">
                            <div>
                                <h5 class="mb-0 fw-bold">Definisi Penilaian Akademik</h5>
                                <p class="text-muted small mb-0">Master kategori dan bobot default penilaian per tahun ajaran</p>
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
                                    <select id="filter_type" class="form-select form-select-sm">
                                        <option value="">Semua Tipe</option>
                                        @foreach ($allowedTypes as $t)
                                            <option value="{{ $t }}">{{ $t }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal"
                                    data-bs-target="#createModal">
                                    <i class="bx bx-plus"></i> Tambah Definisi
                                </button>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col">
                                <div class="table-responsive">
                                    <table class="table table-striped table-bordered align-middle dataTable" style="width:100%" id="table">
                                        <thead>
                                            <tr>
                                                <th style="width: 5%">#</th>
                                                <th>Nama Definisi</th>
                                                <th>Tahun Ajaran</th>
                                                <th>Tipe</th>
                                                <th>Bobot Default</th>
                                                <th>Status</th>
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

    <!-- Create Modal -->
    <x-modal-form id='createModal' title='Tambah Definisi Penilaian' fn="{{ route('assessment.definition.store') }}" method="POST">
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
            <label class="form-label font-weight-bold">Nama Definisi <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control" placeholder="Contoh: Ujian Tengah Semester Ganjil" required maxlength="100">
        </div>
        <div class="mb-3">
            <label class="form-label font-weight-bold">Tipe Penilaian <span class="text-danger">*</span></label>
            <select name="type" class="form-select" required>
                <option value="">Pilih Tipe</option>
                @foreach ($allowedTypes as $t)
                    <option value="{{ $t }}">{{ $t }}</option>
                @endforeach
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label font-weight-bold">Bobot Default (%) <span class="text-danger">*</span></label>
            <input type="number" name="weight" class="form-control" min="0" max="100" value="20" required>
            <small class="text-muted">Persentase bobot penilaian default untuk komponen mata pelajaran.</small>
        </div>
        <div class="mb-3">
            <label class="form-label font-weight-bold">Status</label>
            <select name="is_active" class="form-select">
                <option value="1" selected>Aktif</option>
                <option value="0">Nonaktif</option>
            </select>
        </div>
    </x-modal-form>

    <!-- Edit Modal -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="editForm" method="POST">
                    @csrf
                    @method('PATCH')
                    <div class="modal-header">
                        <h5 class="modal-title" id="editModalLabel">Edit Definisi Penilaian</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label font-weight-bold">Nama Definisi <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="edit_name" class="form-control" required maxlength="100">
                        </div>
                        <div class="mb-3">
                            <label class="form-label font-weight-bold">Tipe Penilaian <span class="text-danger">*</span></label>
                            <select name="type" id="edit_type" class="form-select" required>
                                @foreach ($allowedTypes as $t)
                                    <option value="{{ $t }}">{{ $t }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label font-weight-bold">Bobot Default (%) <span class="text-danger">*</span></label>
                            <input type="number" name="weight" id="edit_weight" class="form-control" min="0" max="100" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label font-weight-bold">Status</label>
                            <select name="is_active" id="edit_is_active" class="form-select">
                                <option value="1">Aktif</option>
                                <option value="0">Nonaktif</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('js')
    <script>
        $(document).ready(function() {
            var table = $('#table').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                ajax: {
                    url: "{{ route('assessment.definition.index') }}",
                    data: function(d) {
                        d.academic_year_id = $('#filter_academic_year').val();
                        d.type = $('#filter_type').val();
                    }
                },
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'name', name: 'name' },
                    { data: 'academic_year_name', name: 'academicYear.name' },
                    { data: 'type', name: 'type' },
                    { data: 'weight_formatted', name: 'weight' },
                    { data: 'status_badge', name: 'is_active' },
                    { data: 'action', name: 'action', orderable: false, searchable: false },
                ]
            });

            $('#filter_academic_year, #filter_type').on('change', function() {
                table.ajax.reload();
            });

            $(document).on('click', '.btn-edit', function() {
                var id = $(this).data('id');
                var name = $(this).data('name');
                var type = $(this).data('type');
                var weight = $(this).data('weight');
                var isActive = $(this).data('is-active');

                var url = "{{ route('assessment.definition.update', ':id') }}".replace(':id', id);
                $('#editForm').attr('action', url);
                $('#edit_name').val(name);
                $('#edit_type').val(type);
                $('#edit_weight').val(weight);
                $('#edit_is_active').val(isActive);
            });
        });
    </script>
@endpush
