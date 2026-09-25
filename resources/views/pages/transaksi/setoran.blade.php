<div class="row mt-4">
    <div class="col-12 col-lg-8">
        <div class="card border-0 border-top border-4 border-success shadow-sm radius-10 mb-4">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="d-flex align-items-center">
                        <div class="widgets-icons-2 rounded-circle bg-light-success text-success me-3">
                            <i class="bx bx-down-arrow-circle"></i>
                        </div>
                        <div>
                            <h6 class="mb-0 text-success font-weight-bold">Formulir Setoran Tabungan</h6>
                            <small class="text-muted">Setoran masuk ke rekening tabungan santri</small>
                        </div>
                    </div>
                    <span class="badge bg-light-success text-success border border-success px-3 py-2 font-12">
                        <i class="bx bx-info-circle me-1"></i> Minimal Rp 50.000
                    </span>
                </div>

                <form action="{{ route('transaksi.store') }}" method="POST" id="form_setoran">
                    @csrf
                    <input type="hidden" name="jenis_transaksi" id="jenis_transaksi" value="Setoran">

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small text-muted font-weight-bold">Nomor Induk Santri (NIS)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bx bx-id-card"></i></span>
                                <input type="text" class="form-control bg-light font-weight-bold" id="santri_noinduk" name="santri_noinduk"
                                    placeholder="Pilih santri terlebih dahulu" readonly required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small text-muted font-weight-bold">Nama Lengkap Santri</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bx bx-user"></i></span>
                                <input type="text" class="form-control bg-light font-weight-bold" id="santri_nama" name="santri_nama"
                                    placeholder="Nama santri" readonly required>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="debit" class="form-label small text-muted font-weight-bold">Nominal Setoran (Rp)</label>
                        <div class="input-group input-group-lg">
                            <span class="input-group-text bg-success text-white font-weight-bold">Rp</span>
                            <input type="text" class="form-control font-weight-bold text-success font-20" placeholder="0"
                                name="debit" id="debit" required autocomplete="off">
                        </div>
                        <small class="text-muted font-11 mt-1 d-block">
                            Gunakan tombol nominal cepat di bawah untuk kemudahan pengisian kasir:
                        </small>
                    </div>

                    {{-- Quick Amount Chips --}}
                    <div class="d-flex flex-wrap gap-2 mb-4">
                        <button type="button" class="btn btn-sm btn-outline-success chip-nominal" data-amount="50000">+ Rp 50.000</button>
                        <button type="button" class="btn btn-sm btn-outline-success chip-nominal" data-amount="100000">+ Rp 100.000</button>
                        <button type="button" class="btn btn-sm btn-outline-success chip-nominal" data-amount="200000">+ Rp 200.000</button>
                        <button type="button" class="btn btn-sm btn-outline-success chip-nominal" data-amount="500000">+ Rp 500.000</button>
                        <button type="button" class="btn btn-sm btn-outline-success chip-nominal" data-amount="1000000">+ Rp 1.000.000</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary chip-clear">Reset</button>
                    </div>

                    <div class="d-flex justify-content-end align-items-center gap-2">
                        <button type="submit" id="btn_submit_setoran" class="btn btn-success px-4 py-2 font-weight-bold d-flex align-items-center gap-2">
                            <i class="bx bx-check-double font-20"></i>
                            <span>Simpan Setoran</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Santri Identity Verification Preview --}}
    <div class="col-12 col-lg-4">
        <div class="card border-0 shadow-sm radius-10 text-center mb-4">
            <div class="card-body p-4">
                <h6 class="text-muted font-13 text-uppercase font-weight-bold mb-3">Verifikasi Santri</h6>
                <div class="mb-3 position-relative d-inline-block">
                    <img src="{{ url('assets/images/avatars/avatar-1.png') }}" alt="Foto Santri"
                        class="img-fluid rounded-circle border shadow-sm p-1" id="santri_profile"
                        style="width: 120px; height: 120px; object-fit: cover;">
                </div>
                <h5 class="font-weight-bold mb-1" id="preview_name">-</h5>
                <p class="text-muted small mb-3">NIS: <span id="preview_nis" class="font-weight-bold text-primary">-</span></p>

                <div class="row g-2 text-start small mb-3">
                    <div class="col-6">
                        <div class="p-2 border rounded bg-light">
                            <span class="text-muted d-block font-11">Kelas</span>
                            <strong id="preview_kelas">-</strong>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-2 border rounded bg-light">
                            <span class="text-muted d-block font-11">Kamar</span>
                            <strong id="preview_kamar">-</strong>
                        </div>
                    </div>
                </div>

                <div class="p-3 rounded radius-10 bg-light-success text-success border border-success">
                    <span class="small d-block text-muted">Saldo Tabungan Saat Ini</span>
                    <h4 class="font-weight-bold mb-0 text-success">Rp <span id="saldo">0</span></h4>
                </div>
            </div>
        </div>
    </div>
