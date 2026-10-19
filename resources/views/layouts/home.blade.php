<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#4f46e5">
    <meta name="robots" content="noindex, nofollow">
    <title>SELLIT · Caisse</title>

    <link rel="icon" type="image/x-icon" href="assets/img/favicon.png">

    {{-- Bootstrap --}}
    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap.min.css') }}">

    {{-- Icônes --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/plugins/fontawesome/css/all.min.css">

    {{-- Plugins --}}
    <link rel="stylesheet" href="assets/css/animate.css">
    <link rel="stylesheet" href="assets/plugins/owlcarousel/owl.carousel.min.css">
    <link rel="stylesheet" href="assets/plugins/owlcarousel/owl.theme.default.min.css">
    <link rel="stylesheet" href="assets/plugins/select2/css/select2.min.css">
    <link rel="stylesheet" href="assets/css/bootstrap-datetimepicker.min.css">
    <link rel="stylesheet" href="{{ asset('vendor/flasher/flasher.min.css') }}">

    {{-- Thème existant puis surcouche POS --}}
    <link rel="stylesheet" href="assets/css/style.css">
    {{-- <link rel="stylesheet" href="assets/css/pos.css"> --}}

    @livewireStyles
</head>

<body class="pos-body">

    @yield('content')

    <script src="{{ asset('assets/js/jquery-3.6.0.min.js') }}"></script>
    @livewireScripts

    @livewireScripts

    <script src="{{ asset('js/printThis.js') }}"></script>
    <script src="assets/js/feather.min.js"></script>
    <script src="assets/js/jquery.slimscroll.min.js"></script>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/jquery.dataTables.min.js"></script>
    <script src="assets/js/dataTables.bootstrap4.min.js"></script>
    <script src="assets/plugins/select2/js/select2.min.js"></script>
    <script src="assets/plugins/owlcarousel/owl.carousel.min.js"></script>
    <script src="assets/plugins/sweetalert/sweetalert2.all.min.js"></script>
    <script src="assets/plugins/sweetalert/sweetalerts.min.js"></script>
    <script src="assets/js/script.js"></script>

    {{-- Librairie PDF (en ligne) --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

    {{-- Fonctions d'impression (statique, hors Livewire) --}}
    <script src="{{ asset('assets/js/pos-print.js') }}"></script>

    <script>
        window.addEventListener('closeModal', function () {
            $('#dette').modal('hide');
            $('#create').modal('hide');
        });

        $("#facture-commande").on('click', function () {
            $("#fac").printThis({
                debug: false,
                importCSS: true,
                importStyle: false,
                printContainer: true,
                loadCSS: "",
                pageTitle: "UTOPIAN PRINT",
                removeInline: false,
                printDelay: 1,
                header: null,
                footer: null,
                base: false,
                formValues: true,
                canvas: false,
                doctypeString: "",
                removeScripts: false,
                copyTagClasses: false
            });
        });
    </script>

    {{-- Scripts propres à chaque page --}}
    @yield('script')

</body>
</html>