@extends('layouts.app')

@section('title', 'Kelola Presensi Sesi | DIGITREN')

@section('content')
    <div class="page-wrapper">
        <div class="page-content-wrapper">
            <div class="page-content">
                <x-breadcrumb url="{{ route('attendance.index') }}" path='Kelola Presensi'></x-breadcrumb>

                <!-- Session Information Card -->
                <div class="card radius-15 border shadow-sm mb-4">
                    <div class="card-body">
                        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 pb-2 border-bottom gap-2">
                            <div>
                                <h5 class="mb-0 fw-bold">Detail Sesi Pembelajaran</h5>
                                <p class="text-muted small mb-0">Informasi kelas, jadwal, dan pengajar</p>
                            </div>
                            <div>
                                @php
                                    $statusClass = match ($teachingSession->status) {
                                        'Completed' => 'bg-success',
                                        'Cancelled' => 'bg-danger',
                                        default => 'bg-primary',
                                    };
                                @endphp
                                <span class="badge {{ $statusClass }} px-3 py-2 font-13">{{ $teachingSession->status }}</span>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-3 col-sm-6">
                                <label class="text-muted small">Mata Pelajaran</label>
                                <p class="fw-bold mb-0">{{ $teachingSession->teachingAssignment?->mapel?->name }} ({{ $teachingSession->teachingAssignment?->mapel?->code }})</p>
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <label class="text-muted small">Kelas</label>
                                <p class="fw-bold mb-0">{{ $teachingSession->teachingAssignment?->kelas?->tingkatan }} - {{ $teachingSession->teachingAssignment?->kelas?->kelas }}</p>
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <label class="text-muted small">Pengajar / Ustadz</label>
                                <p class="fw-bold mb-0">{{ $teachingSession->teachingAssignment?->user?->name }}</p>
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <label class="text-muted small">Tanggal Sesi</label>
                                <p class="fw-bold mb-0">{{ $teachingSession->session_date?->format('d/m/Y') }}</p>
                            </div>
                            @if ($teachingSession->classSchedule)
                                <div class="col-md-3 col-sm-6">
                                    <label class="text-muted small">Waktu Jadwal</label>
                                    <p class="fw-bold mb-0">{{ $teachingSession->classSchedule->day_of_week }}, {{ substr($teachingSession->classSchedule->start_time, 0, 5) }} - {{ substr($teachingSession->classSchedule->end_time, 0, 5) }}</p>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <label class="text-muted small">Ruangan</label>
                                    <p class="fw-bold mb-0">{{ $teachingSession->classSchedule->room ?? '-' }}</p>
                                </div>
                            @endif
                            @if ($teachingSession->notes)
                                <div class="col-12">
                                    <label class="text-muted small">Catatan Sesi</label>
                                    <p class="mb-0 text-secondary font-italic">{{ $teachingSession->notes }}</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Attendance Sheet Form -->
                <div class="card radius-15 border shadow-sm">
                    <div class="card-body">
                        <form action="{{ route('attendance.store', $teachingSession->id) }}" method="POST">
                            @csrf
                            <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 pb-2 border-bottom gap-2">
                                <div>
                                    <h5 class="mb-0 fw-bold">Lembar Presensi Santri</h5>
                                    <p class="text-muted small mb-0">Total santri aktif di kelas: {{ $enrollments->count() }} orang</p>
                                </div>
                                @if ($teachingSession->status !== 'Cancelled')
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-outline-success btn-sm" id="btnSetAllHadir">
                                            <i class="bx bx-check-double"></i> Tandai Semua Hadir
                                        </button>
                                        <button type="submit" class="btn btn-primary btn-sm">
                                            <i class="bx bx-save"></i> Simpan Presensi
                                        </button>
                                    </div>
                                @else
                                    <div>
                                        <span class="badge bg-danger">Sesi Dibatalkan (Presensi Dinonaktifkan)</span>
                                    </div>
                                @endif
                            </div>

                            @if ($errors->any())
                                <div class="alert alert-danger">
                                    <ul class="mb-0">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <div class="table-responsive">
                                <table class="table table-hover table-bordered align-middle" style="width:100%">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 5%">#</th>
                                            <th style="width: 15%">No. Induk (NIS)</th>
                                            <th style="width: 25%">Nama Santri</th>
                                            <th style="width: 30%">Status Kehadiran</th>
                                            <th style="width: 25%">Catatan</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($enrollments as $index => $enrollment)
                                            @php
                                                $record = $existingAttendance->get($enrollment->id);
                                                $currentStatus = $record?->status ?? 'Hadir';
                                                $currentNotes = $record?->notes ?? '';
                                            @endphp
                                            <tr>
                                                <td>{{ $index + 1 }}</td>
                                                <td>{{ $enrollment->santri?->no_induk ?? '-' }}</td>
                                                <td>
                                                    <strong>{{ $enrollment->santri?->user?->name ?? 'Santri #'.$enrollment->santri_id }}</strong>
                                                    <input type="hidden" name="attendance[{{ $index }}][academic_enrollment_id]" value="{{ $enrollment->id }}">
                                                </td>
                                                <td>
                                                    <div class="d-flex gap-3 flex-wrap align-items-center">
                                                        <div class="form-check form-check-inline">
                                                            <input class="form-check-input radio-hadir" type="radio" 
                                                                name="attendance[{{ $index }}][status]" 
                                                                id="status_h_{{ $enrollment->id }}" 
                                                                value="Hadir" 
                                                                {{ $currentStatus === 'Hadir' ? 'checked' : '' }}
                                                                {{ $teachingSession->status === 'Cancelled' ? 'disabled' : '' }}>
                                                            <label class="form-check-label text-success fw-bold" for="status_h_{{ $enrollment->id }}">Hadir</label>
                                                        </div>
                                                        <div class="form-check form-check-inline">
                                                            <input class="form-check-input radio-izin" type="radio" 
                                                                name="attendance[{{ $index }}][status]" 
                                                                id="status_i_{{ $enrollment->id }}" 
                                                                value="Izin" 
                                                                {{ $currentStatus === 'Izin' ? 'checked' : '' }}
                                                                {{ $teachingSession->status === 'Cancelled' ? 'disabled' : '' }}>
                                                            <label class="form-check-label text-info fw-bold" for="status_i_{{ $enrollment->id }}">Izin</label>
                                                        </div>
                                                        <div class="form-check form-check-inline">
                                                            <input class="form-check-input radio-sakit" type="radio" 
                                                                name="attendance[{{ $index }}][status]" 
                                                                id="status_s_{{ $enrollment->id }}" 
                                                                value="Sakit" 
                                                                {{ $currentStatus === 'Sakit' ? 'checked' : '' }}
                                                                {{ $teachingSession->status === 'Cancelled' ? 'disabled' : '' }}>
                                                            <label class="form-check-label text-warning fw-bold" for="status_s_{{ $enrollment->id }}">Sakit</label>
                                                        </div>
                                                        <div class="form-check form-check-inline">
                                                            <input class="form-check-input radio-alpha" type="radio" 
                                                                name="attendance[{{ $index }}][status]" 
                                                                id="status_a_{{ $enrollment->id }}" 
                                                                value="Alpha" 
                                                                {{ $currentStatus === 'Alpha' ? 'checked' : '' }}
                                                                {{ $teachingSession->status === 'Cancelled' ? 'disabled' : '' }}>
                                                            <label class="form-check-label text-danger fw-bold" for="status_a_{{ $enrollment->id }}">Alpha</label>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <input type="text" name="attendance[{{ $index }}][notes]" class="form-control form-control-sm" 
                                                        value="{{ $currentNotes }}" placeholder="Catatan khusus (opsional)"
                                                        {{ $teachingSession->status === 'Cancelled' ? 'disabled' : '' }}>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="text-center py-4 text-muted">
                                                    Tidak ada santri yang terdaftar aktif pada kelas dan tahun ajaran ini.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            @if ($enrollments->isNotEmpty() && $teachingSession->status !== 'Cancelled')
                                <div class="d-flex justify-content-end mt-3">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bx bx-save"></i> Simpan Presensi Santri
                                    </button>
                                </div>
                            @endif
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('js')
    <script>
        $(document).ready(function() {
            $('#btnSetAllHadir').on('click', function() {
                $('.radio-hadir').prop('checked', true);
            });
        });
    </script>
@endpush