</div>

@push('js')
    <script>
        $(document).ready(function() {
            function formatNumber(num) {
                return num.toString().replace(/[^0-9]/g, '');
            }

            $('.chip-nominal').on('click', function() {
                var amount = $(this).data('amount');
                var current = parseInt($('#debit').val().replace(/[^0-9]/g, '')) || 0;
                $('#debit').val(current + parseInt(amount));
            });

            $('.chip-clear').on('click', function() {
                $('#debit').val('');
            });

            $('#debit').on('input', function() {
                this.value = this.value.replace(/[^0-9]/g, '');
            });

            function populateSantri(data) {
                $('#santri_noinduk').val(data.no_induk);
                $('#santri_nama').val(data.name);
                $('#preview_name').text(data.name);
                $('#preview_nis').text(data.no_induk);
                $('#preview_kelas').text(data.kelas || '-');
                $('#preview_kamar').text(data.kamar || '-');
                $('#saldo').text(data.saldo);

                if (data.foto && data.foto !== 'santri.png') {
                    $('#santri_profile').attr('src', '/storage/uploads/santri/' + data.foto);
                } else {
                    $('#santri_profile').attr('src', '/assets/images/avatars/avatar-1.png');
                }
                $('#debit').focus();
            }

            function clearSantri() {
                $('#santri_noinduk').val('');
                $('#santri_nama').val('');
                $('#preview_name').text('-');
                $('#preview_nis').text('-');
                $('#preview_kelas').text('-');
                $('#preview_kamar').text('-');
                $('#saldo').text('0');
                $('#santri_profile').attr('src', '/assets/images/avatars/avatar-1.png');
            }

            var searchTimeout = null;
            function performLookup(noInduk) {
                $('#spinner').show();
                $.ajax({
                    url: "{{ route('transaksi.index') }}",
                    method: "GET",
                    data: {
                        no_induk: noInduk,
                        jenis: "Setoran"
                    },
                    success: function(res) {
                        $('#spinner').hide();
                        if (res.data) {
                            populateSantri(res.data);
                        } else {
                            clearSantri();
                            if (typeof Lobibox !== 'undefined') {
                                Lobibox.notify('error', {
                                    pauseDelayOnHover: true,
                                    icon: 'bx bx-error',
                                    size: 'mini',
                                    msg: res.message
                                });
                            }
                        }
                    },
                    error: function() {
                        $('#spinner').hide();
                    }
                });
            }

            $('#name').on('change', function() {
                var val = $(this).val();
                if (val) {
                    $('#no_induk').val(val);
                    performLookup(val);
                }
            });

            $('#no_induk').on('input', function() {
                var clean = this.value.replace(/[^0-9]/g, '');
                this.value = clean;
                clearTimeout(searchTimeout);
                if (clean.length === 8) {
                    searchTimeout = setTimeout(function() {
                        performLookup(clean);
                    }, 250);
                } else if (clean.length === 0) {
                    clearSantri();
                }
            });

            $('#form_setoran').on('submit', function() {
                var btn = $('#btn_submit_setoran');
                btn.prop('disabled', true);
                btn.html('<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Menyimpan...');
            });
        });
    </script>
@endpush
