<!DOCTYPE html>
<html lang="en" class="{{ session()->get('theme') }}">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>@yield('title', config('app.name'))</title>
    <link rel="icon"
        href="{{ \App\Models\Setting::getFaviconUrl($setting ?? null) }}"
        type="image/png" />
    <link href="{{ url('assets/plugins/vectormap/jquery-jvectormap-2.0.2.css') }}" rel="stylesheet" />
    <link href="{{ url('assets/plugins/simplebar/css/simplebar.css') }}" rel="stylesheet" />
    <link href="{{ url('assets/plugins/perfect-scrollbar/css/perfect-scrollbar.css') }}" rel="stylesheet" />
    <link href="{{ url('assets/plugins/metismenu/css/metisMenu.min.css') }}" rel="stylesheet" />
    <link href="{{ url('assets/css/pace.min.css') }}" rel="stylesheet" />
    <script src="{{ url('assets/js/pace.min.js') }}"></script>
    <link rel="stylesheet" href="{{ url('assets/css/bootstrap.min.css') }}" />
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;600&family=Roboto&display=swap" />
    <link rel="stylesheet" href="{{ url('assets/fonts/fonts.css') }}" />
    <link rel="stylesheet" href="{{ url('assets/css/icons.css') }}" />
    <link rel="stylesheet" href="{{ url('assets/css/app.css') }}" />
    <link rel="stylesheet" href="{{ url('assets/css/dark-sidebar.css') }}" />
    <link rel="stylesheet" href="{{ url('assets/css/dark-theme.css') }}" />
    <link rel="stylesheet" href="{{ url('assets/plugins/notifications/css/lobibox.min.css') }}" />
    <link href="{{ url('assets/plugins/datatable/css/dataTables.bootstrap4.min.css') }}" rel="stylesheet"
        type="text/css">
    <link href="{{ url('assets/plugins/datatable/css/buttons.bootstrap4.min.css') }}" rel="stylesheet" type="text/css">
    <link href="{{ url('assets/plugins/select2/css/select2.min.css') }}" rel="stylesheet" type="text/css">
    <link href="{{ url('assets/plugins/select2/css/select2-bootstrap4.css') }}" rel="stylesheet" type="text/css">
    @stack('css')
</head>

