@props([
    'title' => 'Konfirmasi Hapus Data',
    'subtitle' => 'Konfirmasi tindakan penghapusan data',
    'id',
    'fn',
    'method' => 'POST',
    'entity' => null,
    'message' => null,
    'btnText' => 'Ya, Hapus Data',
    'btnClass' => 'btn-danger',
    'btnIcon' => 'bx-trash',
    'iconColor' => 'danger',
    'icon' => 'bx-error-circle',
])

<!-- Modern Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal-{{ $id }}" tabindex="-1" aria-labelledby="deleteModalLabel-{{ $id }}"
    aria-hidden="true" style="display: none;">
    <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down">
        <div class="modal-content radius-15 border-0 shadow">
            <div class="modal-header border-bottom-0 pb-0 pt-4 px-4">
                <div class="d-flex align-items-center gap-3 w-100 overflow-hidden">
                    <div class="widgets-icons rounded-circle bg-light-{{ $iconColor }} text-{{ $iconColor }} flex-shrink-0">
                        <i class="bx {{ $icon }} font-24"></i>
                    </div>
                    <div class="overflow-hidden flex-grow-1 pe-2">
                        <h5 class="modal-title font-weight-bold mb-1 text-wrap text-break" id="deleteModalLabel-{{ $id }}">
                            {{ $title }}
                        </h5>
                        <small class="text-muted d-block text-wrap text-break">{{ $subtitle }}</small>
                    </div>
                </div>
                <button type="button" class="btn-close align-self-start flex-shrink-0 ms-2" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ $fn }}" method="{{ $method }}">
                <div class="modal-body px-4 py-3">
                    @if ($entity)
                        <div class="alert alert-{{ $iconColor }} border-0 bg-light-{{ $iconColor }} py-2 mb-3">
                            <div class="d-flex align-items-center">
                                <div class="font-18 text-{{ $iconColor }} flex-shrink-0"><i class="bx {{ $btnIcon }}"></i></div>
                                <div class="ms-2 overflow-hidden w-100">
                                    <div class="text-{{ $iconColor }} font-weight-bold font-13 text-wrap text-break">{{ $entity }}</div>
                                </div>
                            </div>
                        </div>
                    @endif
                    <p class="text-secondary font-14 mb-0 text-wrap text-break">
                        {{ $message ?? 'Apakah Anda yakin ingin menghapus data ini secara permanen? Data yang telah dihapus tidak dapat dipulihkan kembali.' }}
                    </p>
                    {{ $slot }}
                </div>
                <div class="modal-footer border-top-0 pt-0 pb-4 px-4 gap-2 flex-wrap flex-sm-nowrap">
                    <button type="button" class="btn btn-outline-secondary px-4 w-100 w-sm-auto mb-2 mb-sm-0" data-bs-dismiss="modal">
                        <i class="bx bx-x me-1"></i> Batal
                    </button>
                    <button type="submit" class="btn {{ $btnClass }} px-4 font-weight-bold w-100 w-sm-auto">
                        <i class="bx {{ $btnIcon }} me-1"></i> {{ $btnText }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
