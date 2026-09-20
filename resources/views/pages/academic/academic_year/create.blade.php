@extends('layouts.app')

@section('title', 'Tambah Tahun Ajaran | DIGITREN')

@section('content')
    <div class="page-wrapper">
        <div class="page-content-wrapper">
            <div class="page-content">
                <x-breadcrumb url="{{ route('academic-year.index') }}" path='Tambah Tahun Ajaran'></x-breadcrumb>
                <div class="card">
                    <div class="card-body">
                        <form action="{{ route('academic-year.store') }}" method="POST">
                            @csrf
                            <div class="mb-3">
                                <x-input type='text' name='name' id="name" label='Tahun Ajaran'
                                    placeholder='contoh: 2026/2027' attribute="required"></x-input>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Semester</label>
                                <select name="semester" class="form-select" required>
                                    <option value="Ganjil" selected>Ganjil</option>
                                    <option value="Genap">Genap</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <x-input type='date' name='start_date' id="start_date" label='Tanggal Mulai'
                                    attribute="required"></x-input>
                            </div>
                            <div class="mb-3">
                                <x-input type='date' name='end_date' id="end_date" label='Tanggal Selesai'
                                    attribute="required"></x-input>
                            </div>
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1">
                                <label class="form-check-label" for="is_active">Tahun Ajaran Aktif</label>
                            </div>
                            <button type="submit" class="btn btn-primary">Simpan</button>
                            <a href="{{ route('academic-year.index') }}" class="btn btn-secondary">Kembali</a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
