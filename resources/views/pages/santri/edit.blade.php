@extends('layouts.app')

@section('title', 'Santri | DIGITREN')

@section('content')
    <div>
        <!--page-wrapper-->
        <div class="page-wrapper">
            <!--page-content-wrapper-->
            <div class="page-content-wrapper">
                <div class="page-content">
                    <x-breadcrumb url="{{ route('dashboard') }}" attribute="required" path='Santri'></x-breadcrumb>
                    <div class="card">
                        <div class="card-body">
                            <div id="invoice">
                                <div class="toolbar hidden-print">
                                    <div class="text-end">
                                        <a href="{{ route('santri.index') }}" type="button" class="btn btn-primary">
                                            Kembali
                                        </a>
                                    </div>
                                    <hr />
                                </div>
                            </div>
                            <form action="{{ route('santri.update', $item->id) }}" method="POST"
                                enctype="multipart/form-data">
                                @csrf
                                @method('patch')
                                @include('pages.santri.include.form')
                                <button class="btn btn-primary">Update</button>
                                <a href="{{ route('santri.index') }}" type="button" class="btn btn-info">
                                    Kembali
                                </a>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
