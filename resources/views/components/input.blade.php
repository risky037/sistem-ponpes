@props(['type', 'name', 'id', 'value', 'label', 'placeholder', 'attribute', 'min', 'max', 'accept', 'helper'])
<label for="{{ $id }}" class="form-label">{{ $label }}</label>
<input type="{{ $type }}" class="form-control @error($name) is-invalid @enderror" id="{{ $id }}"
    name="{{ $name }}" placeholder="{{ $placeholder ?? '' }}" value="{{ $value ?? '' }}" {{ $attribute ?? '' }}
    min="{{ $min ?? '' }}" max="{{ $max ?? '' }}"
    @if(isset($accept)) accept="{{ $accept }}" @endif
    {{ $attributes }}>

@if(isset($helper))
    <div class="form-text text-muted font-12">{{ $helper }}</div>
@endif

@error($name)
    <div id="{{ $id }}" class="invalid-feedback">
        {{ $message }}
    </div>
@enderror
