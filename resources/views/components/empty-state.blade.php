@props([
    'title' => 'Belum Ada Data',
    'message' => 'Tidak ada data yang tersedia untuk ditampilkan saat ini.',
    'icon' => 'bx bx-folder-open',
    'url' => null,
    'buttonText' => null,
])

<div class="text-center py-5 px-3">
    <div class="widgets-icons-2 rounded-circle bg-light-secondary text-secondary mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 64px; height: 64px;">
        <i class="{{ $icon }} font-30"></i>
    </div>
    <h6 class="font-weight-bold mb-1">{{ $title }}</h6>
    <p class="text-secondary small mb-3" style="max-width: 420px; margin: 0 auto;">
        {{ $message }}
    </p>
    @if ($url && $buttonText)
        <a href="{{ $url }}" class="btn btn-sm btn-primary">
            {{ $buttonText }}
        </a>
    @endif
</div>
