@extends('layouts.app')

@section('title', $document['title'] . ' | Pusat Dokumentasi DIGITREN')

@push('css')
<style>
    /* Reading Progress Bar */
    #doc-progress-bar {
        position: fixed;
        top: 0;
        left: 0;
        height: 3px;
        background: linear-gradient(90deg, #0d6efd, #20c997);
        width: 0%;
        z-index: 9999;
        transition: width 0.1s ease-out;
    }

    /* Documentation Typography & Spacing */
    .doc-markdown-content {
        line-height: 1.8;
        font-size: 15px;
        color: #2b3035;
    }
    .doc-markdown-content h1 {
        font-size: 1.85rem;
        font-weight: 700;
        margin-top: 1.5rem;
        margin-bottom: 1rem;
        padding-bottom: 0.5rem;
        border-bottom: 1px solid #e9ecef;
    }
    .doc-markdown-content h2 {
        font-size: 1.45rem;
        font-weight: 600;
        margin-top: 2rem;
        margin-bottom: 0.75rem;
        padding-bottom: 0.35rem;
        border-bottom: 1px solid #f1f3f5;
    }
    .doc-markdown-content h3 {
        font-size: 1.2rem;
        font-weight: 600;
        margin-top: 1.5rem;
        margin-bottom: 0.5rem;
    }
    .doc-markdown-content p {
        margin-bottom: 1rem;
    }
    .doc-markdown-content ul, .doc-markdown-content ol {
        margin-bottom: 1rem;
        padding-left: 1.5rem;
    }
    .doc-markdown-content li {
        margin-bottom: 0.35rem;
    }
    .doc-markdown-content blockquote {
        border-left: 4px solid #0d6efd;
        background-color: #f8f9fa;
        padding: 0.75rem 1.25rem;
        margin: 1.25rem 0;
        border-radius: 0 8px 8px 0;
        color: #495057;
    }

    /* Callout Alert Styles */
    .doc-callout {
        border-radius: 8px;
    }
    .doc-callout p:last-child {
        margin-bottom: 0;
    }

    /* Code Blocks */
    .doc-code-block pre {
        border-bottom-left-radius: 8px;
        border-bottom-right-radius: 8px;
    }
    .btn-copy-code:hover {
        color: #fff !important;
    }

    /* Sidebar Navigation */
    .doc-sidebar-nav .list-group-item {
        border: none;
        padding: 0.6rem 0.85rem;
        font-size: 13.5px;
        border-radius: 6px;
        margin-bottom: 2px;
        transition: all 0.2s ease;
    }
    .doc-sidebar-nav .list-group-item:hover {
        background-color: #f1f5f9;
        color: #0d6efd;
    }
    .doc-sidebar-nav .list-group-item.active {
        background-color: #e7f1ff;
        color: #0d6efd;
        font-weight: 600;
        border-left: 3px solid #0d6efd;
        border-radius: 0 6px 6px 0;
    }

    /* Right TOC Anchor Links */
    .doc-toc-link {
        display: block;
        padding: 0.25rem 0.5rem;
        font-size: 12.5px;
        color: #6c757d;
        text-decoration: none;
        border-left: 2px solid transparent;
        transition: all 0.15s ease;
    }
    .doc-toc-link:hover {
        color: #0d6efd;
        border-left-color: #0d6efd;
        background-color: #f8f9fa;
    }
    .doc-toc-level-3 {
        padding-left: 1.25rem;
    }

    /* Heading Anchor Hover */
    .doc-heading:hover .doc-heading-anchor {
        opacity: 1 !important;
    }
</style>
@endpush

@section('content')
<div id="doc-progress-bar"></div>

