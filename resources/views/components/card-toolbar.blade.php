@props(['title' => null])

<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <div>
        @if($title)
            <h5 class="mb-0 text-dark font-weight-bold">{{ $title }}</h5>
        @endif
    </div>
    <div class="d-flex flex-wrap align-items-center gap-2 ms-auto">
        {{ $slot }}
    </div>
</div>
<hr class="mt-0 mb-3" />
