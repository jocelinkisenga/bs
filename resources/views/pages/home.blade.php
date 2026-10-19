@extends("layouts.home")

@section('content')
    <livewire:home>
@endsection

@section('script')
    <script>
        window.addEventListener('close-modal', event => {
            $("#recents").modal('hide');
            $("#create").modal('hide');
        });

        function commande_search() {
            // Declare variables
            var input, filter, table, tr, td, i, txtValue;
            input = document.getElementById("myInput");
            filter = input.value.toUpperCase();
            table = document.getElementById("myTable");
            tr = table.getElementsByTagName("tr");

            // Loop through all table rows, and hide those who don't match the search query
            for (i = 0; i < tr.length; i++) {
                td = tr[i].getElementsByTagName("td")[0];
                if (td) {
                    txtValue = td.textContent || td.innerText;
                    if (txtValue.toUpperCase().indexOf(filter) > -1) {
                        tr[i].style.display = "";
                    } else {
                        tr[i].style.display = "none";
                    }
                }
            }
        }

        // Filtre des produits (recherche instantanée, côté client)
        document.addEventListener('input', function (e) {
            if (e.target.id !== 'pos-search') return;

            var root = e.target.closest('.pos-app');
            var q = e.target.value.trim().toLowerCase();

            root.classList.toggle('is-searching', q !== '');
            root.querySelectorAll('.pos-product').forEach(function (el) {
                el.hidden = q !== '' && el.dataset.name.indexOf(q) === -1;
            });
        });
    </script>
@endsection