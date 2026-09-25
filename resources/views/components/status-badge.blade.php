@props(['status'])

@php
    $class = match($status) {
        'Santri Aktif', 'Aktif', 1, true => 'bg-success',
        'Santri Alumni', 'Nonaktif', 0, false => 'bg-secondary',
        'Santri Pindah', 'Santri Drop Out' => 'bg-danger',
        'Ganjil' => 'bg-primary',
        'Genap' => 'bg-info text-white',
        default => 'bg-light-secondary text-secondary border',
    };
@endphp

<span class="badge {{ $class }} font-12">{{ is_bool($status) ? ($status ? 'Aktif' : 'Nonaktif') : $status }}</span>