<body>
    <div class="wrapper">
        @auth
            <x-navbar :setting="$setting ?? null"></x-navbar>
            <x-header></x-header>
        @endauth
        @yield('content')
        @auth
            <div class="overlay toggle-btn-mobile"></div><a href="javaScript:;" class="back-to-top"><i
                    class='bx bxs-up-arrow-alt'></i></a>
            <x-footer></x-footer>
        @endauth
    </div>
    <x-theme></x-theme>
    <script src="{{ url('assets/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ url('assets/js/jquery.min.js') }}"></script>
    @auth
        <script src="{{ url('assets/plugins/simplebar/js/simplebar.min.js') }}"></script>
        <script src="{{ url('assets/plugins/metismenu/js/metisMenu.min.js') }}"></script>
        <script src="{{ url('assets/plugins/perfect-scrollbar/js/perfect-scrollbar.js') }}"></script>
        <script src="{{ url('assets/plugins/apexcharts-bundle/js/apexcharts.min.js') }}"></script>
        <script src="{{ url('assets/js/app.js') }}"></script>
        <script src="{{ url('assets/plugins/notifications/js/lobibox.min.js') }}"></script>
        <script src="{{ url('assets/plugins/notifications/js/notifications.min.js') }}"></script>
        <script src="{{ url('assets/plugins/notifications/js/notification-custom-script.js') }}"></script>
        <script src="{{ url('assets/plugins/datatable/js/jquery.dataTables.min.js') }}" attribute="required"></script>
        <script src="{{ url('assets/plugins/select2/js/select2.min.js') }}"></script>
        <script src="{{ url('assets/plugins/bootstrap-material-datetimepicker/js/moment.min.js') }}"></script>
        <script>
            if (typeof moment !== 'undefined') {
                moment.updateLocale('id', {
                    months: 'Januari_Februari_Maret_April_Mei_Juni_Juli_Agustus_September_Oktober_November_Desember'.split('_'),
                    monthsShort: 'Jan_Feb_Mar_Apr_Mei_Jun_Jul_Ags_Sep_Okt_Nov_Des'.split('_'),
                    weekdays: 'Minggu_Senin_Selasa_Rabu_Kamis_Jumat_Sabtu'.split('_'),
                    weekdaysShort: 'Min_Sen_Sel_Rab_Kam_Jum_Sab'.split('_'),
                    weekdaysMin: 'Mg_Sn_Sl_Rb_Km_Jm_Sb'.split('_'),
                });
                moment.locale('id');
            }

            if (window.jQuery && $.fn && $.fn.dataTable) {
                $.extend(true, $.fn.dataTable.defaults, {
                    language: {
                        processing: '<div class="d-flex justify-content-center my-3"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Memuat data...</span></div></div>',
                        search: "Cari:",
                        lengthMenu: "Tampilkan _MENU_ entri",
                        info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ entri",
                        infoEmpty: "Menampilkan 0 sampai 0 dari 0 entri",
                        infoFiltered: "(disaring dari _MAX_ total entri)",
                        zeroRecords: '<div class="text-center py-4 text-muted"><i class="bx bx-folder-open font-30 d-block mb-1"></i>Tidak ditemukan data yang sesuai</div>',
                        emptyTable: '<div class="text-center py-4 text-muted"><i class="bx bx-folder-open font-30 d-block mb-1"></i>Tidak ada data yang tersedia pada tabel ini</div>',
                        paginate: {
                            first: "Pertama",
                            previous: "Sebelumnya",
                            next: "Berikutnya",
                            last: "Terakhir"
                        }
                    }
                });

                var bulanIndoList = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

                $.fn.dataTable.render.indonesianDate = function () {
                    return function (data, type, row) {
                        if (!data) return '-';
                        if (type === 'sort' || type === 'type') return data;
                        try {
                            if (typeof moment !== 'undefined') {
                                var m = moment(data);
                                if (m.isValid()) return m.locale('id').format('DD MMMM YYYY');
                            }
                            var d = new Date(data);
                            if (isNaN(d.getTime())) return data;
                            var day = ('0' + d.getDate()).slice(-2);
                            var month = bulanIndoList[d.getMonth()];
                            var year = d.getFullYear();
                            return day + ' ' + month + ' ' + year;
                        } catch (e) {
                            return data;
                        }
                    };
                };

                $.fn.dataTable.render.indonesianDateTime = function () {
                    return function (data, type, row) {
                        if (!data) return '-';
                        if (type === 'sort' || type === 'type') return data;
                        try {
                            if (typeof moment !== 'undefined') {
                                var m = moment(data);
                                if (m.isValid()) return m.locale('id').format('DD MMMM YYYY HH:mm');
                            }
                            var d = new Date(data);
                            if (isNaN(d.getTime())) return data;
                            var day = ('0' + d.getDate()).slice(-2);
                            var month = bulanIndoList[d.getMonth()];
                            var year = d.getFullYear();
                            var hours = ('0' + d.getHours()).slice(-2);
                            var minutes = ('0' + d.getMinutes()).slice(-2);
                            return day + ' ' + month + ' ' + year + ' ' + hours + ':' + minutes;
                        } catch (e) {
                            return data;
                        }
                    };
                };
            }
        </script>
        @if (flash()->message)
            <script>
                Lobibox.notify("{{ flash()->class }}", {
                    pauseDelayOnHover: true,
                    icon: 'bx bx-info-circle',
                    continueDelayOnInactiveTab: false,
                    position: 'top right',
                    size: 'mini',
                    msg: "{{ flash()->message }}"
                });
            </script>
        @endif
    @endauth
    @stack('js')
</body>

</html>