<div class="page-wrapper">
    <div class="page-content-wrapper">
        <div class="page-content">
            
            {{-- Breadcrumb --}}
            <div class="page-breadcrumb d-none d-md-flex align-items-center mb-3">
                <div class="breadcrumb-title pe-3">{{ $document['category_name'] }}</div>
                <div class="ps-3">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0 p-0">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class='bx bx-home-alt'></i></a></li>
                            <li class="breadcrumb-item"><a href="{{ route('documentation.index') }}">Panduan Sistem</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('documentation.index') }}#cat-{{ $document['category'] }}">{{ $document['category_name'] }}</a></li>
                            <li class="breadcrumb-item active" aria-current="page">{{ $document['title'] }}</li>
                        </ol>
                    </nav>
                </div>
                <div class="ms-auto">
                    <a href="{{ route('documentation.index') }}" class="btn btn-outline-secondary btn-sm radius-10">
                        <i class="bx bx-arrow-back me-1"></i> Beranda Panduan
                    </a>
                </div>
            </div>

            <div class="row">
                {{-- LEFT SIDEBAR: Navigation Tree --}}
                <div class="col-12 col-lg-3 col-xl-3 mb-4">
                    <div class="card radius-15 border shadow-sm sticky-top" style="top: 80px; z-index: 10;">
                        <div class="card-header bg-transparent border-bottom py-3">
                            <div class="d-flex align-items-center justify-content-between">
                                <h6 class="mb-0 fw-bold font-14">
                                    <i class="bx bx-book-content me-1 text-primary"></i> Daftar Panduan
                                </h6>
                                <span class="badge bg-light-primary text-primary font-11">DIGITREN</span>
                            </div>
                            <div class="mt-2 position-relative">
                                <input type="text" id="sidebar_doc_filter" class="form-control form-control-sm ps-4 radius-8"
                                       placeholder="Saring dokumen...">
                                <i class="bx bx-search position-absolute top-50 start-0 translate-middle-y ms-2 text-muted font-14"></i>
                            </div>
                        </div>
                        <div class="card-body p-2 doc-sidebar-nav overflow-auto" style="max-height: calc(100vh - 220px);">
                            @foreach ($menu as $catKey => $category)
                                <div class="mb-3 doc-category-group" data-category="{{ $catKey }}">
                                    <div class="d-flex align-items-center justify-content-between px-2 py-1 text-uppercase text-muted font-11 fw-bold">
                                        <span><i class="{{ $category['icon'] }} me-1"></i> {{ $category['name'] }}</span>
                                        <span class="badge bg-light text-secondary rounded-pill font-10">{{ count($category['docs']) }}</span>
                                    </div>
                                    <div class="list-group list-group-flush mt-1">
                                        @foreach ($category['docs'] as $doc)
                                            <a href="{{ $doc['url'] }}"
                                               class="list-group-item list-group-item-action {{ $currentCategory === $catKey && $currentSlug === $doc['slug'] ? 'active' : '' }} doc-nav-item"
                                               data-title="{{ strtolower($doc['title']) }}">
                                                <i class="bx {{ $currentCategory === $catKey && $currentSlug === $doc['slug'] ? 'bx-file text-primary' : 'bx-file-blank text-muted' }} me-1"></i>
                                                {{ $doc['title'] }}
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- MAIN CONTENT: Article Reader --}}
                <div class="col-12 col-lg-9 col-xl-7 mb-4">
                    <div class="card radius-15 border shadow-sm">
                        <div class="card-body p-4 p-md-5">
                            {{-- Header Meta --}}
                            <div class="d-flex align-items-center gap-2 flex-wrap mb-2">
                                <span class="badge bg-light-primary text-primary px-3 py-1 radius-8 font-12 fw-bold">
                                    <i class="{{ $document['category_icon'] }} me-1"></i> {{ $document['category_name'] }}
                                </span>
                                <span class="text-muted font-12">
                                    <i class="bx bx-time-five me-1"></i> Diperbarui {{ $document['formatted_date'] }}
                                </span>
                                <span class="text-muted font-12">
                                    <i class="bx bx-book-reader me-1"></i> {{ $document['reading_time'] }} menit baca
                                </span>
                                <span class="text-muted font-12">
                                    <i class="bx bx-file-blank me-1"></i> {{ number_format($document['word_count']) }} kata
                                </span>
                            </div>

                            <hr class="my-3">

                            {{-- Rendered Markdown Content --}}
                            <article class="doc-markdown-content" id="doc_content_area">
                                {!! $document['html'] !!}
                            </article>

                            <hr class="my-4">

                            {{-- Previous / Next Navigation --}}
                            <div class="row g-3">
                                <div class="col-sm-6">
                                    @if ($navigation['prev'])
                                        <a href="{{ $navigation['prev']['url'] }}" class="card radius-10 border shadow-none text-decoration-none h-100 p-3 hover-shadow bg-light">
                                            <small class="text-muted d-block font-11 text-uppercase mb-1">
                                                <i class="bx bx-chevron-left"></i> Dokumen Sebelumnya
                                            </small>
                                            <span class="fw-bold font-13 text-dark d-block text-truncate">
                                                {{ $navigation['prev']['title'] }}
                                            </span>
                                        </a>
                                    @endif
                                </div>
                                <div class="col-sm-6 text-end">
                                    @if ($navigation['next'])
                                        <a href="{{ $navigation['next']['url'] }}" class="card radius-10 border shadow-none text-decoration-none h-100 p-3 hover-shadow bg-light">
                                            <small class="text-muted d-block font-11 text-uppercase mb-1">
                                                Dokumen Selanjutnya <i class="bx bx-chevron-right"></i>
                                            </small>
                                            <span class="fw-bold font-13 text-primary d-block text-truncate">
                                                {{ $navigation['next']['title'] }}
                                            </span>
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- RIGHT SIDEBAR: Table of Contents (TOC) --}}
                <div class="col-12 col-xl-2 d-none d-xl-block mb-4">
                    <div class="card radius-15 border shadow-sm sticky-top" style="top: 80px;">
                        <div class="card-header bg-transparent border-bottom py-2">
                            <span class="text-uppercase font-11 fw-bold text-muted">
                                <i class="bx bx-list-ul me-1"></i> Di Halaman Ini
                            </span>
                        </div>
                        <div class="card-body p-2 overflow-auto" style="max-height: calc(100vh - 250px);">
                            @if (!empty($document['toc']))
                                <nav class="doc-toc-nav">
                                    @foreach ($document['toc'] as $heading)
                                        <a href="#{{ $heading['id'] }}" class="doc-toc-link doc-toc-level-{{ $heading['level'] }}">
                                            {{ $heading['text'] }}
                                        </a>
                                    @endforeach
                                </nav>
                            @else
                                <p class="text-muted font-12 p-2 mb-0">Halaman ini adalah ringkasan panduan langsung.</p>
                            @endif

                            <hr class="my-3">

                            <div class="p-2 text-center">
                                <button type="button" class="btn btn-sm btn-outline-secondary w-100 radius-8 font-12" onclick="window.scrollTo({top: 0, behavior: 'smooth'});">
                                    <i class="bx bx-up-arrow-alt me-1"></i> Kembali ke Atas
                                </button>
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
{{-- Mermaid CDN with offline resilience fallback --}}
<script src="https://cdn.jsdelivr.net/npm/mermaid@10/dist/mermaid.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // 1. Reading Progress Bar
        window.addEventListener('scroll', function () {
            var winScroll = document.body.scrollTop || document.documentElement.scrollTop;
            var height = document.documentElement.scrollHeight - document.documentElement.clientHeight;
            var scrolled = height > 0 ? (winScroll / height) * 100 : 0;
            var bar = document.getElementById('doc-progress-bar');
            if (bar) {
                bar.style.width = scrolled + '%';
            }
        });

        // 2. Initialize Mermaid if available
        if (typeof mermaid !== 'undefined') {
            mermaid.initialize({
                startOnLoad: true,
                theme: 'neutral',
                securityLevel: 'loose'
            });
        }

        // 3. Copy Code Block Snippet
        document.querySelectorAll('.btn-copy-code').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var codeEl = this.closest('.doc-code-block').querySelector('code');
                if (codeEl) {
                    var text = codeEl.innerText;
                    navigator.clipboard.writeText(text).then(function () {
                        var originalHtml = btn.innerHTML;
                        btn.innerHTML = '<i class="bx bx-check me-1 text-success"></i> Tersalin!';
                        setTimeout(function () {
                            btn.innerHTML = originalHtml;
                        }, 2000);
                    });
                }
            });
        });

        // 4. Sidebar Instant Document Filter
        var filterInput = document.getElementById('sidebar_doc_filter');
        if (filterInput) {
            filterInput.addEventListener('input', function () {
                var query = this.value.toLowerCase().trim();
                document.querySelectorAll('.doc-nav-item').forEach(function (item) {
                    var title = item.getAttribute('data-title') || '';
                    if (query === '' || title.includes(query)) {
                        item.classList.remove('d-none');
                    } else {
                        item.classList.add('d-none');
                    }
                });

                document.querySelectorAll('.doc-category-group').forEach(function (group) {
                    var visibleItems = group.querySelectorAll('.doc-nav-item:not(.d-none)');
                    if (visibleItems.length === 0 && query !== '') {
                        group.classList.add('d-none');
                    } else {
                        group.classList.remove('d-none');
                    }
                });
            });
        }
    });
</script>
@endpush
