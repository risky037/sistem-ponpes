@extends('layouts.app')

@section('title', 'Edit Tahun Ajaran | DIGITREN')

@section('content')
    <div class="page-wrapper">
        <div class="page-content-wrapper">
            <div class="page-content">
                <x-breadcrumb url="{{ route('academic-year.index') }}" path='Edit Tahun Ajaran'></x-breadcrumb>
                <div class="card">
                    <div class="card-body">
                        <form action="{{ route('academic-year.update', $academicYear->id) }}" method="POST">
                            @csrf
                            @method('PATCH')
                            <div class="mb-3">
                                <x-input type='text' name='name' id="name" label='Tahun Ajaran'
                                    placeholder='contoh: 2026/2027' value="{{ $academicYear->name }}" attribute="required"></x-input>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Semester</label>
                                <select name="semester" class="form-select" required>
                                    <option value="Ganjil" {{ $academicYear->semester === 'Ganjil' ? 'selected' : '' }}>Ganjil</option>
                                    <option value="Genap" {{ $academicYear->semester === 'Genap' ? 'selected' : '' }}>Genap</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <x-input type='date' name='start_date' id="start_date" label='Tanggal Mulai'
                                    value="{{ $academicYear->start_date?->format('Y-m-d') }}" attribute="required"></x-input>
                            </div>
                            <div class="mb-3">
                                <x-input type='date' name='end_date' id="end_date" label='Tanggal Selesai'
                                    value="{{ $academicYear->end_date?->format('Y-m-d') }}" attribute="required"></x-input>
                            </div>
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1"
                                    {{ $academicYear->is_active ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_active">Tahun Ajaran Aktif</label>
                            </div>
                            <button type="submit" class="btn btn-primary">Update</button>
                            <a href="{{ route('academic-year.index') }}" class="btn btn-secondary">Kembali</a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
