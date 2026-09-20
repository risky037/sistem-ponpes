@extends('layouts.app')

@section('title', 'Edit Data Santri | DIGITREN')

@section('content')
    <div class="page-wrapper">
        <div class="page-content-wrapper">
            <div class="page-content">
                <x-breadcrumb url="{{ route('santri.index') }}" path='Edit Data Santri'></x-breadcrumb>
                <div class="card radius-15 border shadow-sm">
                    <div class="card-body">
                        <x-card-toolbar title="Form Edit Data Santri">
                            <a href="{{ route('santri.show', $item->id) }}" class="btn btn-outline-info btn-sm">
                                <i class="bx bx-user"></i> Lihat Profil
                            </a>
                            <a href="{{ route('santri.index') }}" class="btn btn-outline-secondary btn-sm">
                                <i class="bx bx-arrow-back"></i> Kembali
                            </a>
                        </x-card-toolbar>

                        <form action="{{ route('santri.update', $item->id) }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            @method('PATCH')
                            @include('pages.santri.include.form')
                            <div class="d-flex gap-2 mt-4">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bx bx-save"></i> Simpan Perubahan
                                </button>
                                <a href="{{ route('santri.index') }}" class="btn btn-secondary">
                                    Batal
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
