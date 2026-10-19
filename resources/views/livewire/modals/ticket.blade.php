@if (!empty($invoce))
    @php
        // Calculs locaux au ticket (indépendants de la facture)
        $devise = '$';
        $t_sous = 0;
        foreach ($invoce as $ligne) {
            $t_sous += $ligne->qty * $ligne->price;
        }
        $t_pct    = $invoce[0]->pourcentage ?? 0;
        $t_remise = $t_pct > 0 ? ($t_sous / 100) * $t_pct : 0;
        $t_total  = $t_sous - $t_remise;
    @endphp

    <div id="ticketCaisse" class="ticket">

        <div class="ticket__center">
            <div class="ticket__name">KIN MARCHE</div>
            <div>Avenue Square 23, 67</div>
            <div>Tél. +243 994 445 56</div>
        </div>

        <div class="ticket__sep"></div>

        <div class="ticket__row"><span>Date</span><span>{{ date('d/m/Y H:i') }}</span></div>
        <div class="ticket__row"><span>Ticket</span><span>{{ $invoce[0]->code ?? '—' }}</span></div>

        <div class="ticket__sep"></div>

        <table class="ticket__items">
            <thead>
                <tr>
                    <th>Article</th>
                    <th class="r">Qté</th>
                    <th class="r">Montant</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($invoce as $item)
                    <tr>
                        <td class="name">
                            {{ $item->name }}
                            <small>{{ $item->price }} {{ $devise }} l'unité</small>
                        </td>
                        <td class="r">{{ $item->qty }}</td>
                        <td class="r">{{ $item->qty * $item->price }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="ticket__sep"></div>

        <div class="ticket__row"><span>Sous-total</span><span>{{ $t_sous }} {{ $devise }}</span></div>

        @if ($t_pct > 0)
            <div class="ticket__row">
                <span>Remise ({{ $t_pct }} %)</span>
                <span>- {{ $t_remise }} {{ $devise }}</span>
            </div>
        @endif

        <div class="ticket__sep"></div>

        <div class="ticket__row ticket__total">
            <span>TOTAL</span>
            <span>{{ $t_total }} {{ $devise }}</span>
        </div>

        <div class="ticket__sep"></div>

        <div class="ticket__center ticket__foot">
            <div>Merci de votre visite !</div>
            <div>À bientôt</div>
        </div>
    </div>

    <script>
        function imprimerTicketCaisse() {
            document.body.classList.add('print-ticket');

            window.addEventListener('afterprint', function restaurer() {
                document.body.classList.remove('print-ticket');
                window.removeEventListener('afterprint', restaurer);
            });

            window.print();
        }
    </script>
@endif

<style>
    /* ---------- Ticket (écran : masqué) ---------- */
    #ticketCaisse {
        display: none;
    }

    .ticket {
        width: 72mm;
        margin: 0 auto;
        padding: 3mm 0;
        background: #fff;
        color: #000;
        font-family: "Courier New", Courier, monospace;
        font-size: 12px;
        line-height: 1.35;
    }
    .ticket__center { text-align: center; }
    .ticket__name {
        font-size: 16px;
        font-weight: bold;
        letter-spacing: .05em;
    }
    .ticket__sep {
        border-top: 1px dashed #000;
        margin: 4px 0;
    }
    .ticket__row {
        display: flex;
        justify-content: space-between;
        gap: 6px;
        padding: 1px 0;
    }
    .ticket__total {
        font-size: 15px;
        font-weight: bold;
        padding: 3px 0;
    }
    .ticket__items {
        width: 100%;
        table-layout: fixed;
        border-collapse: collapse;
    }
    .ticket__items th,
    .ticket__items td {
        padding: 2px 0;
        text-align: left;
        vertical-align: top;
    }
    .ticket__items th:nth-child(1) { width: 50%; }
    .ticket__items th:nth-child(2) { width: 14%; }
    .ticket__items th:nth-child(3) { width: 36%; }
    .ticket__items .r { text-align: right; }
    .ticket__items th { font-weight: bold; border-bottom: 1px solid #000; }
    .ticket__items .name { word-wrap: break-word; }
    .ticket__items .name small {
        display: block;
        font-size: 10px;
        color: #333;
    }
    .ticket__foot {
        margin-top: 6px;
        font-size: 11px;
    }

    /* ---------- Impression ticket ---------- */
   @media print {
    @page {
        size: 80mm auto;
        margin: 0;
    }

    body.print-ticket,
    body.print-ticket .pos-app {
        background: #fff !important;
    }

    /* Tout est invisible, sauf le ticket */
    body.print-ticket * {
        visibility: hidden !important;
    }
    body.print-ticket #ticketCaisse,
    body.print-ticket #ticketCaisse * {
        visibility: visible !important;
    }

    body.print-ticket #ticketCaisse {
        display: block !important;
        position: absolute !important;
        top: 0;
        left: 0;
        width: 72mm;
    }
}
</style>