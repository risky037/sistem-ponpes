@extends('layouts.app')

@section('title', 'Kelola Nilai Santri | DIGITREN')

@section('content')
    <div class="page-wrapper">
        <div class="page-content-wrapper">
            <div class="page-content">
                <x-breadcrumb url="{{ route('assessment.component.index') }}" path='Kelola Nilai'></x-breadcrumb>

                <!-- Assessment Component Detail Card -->
                <div class="card radius-15 border shadow-sm mb-4">
                    <div class="card-body">
                        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 pb-2 border-bottom gap-2">
                            <div>
                                <h5 class="mb-0 fw-bold">Detail Komponen Penilaian</h5>
                                <p class="text-muted small mb-0">Informasi mata pelajaran, kelas, dan bobot penilaian</p>
                            </div>
                            <div>
                                <span class="badge bg-primary px-3 py-2 font-13">Bobot: {{ $assessmentComponent->weight }}%</span>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-3 col-sm-6">
                                <label class="text-muted small">Definisi Penilaian</label>
                                <p class="fw-bold mb-0">{{ $assessmentComponent->assessmentDefinition?->name }} ({{ $assessmentComponent->assessmentDefinition?->type }})</p>
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <label class="text-muted small">Mata Pelajaran</label>
                                <p class="fw-bold mb-0">{{ $assessmentComponent->teachingAssignment?->mapel?->name }} ({{ $assessmentComponent->teachingAssignment?->mapel?->code }})</p>
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <label class="text-muted small">Kelas</label>
                                <p class="fw-bold mb-0">{{ $assessmentComponent->teachingAssignment?->kelas?->tingkatan }} - {{ $assessmentComponent->teachingAssignment?->kelas?->kelas }}</p>
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <label class="text-muted small">Pengajar / Ustadz</label>
                                <p class="fw-bold mb-0">{{ $assessmentComponent->teachingAssignment?->user?->name }}</p>
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <label class="text-muted small">Tahun Ajaran</label>
                                <p class="fw-bold mb-0">{{ $assessmentComponent->teachingAssignment?->academicYear?->name }} ({{ $assessmentComponent->teachingAssignment?->academicYear?->semester }})</p>
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <label class="text-muted small">Bobot Default Definisi</label>
                                <p class="fw-bold mb-0">{{ $assessmentComponent->assessmentDefinition?->weight }}%</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Score Sheet Card -->
                <div class="card radius-15 border shadow-sm">
                    <div class="card-body">
                        <form action="{{ route('assessment.score.store', $assessmentComponent->id) }}" method="POST">
                            @csrf
                            <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 pb-2 border-bottom gap-2">
                                <div>
                                    <h5 class="mb-0 fw-bold">Lembar Nilai Santri</h5>
                                    <p class="text-muted small mb-0">Total santri aktif di kelas: {{ $enrollments->count() }} orang</p>
                                </div>
                                <div class="d-flex gap-2">
                                    <a href="{{ route('assessment.component.index') }}" class="btn btn-outline-secondary btn-sm">
                                        <i class="bx bx-arrow-back"></i> Kembali
                                    </a>
                                    <button type="submit" class="btn btn-primary btn-sm">
                                        <i class="bx bx-save"></i> Simpan Nilai
                                    </button>
                                </div>
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
                                <table class="table table-striped table-bordered align-middle" style="width:100%">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 5%">#</th>
                                            <th style="width: 15%">No. Induk (NIS)</th>
                                            <th style="width: 30%">Nama Santri</th>
                                            <th style="width: 15%">Nilai (0 - 100)</th>
                                            <th style="width: 20%">Catatan</th>
                                            <th style="width: 15%">Histori Pengisian</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($enrollments as $index => $enrollment)
                                            @php
                                                $record = $existingScores->get($enrollment->id);
                                                $currentScore = $record ? $record->score : '';
                                                $currentNotes = $record?->notes ?? '';
                                            @endphp
                                            <tr>
                                                <td>{{ $index + 1 }}</td>
                                                <td>{{ $enrollment->santri?->no_induk ?? '-' }}</td>
                                                <td>
                                                    <strong>{{ $enrollment->santri?->user?->name ?? 'Santri #'.$enrollment->santri_id }}</strong>
                                                    <input type="hidden" name="scores[{{ $index }}][academic_enrollment_id]" value="{{ $enrollment->id }}">
                                                </td>
                                                <td>
                                                    <input type="number" step="0.01" min="0" max="100" 
                                                        name="scores[{{ $index }}][score]" 
                                                        class="form-control form-control-sm text-end font-weight-bold" 
                                                        value="{{ old('scores.'.$index.'.score', $currentScore) }}"
                                                        placeholder="0.00">
                                                </td>
                                                <td>
                                                    <input type="text" name="scores[{{ $index }}][notes]" 
                                                        class="form-control form-control-sm" 
                                                        value="{{ old('scores.'.$index.'.notes', $currentNotes) }}" 
                                                        placeholder="Catatan nilai (opsional)">
                                                </td>
                                                <td>
                                                    @if ($record && $record->graded_at)
                                                        <small class="text-muted d-block">
                                                            <i class="bx bx-user"></i> {{ $record->grader?->name ?? 'Sistem' }}
                                                        </small>
                                                        <small class="text-muted d-block">
                                                            <i class="bx bx-time"></i> {{ $record->graded_at->format('d/m/Y H:i') }}
                                                        </small>
                                                    @else
                                                        <span class="badge bg-light text-muted">Belum ada nilai</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6" class="text-center py-4 text-muted">
                                                    Tidak ada santri yang terdaftar aktif pada kelas dan tahun ajaran ini.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            @if ($enrollments->isNotEmpty())
                                <div class="d-flex justify-content-end mt-3">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bx bx-save"></i> Simpan Nilai Santri
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
