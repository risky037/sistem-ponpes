@props([
    'type' => 'submit',
    'class' => 'btn btn-primary',
    'text' => 'Simpan',
    'loadingText' => 'Memproses...',
    'icon' => null,
    'id' => null,
])

<button type="{{ $type }}" {{ $attributes->merge(['class' => $class]) }} @if($id) id="{{ $id }}" @endif>
    <span class="btn-spinner spinner-border spinner-border-sm me-1 d-none" role="status" aria-hidden="true"></span>
    @if ($icon)
        <i class="{{ $icon }} btn-icon me-1"></i>
    @endif
    <span class="btn-text">{{ $text }}</span>
</button>
