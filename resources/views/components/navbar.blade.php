<!--sidebar-wrapper-->
@props(['setting'])
<div class="sidebar-wrapper" data-simplebar="true">
    <div class="sidebar-header">
        <div class="">
            <img src="{{ $setting != null ? url('storage/uploads/setting/', $setting->logo) : url('assets/images/logo-icon.png') }}"
                class="logo-icon-2" alt="" />
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
