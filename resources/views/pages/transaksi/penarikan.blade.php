<div class="row mt-4">
    <div class="col-12 col-lg-8">
        <div class="card border-0 border-top border-4 border-danger shadow-sm radius-10 mb-4">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="d-flex align-items-center">
                        <div class="widgets-icons-2 rounded-circle bg-light-danger text-danger me-3">
                            <i class="bx bx-up-arrow-circle"></i>
                        </div>
                        <div>
                            <h6 class="mb-0 text-danger font-weight-bold">Formulir Penarikan Tabungan</h6>
                            <small class="text-muted">Penarikan saldo kas tabungan santri</small>
                        </div>
                    </div>
                    <div class="text-end">
                        <span class="badge bg-light-danger text-danger border border-danger px-2 py-1 font-11 d-block mb-1">
                            <i class="bx bx-error-circle me-1"></i> Min. Rp 10.000
                        </span>
                        <span class="badge bg-light-warning text-warning border border-warning px-2 py-1 font-11">
                            <i class="bx bx-time me-1"></i> Maks. 1x / Hari
                        </span>
                    </div>
                </div>

                <div id="alert_withdrawal_status" style="display: none;" class="alert alert-warning border-0 bg-light-warning py-2 mb-3">
                    <div class="d-flex align-items-center">
                        <i class="bx bx-error font-24 text-warning me-2"></i>
                        <span id="withdrawal_status_text" class="small"></span>
                    </div>
                </div>

                <form action="{{ route('transaksi.update') }}" method="POST" id="form_penarikan">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="jenis_transaksi" id="jenis_transaksi" value="Penarikan">

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
                        <label for="kredit" class="form-label small text-muted font-weight-bold">Nominal Penarikan (Rp)</label>
                        <div class="input-group input-group-lg">
                            <span class="input-group-text bg-danger text-white font-weight-bold">Rp</span>
                            <input type="text" class="form-control font-weight-bold text-danger font-20" placeholder="0"
                                name="kredit" id="kredit" required autocomplete="off">
                        </div>
                        <small class="text-muted font-11 mt-1 d-block">
                            Pilih nominal cepat penarikan santri:
                        </small>
                    </div>

                    {{-- Quick Amount Chips --}}
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <button type="button" class="btn btn-sm btn-outline-danger chip-nominal-tarik" data-amount="10000">Rp 10.000</button>
                        <button type="button" class="btn btn-sm btn-outline-danger chip-nominal-tarik" data-amount="20000">Rp 20.000</button>
                        <button type="button" class="btn btn-sm btn-outline-danger chip-nominal-tarik" data-amount="50000">Rp 50.000</button>
                        <button type="button" class="btn btn-sm btn-outline-danger chip-nominal-tarik" data-amount="100000">Rp 100.000</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary chip-clear-tarik">Reset</button>
                    </div>

                    <div class="mb-4">
                        <label for="tujuan" class="form-label small text-muted font-weight-bold">Keperluan / Tujuan Penarikan</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bx bx-note"></i></span>
                            <input type="text" class="form-control" placeholder="Contoh: Uang Jajan, Beli Kitab, Perlengkapan"
                                name="tujuan" id="tujuan" value="Uang Jajan">
                        </div>
                        <small class="text-muted font-11">Jika dikosongkan, otomatis tercatat sebagai "Uang Jajan".</small>
                    </div>

                    <div class="d-flex justify-content-end align-items-center gap-2">
                        <button type="submit" id="btn_submit_penarikan" class="btn btn-danger px-4 py-2 font-weight-bold d-flex align-items-center gap-2">
                            <i class="bx bx-check-double font-20"></i>
                            <span>Simpan Penarikan</span>
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

                <div class="p-3 rounded radius-10 bg-light-danger text-danger border border-danger">
                    <span class="small d-block text-muted">Saldo Tabungan Saat Ini</span>
                    <h4 class="font-weight-bold mb-0 text-danger">Rp <span id="saldo">0</span></h4>
                </div>
            </div>
        </div>
    </div>
</div>

@push('js')
    <script>
        $(document).ready(function() {
            $('.chip-nominal-tarik').on('click', function() {
                var amount = $(this).data('amount');
                $('#kredit').val(amount);
            });

            $('.chip-clear-tarik').on('click', function() {
                $('#kredit').val('');
            });

            $('#kredit').on('input', function() {
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

                $('#alert_withdrawal_status').hide();
                $('#btn_submit_penarikan').prop('disabled', false);
                $('#kredit').focus();
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
                $('#alert_withdrawal_status').hide();
                $('#btn_submit_penarikan').prop('disabled', false);
            }

            var searchTimeout = null;
            function performLookup(noInduk) {
                $('#spinner').show();
                $.ajax({
                    url: "{{ route('transaksi.index') }}",
                    method: "GET",
                    data: {
                        no_induk: noInduk,
                        jenis: "Penarikan"
                    },
                    success: function(res) {
                        $('#spinner').hide();
                        if (res.data) {
                            populateSantri(res.data);
                        } else {
                            clearSantri();
                            if (res.message) {
                                $('#withdrawal_status_text').html(res.message);
                                $('#alert_withdrawal_status').show();
                                $('#btn_submit_penarikan').prop('disabled', true);
                            }
                            if (typeof Lobibox !== 'undefined') {
                                Lobibox.notify('warning', {
                                    pauseDelayOnHover: true,
                                    icon: 'bx bx-error-circle',
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

            $('#form_penarikan').on('submit', function() {
                var btn = $('#btn_submit_penarikan');
                btn.prop('disabled', true);
                btn.html('<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Memproses...');
            });
        });
    </script>
@endpush
