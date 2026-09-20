@props(['title' => 'Konfirmasi Hapus Data', 'id', 'fn', 'method' => 'POST', 'entity' => null, 'message' => null])

<!-- Modern Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal-{{ $id }}" tabindex="-1" aria-labelledby="deleteModalLabel-{{ $id }}"
    aria-hidden="true" style="display: none;">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content radius-15 border-0 shadow">
            <div class="modal-header border-bottom-0 pb-0 pt-4 px-4">
                <div class="d-flex align-items-center gap-3">
                    <div class="widgets-icons rounded-circle bg-light-danger text-danger">
                        <i class="bx bx-error-circle font-24"></i>
                    </div>
                    <div>
                        <h5 class="modal-title font-weight-bold text-dark" id="deleteModalLabel-{{ $id }}">
                            {{ $title }}
                        </h5>
                        <small class="text-muted">Konfirmasi tindakan penghapusan data</small>
                    </div>
                </div>
                <button type="button" class="btn-close align-self-start" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ $fn }}" method="{{ $method }}">
                <div class="modal-body px-4 py-3">
                    @if ($entity)
                        <div class="alert alert-danger border-0 bg-light-danger py-2 mb-3">
                            <div class="d-flex align-items-center">
                                <div class="font-18 text-danger"><i class="bx bx-trash"></i></div>
                                <div class="ms-2">
                                    <div class="text-danger font-weight-bold font-13">{{ $entity }}</div>
                                </div>
                            </div>
                        </div>
                    @endif
                    <p class="text-secondary font-14 mb-0">
                        {{ $message ?? 'Apakah Anda yakin ingin menghapus data ini secara permanen? Data yang telah dihapus tidak dapat dipulihkan kembali.' }}
                    </p>
                    {{ $slot }}
                </div>
                <div class="modal-footer border-top-0 pt-0 pb-4 px-4 gap-2">
                    <button type="button" class="btn btn-light border px-4" data-bs-dismiss="modal">
                        <i class="bx bx-x me-1"></i> Batal
                    </button>
                    <button type="submit" class="btn btn-danger px-4 font-weight-bold">
                        <i class="bx bx-trash me-1"></i> Ya, Hapus Data
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
