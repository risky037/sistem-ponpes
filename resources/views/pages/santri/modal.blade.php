<div class="table-responsive">
    <table class="table table-bordered table-striped align-middle">
        <tbody>
            <tr>
                <td style="width: 30%;" class="font-weight-bold">Foto Santri</td>
                <td>
                    @if ($item->foto && $item->foto !== 'santri.png')
                        <img src="{{ url('storage/uploads/santri/' . $item->foto) }}" alt="Foto Santri" class="img-fluid rounded border" style="width: 90px; height: 90px; object-fit: cover;">
                    @else
                        <img src="{{ url('img/santri.png') }}" alt="Foto Santri" class="img-fluid rounded-circle border" style="width: 80px; height: 80px; object-fit: cover;">
                    @endif
                </td>
            </tr>
            <tr>
                <td class="font-weight-bold">Nomor Induk (NIS)</td>
                <td><span class="badge bg-light text-dark font-14">{{ $item->no_induk }}</span></td>
            </tr>
            <tr>
                <td class="font-weight-bold">Nama Lengkap</td>
                <td class="font-weight-bold text-dark">{{ $item->user->name }}</td>
            </tr>
            <tr>
                <td class="font-weight-bold">Jenis Kelamin</td>
                <td>
                    @if ($item->jenis_kelamin === 'Laki-Laki')
                        <span class="badge bg-light-primary text-primary font-12"><i class="bx bx-male-sign"></i> Laki-Laki</span>
                    @else
                        <span class="badge bg-light-info text-info font-12"><i class="bx bx-female-sign"></i> Perempuan</span>
                    @endif
                </td>
            </tr>
            <tr>
                <td class="font-weight-bold">Tempat, Tanggal Lahir</td>
                <td>{{ $item->tempat_lahir }}, {{ \Illuminate\Support\Carbon::parse($item->tanggal_lahir)->translatedFormat('d F Y') }}</td>
            </tr>
            <tr>
                <td class="font-weight-bold">Data Kependudukan</td>
                <td>NIK: {{ $item->nik ?? '-' }} | No. KK: {{ $item->kk ?? '-' }}</td>
            </tr>
            <tr>
                <td class="font-weight-bold">Alamat Lengkap</td>
                <td>{{ $item->alamat_santri->alamat_lengkap ?? '-' }}</td>
            </tr>
            <tr>
                <td class="font-weight-bold">Status Santri</td>
                <td>
                    <x-status-badge :status="$item->status" />
                </td>
            </tr>
            <tr>
                <td class="font-weight-bold">Tahun Masuk</td>
                <td>{{ $item->tahun_masuk }} ({{ $item->tahun_masuk_hijriyah }} H)</td>
            </tr>
            @if ($item->student_batch)
                <tr>
                    <td class="font-weight-bold">Angkatan</td>
                    <td><span class="badge bg-light-success text-success">{{ $item->student_batch->name }}</span></td>
                </tr>
            @endif
            @if ($item->status === 'Santri Alumni')
                <tr>
                    <td class="font-weight-bold">Tanggal Boyong</td>
                    <td>{{ $item->tanggal_boyong ?? '-' }} ({{ $item->tanggal_boyong_hijriyah ?? '-' }} H)</td>
                </tr>
            @endif
            @if ($item->status === 'Santri Aktif')
                <tr>
                    <td class="font-weight-bold">Kelas Saat Ini</td>
                    <td>
                        {{ isset($item->kelas_santri) && $item->kelas_santri->kelas ? $item->kelas_santri->kelas->tingkatan . ' - ' . $item->kelas_santri->kelas->kelas : 'Belum ditentukan' }}
                    </td>
                </tr>
                <tr>
                    <td class="font-weight-bold">Kamar Asrama</td>
                    <td>
                        {{ isset($item->kamar_santri) && $item->kamar_santri->kamar ? $item->kamar_santri->kamar->nama . ' (Blok ' . $item->kamar_santri->kamar->blok . ')' : 'Belum ditentukan' }}
                    </td>
                </tr>
            @endif
            <tr>
                <td class="font-weight-bold">WhatsApp Santri</td>
                <td>
                    {{ $item->whatsapp ?? '-' }}
                    @if ($item->whatsapp)
                        <a href="{{ \App\Helpers\Whatsapp::url($item->whatsapp, 'Assalamualaikum ' . $item->user->name) }}" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-success ms-2">
                            <i class="bx bxl-whatsapp"></i> Chat Santri
                        </a>
                    @endif
                </td>
            </tr>
            <tr>
                <td class="font-weight-bold">Nama Ayah / Ibu</td>
                <td>
                    Ayah: {{ $item->wali_santri->nama_ayah ?? '-' }}<br>
                    Ibu: {{ $item->wali_santri->nama_ibu ?? '-' }}
                    @if ($item->whatsapp)
                        <a href="{{ \App\Helpers\Whatsapp::url($item->whatsapp, 'Assalamualaikum Bapak/Ibu Wali dari ' . $item->user->name) }}" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-success ms-2 mt-1">
                            <i class="bx bxl-whatsapp"></i> Hubungi Wali
                        </a>
                    @endif
                </td>
            </tr>
        </tbody>
    </table>
</div>
