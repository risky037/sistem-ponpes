@extends('layouts.app')

@section('title', 'Profil | DIGITREN')

@section('content')
    <div>
        <!--page-wrapper-->
        <div class="page-wrapper">
            <!--page-content-wrapper-->
            <div class="page-content-wrapper">
                <div class="page-content">
                    <x-breadcrumb url="{{ route('dashboard') }}" attribute="required" path='Profil'></x-breadcrumb>
                    <div class="user-profile-page">
                        <div class="card radius-15">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-12 col-lg-7 border-right">
                                        <div class="d-md-flex align-items-center">
                                            <div class="mb-md-0 mb-3">
                                                @if ($user->santri?->foto && $user->santri?->foto !== 'santri.png' && $user->santri?->foto !== '')
                                                    <img src="{{ $user->santri?->fotoUrl() }}" loading="lazy"
                                                        class="rounded-circle shadow" width="110" height="110" style="object-fit: cover;"
                                                        alt="Foto {{ $user->name }}"
                                                        onerror="this.onerror=null;this.src='{{ url('assets/images/avatars/avatar-1.png') }}';" />
                                                @else
                                                    <img src="{{ url('assets/images/avatars/avatar-1.png') }}" loading="lazy"
                                                        class="rounded-circle shadow" width="110" height="110"
                                                        alt="Avatar {{ $user->name }}" />
                                                @endif
                                            </div>
                                            <div class="ms-md-4 flex-grow-1">
                                                <div class="d-flex align-items-center mb-1">
                                                    <h4 class="mb-0">{{ $user->name }}</h4>
                                                </div>
                                                <p class="text-primary mb-0"><i class='bx bx-buildings me-1'></i>
                                                    {{ $user->roles->first()?->name ?? 'Pengguna' }}
                                                    @if ($user->santri?->no_induk)
                                                        - {{ $user->santri?->no_induk }}
                                                    @endif
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-12 col-lg-5">
                                    </div>
                                </div>
                                <!--end row-->
                                <ul class="nav nav-pills mt-2">
                                    {{-- <li class="nav-item"> <a class="nav-link active" data-bs-toggle="tab"
                                            href="#Show-Profile"><span class="p-tab-name">Profil</span><i
                                                class='bx bx-message-edit font-24 d-sm-none'></i></a>
                                    </li>
                                    <li class="nav-item"> <a class="nav-link" data-bs-toggle="tab"
                                            href="#Edit-Profile"><span class="p-tab-name">Edit Profil</span><i
                                                class='bx bx-message-edit font-24 d-sm-none'></i></a>
                                    </li> --}}
                                </ul>
                                <div class="tab-content mt-3">
                                    @include('pages.profil.form')
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('js')
@endpush
