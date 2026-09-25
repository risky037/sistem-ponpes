<!--sidebar-wrapper-->
@props(['setting'])
<div class="sidebar-wrapper" data-simplebar="true">
    <div class="sidebar-header">
        <div class="">
            <img src="{{ \App\Models\Setting::getLogoUrl($setting ?? null) }}"
                class="logo-icon-2" alt="Logo" onerror="this.onerror=null;this.src='{{ asset('assets/images/logo-icon.png') }}';" />
        </div>
        <div>
            <h4 class="logo-text">Digitren</h4>
        </div>
        <a href="javascript:;" class="toggle-btn ms-auto"> <i class="bx bx-menu"></i>
        </a>
    </div>
    <!--navigation-->
    <ul class="metismenu" id="menu">
        <li class="{{ request()->routeIs('dashboard.*') ? 'mm-active' : '' }}">
            <a href="{{ route('dashboard') }}">
                <div class="parent-icon icon-color-1"><i class="bx bx-home-alt"></i>
                </div>
                <div class="menu-title">Dashboard</div>
            </a>
        </li>
        @role('Administrator|Pengurus')
            <!-- master data -->
            <li class="menu-label">Master Data</li>
            <li class="{{ request()->routeIs('kamar.*') ? 'mm-active' : '' }}">
                <a href="{{ route('kamar.index') }}">
                    <div class="parent-icon icon-color-10"> <i class="bx bx-home-alt"></i>
                    </div>
                    <div class="menu-title">Kamar</div>
                </a>
            </li>
            <li class="{{ request()->routeIs('kelas.*') ? 'mm-active' : '' }}">
                <a href="{{ route('kelas.index') }}">
                    <div class="parent-icon icon-color-3"> <i class="bx bx-devices"></i>
                    </div>
                    <div class="menu-title">Kelas</div>
                </a>
            </li>
            <li class="{{ request()->routeIs('academic-year.*') ? 'mm-active' : '' }}">
                <a href="{{ route('academic-year.index') }}">
                    <div class="parent-icon icon-color-2"> <i class="bx bx-calendar"></i>
                    </div>
                    <div class="menu-title">Tahun Ajaran</div>
                </a>
            </li>
            <li class="{{ request()->routeIs('student-batch.*') ? 'mm-active' : '' }}">
                <a href="{{ route('student-batch.index') }}">
                    <div class="parent-icon icon-color-5"> <i class="bx bx-group"></i>
                    </div>
                    <div class="menu-title">Angkatan Santri</div>
                </a>
            </li>
            <li class="{{ request()->routeIs('academic-enrollment.*') ? 'mm-active' : '' }}">
                <a href="{{ route('academic-enrollment.index') }}">
                    <div class="parent-icon icon-color-6"> <i class="bx bx-book-reader"></i>
                    </div>
                    <div class="menu-title">Pendaftaran Akademik</div>
                </a>
            </li>
            <li class="{{ request()->routeIs('wali-kelas-assignment.*') ? 'mm-active' : '' }}">
                <a href="{{ route('wali-kelas-assignment.index') }}">
                    <div class="parent-icon icon-color-7"> <i class="bx bx-user-pin"></i>
                    </div>
                    <div class="menu-title">Wali Kelas</div>
                </a>
            </li>
            <li class="{{ request()->routeIs('mapel.*') ? 'mm-active' : '' }}">
                <a href="{{ route('mapel.index') }}">
                    <div class="parent-icon icon-color-8"> <i class="bx bx-book-bookmark"></i>
                    </div>
                    <div class="menu-title">Mata Pelajaran</div>
                </a>
            </li>
            <li class="{{ request()->routeIs('teaching-assignment.*') ? 'mm-active' : '' }}">
                <a href="{{ route('teaching-assignment.index') }}">
                    <div class="parent-icon icon-color-9"> <i class="bx bx-chalkboard"></i>
                    </div>
                    <div class="menu-title">Penugasan Mengajar</div>
                </a>
            </li>
            <li class="{{ request()->routeIs('class-schedule.*') ? 'mm-active' : '' }}">
                <a href="{{ route('class-schedule.index') }}">
                    <div class="parent-icon icon-color-1"> <i class="bx bx-time-five"></i>
                    </div>
                    <div class="menu-title">Jadwal Pelajaran</div>
                </a>
            </li>
            <li class="{{ request()->routeIs('academic-calendar-event.*') ? 'mm-active' : '' }}">
                <a href="{{ route('academic-calendar-event.index') }}">
                    <div class="parent-icon icon-color-2"> <i class="bx bx-calendar-event"></i>
                    </div>
                    <div class="menu-title">Kalender Akademik</div>
                </a>
            </li>
            <li class="{{ request()->routeIs('attendance.*') ? 'mm-active' : '' }}">
                <a href="{{ route('attendance.index') }}">
                    <div class="parent-icon icon-color-3"> <i class="bx bx-check-square"></i>
                    </div>
                    <div class="menu-title">Presensi Kelas</div>
                </a>
            </li>
            @hasanyrole('Administrator|Pengurus|Guru')
            @canany(['assessment.definition.index', 'assessment.score.index', 'assessment.input'])
            <li class="{{ request()->routeIs('assessment.*') ? 'mm-active' : '' }}">
                <a class="has-arrow" href="javascript:;">
                    <div class="parent-icon icon-color-5"><i class="bx bx-clipboard"></i>
                    </div>
                    <div class="menu-title">Penilaian</div>
                </a>
                <ul>
                    @can('assessment.definition.index')
                    <li class="{{ request()->routeIs('assessment.definition.*') ? 'mm-active' : '' }}">
                        <a href="{{ route('assessment.definition.index') }}"><i class="bx bx-right-arrow-alt"></i>Definisi Nilai</a>
                    </li>
                    <li class="{{ request()->routeIs('assessment.component.*') ? 'mm-active' : '' }}">
                        <a href="{{ route('assessment.component.index') }}"><i class="bx bx-right-arrow-alt"></i>Komponen Nilai</a>
                    </li>
                    @endcan
                    @canany(['assessment.score.index', 'assessment.input'])
                    <li class="{{ request()->routeIs('assessment.score.*') ? 'mm-active' : '' }}">
                        <a href="{{ route('assessment.score.index') }}"><i class="bx bx-right-arrow-alt"></i>Input Nilai</a>
                    </li>
                    @endcanany
                </ul>
            </li>
            @endcanany
            @can('performance.index')
            <li class="{{ request()->routeIs('academic.performance.*') ? 'mm-active' : '' }}">
                <a href="{{ route('academic.performance.index') }}">
                    <div class="parent-icon icon-color-6"><i class="bx bx-bar-chart-alt-2"></i>
                    </div>
                    <div class="menu-title">Performa Akademik</div>
                </a>
            </li>
            @endcan
            @can('export.index')
            <li class="{{ request()->routeIs('academic.administration.*') ? 'mm-active' : '' }}">
                <a href="{{ route('academic.administration.index') }}">
                    <div class="parent-icon icon-color-1"><i class="bx bx-grid-alt"></i>
                    </div>
                    <div class="menu-title">Admin Akademik</div>
                </a>
            </li>
            <li class="{{ request()->routeIs('academic.export.*') ? 'mm-active' : '' }}">
                <a href="{{ route('academic.export.index') }}">
                    <div class="parent-icon icon-color-7"><i class="bx bx-download"></i>
                    </div>
                    <div class="menu-title">Ekspor Data</div>
                </a>
            </li>
            @endcan
            @can('intelligence.index')
            <li class="{{ request()->routeIs('academic.intelligence.*') ? 'mm-active' : '' }}">
                <a class="has-arrow" href="javascript:;">
                    <div class="parent-icon icon-color-3"><i class="bx bx-analyse"></i>
                    </div>
                    <div class="menu-title">Intelijen Akademik</div>
                </a>
                <ul>
                    <li class="{{ request()->routeIs('academic.intelligence.index') ? 'mm-active' : '' }}">
                        <a href="{{ route('academic.intelligence.index') }}"><i class="bx bx-right-arrow-alt"></i>Dashboard KPI</a>
                    </li>
                    <li class="{{ request()->routeIs('academic.intelligence.workload') ? 'mm-active' : '' }}">
                        <a href="{{ route('academic.intelligence.workload') }}"><i class="bx bx-right-arrow-alt"></i>Beban Mengajar Guru</a>
                    </li>
                    <li class="{{ request()->routeIs('academic.intelligence.subjects') ? 'mm-active' : '' }}">
                        <a href="{{ route('academic.intelligence.subjects') }}"><i class="bx bx-right-arrow-alt"></i>Analisis Mapel</a>
                    </li>
                </ul>
            </li>
            @endcan
            @endhasanyrole
            <li>
                <a class="has-arrow" href="javascript:;">
                    <div class="parent-icon icon-color-4"><i class="bx bx-user"></i>
                    </div>
                    <div class="menu-title">Santri</div>
                </a>
                <ul>
                    <li class="{{ request()->routeIs('santri.*') ? 'mm-active' : '' }}">
                        <a href="{{ route('santri.index') }}"><i class="bx bx-right-arrow-alt"></i>Santri
                        </a>
                    </li>
                    @role('Administrator')
                        <li class="{{ request()->routeIs('users.*') ? 'mm-active' : '' }}">
                            <a href="{{ route('users.index') }}"><i class="bx bx-right-arrow-alt"></i>Pengguna
                            </a>
                        </li>
                    @endrole
                </ul>
            </li>
        @endrole

        @hasanyrole('Administrator|Keuangan')
            <!-- tabungan -->
            <li class="menu-label">Tabungan</li>
            <li class="{{ request()->routeIs('saldo_debit.*') ? 'mm-active' : '' }}">
                <a href="{{ route('saldo_debit.index') }}">
                    <div class="parent-icon icon-color-5"><i class='bx bx-wallet-alt'></i>
                    </div>
                    <div class="menu-title">Tabungan</div>
                </a>
            </li>
            <li class="{{ request()->routeIs('transfer.*') ? 'mm-active' : '' }}">
                <a href="{{ route('transfer.index') }}">
                    <div class="parent-icon icon-color-1"><i class="bx bx-transfer-alt"></i>
                    </div>
                    <div class="menu-title">Transfer</div>
                </a>
            </li>
            <li class="{{ request()->routeIs('transaksi.*') ? 'mm-active' : '' }}">
                <a href="{{ route('transaksi.index') }}">
                    <div class="parent-icon icon-color-7"><i class='bx bx-money'></i>
                    </div>
                    <div class="menu-title">Transaksi</div>
                </a>
            </li>
        @endrole

        <!-- utilities -->
        @role('Administrator')
            <li class="menu-label">Utilitas</li>
            <li class="{{ request()->routeIs('riwayat.*') ? 'mm-active' : '' }}">
                <a href="{{ route('riwayat.index') }}">
                    <div class="parent-icon icon-color-8"><i class="bx bx-history"></i>
                    </div>
                    <div class="menu-title">Riwayat</div>
                </a>
            </li>
        @endrole
        <!-- utilities -->
        <!--end navigation-->
</div>
<!--end sidebar-wrapper-->
