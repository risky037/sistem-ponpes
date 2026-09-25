@props(['title', 'id', 'fn', 'method' => 'POST', 'modalSize' => null, 'icon' => 'bx-edit', 'iconColor' => 'primary'])
<!-- Modal -->
<div class="modal fade" id="editModal-{{ $id }}" tabindex="-1" aria-labelledby="editModalLabel-{{ $id }}"
    aria-hidden="true" style="display: none;">
    <div class="modal-dialog modal-dialog-centered {{ $modalSize ?? '' }}">
        <div class="modal-content radius-15 border-0 shadow">
            <div class="modal-header border-bottom-0 pb-0 pt-4 px-4">
                <div class="d-flex align-items-center gap-3">
                    <div class="widgets-icons rounded-circle bg-light-primary text-primary">
                        <i class="bx {{ $icon }} font-24"></i>
                    </div>
                    <div>
                        <h5 class="modal-title font-weight-bold" id="editModalLabel-{{ $id }}">{{ $title }}</h5>
                    </div>
                </div>
                <button type="button" class="btn-close align-self-start" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div>
                <form action="{{ $fn }}" method="{{ $method }}" enctype="multipart/form-data">
                    <div class="modal-body px-4 py-3">
                        {{ $slot }}
                    </div>
                    <div class="modal-footer border-top-0 pt-0 pb-4 px-4">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary"><i class="bx bx-save me-1"></i> Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
