@php
    // Devise affichée sur la facture (modifier ici si besoin : '$', 'F', 'FC'...)
    $devise = '$';
@endphp

@if (!empty($last_commande) and $last_commande->status == false)
    <div wire:ignore.self class="modal fade" id="facture" tabindex="-1" aria-labelledby="factureLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content pos-invoice-modal">

                <div class="modal-header">
                    <h5 class="modal-title" id="factureLabel">
                        <i class="bi bi-receipt me-2"></i>Facture
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>

                <div class="modal-body">

                    {{-- Actions : masquées à l'impression --}}
                    <div class="pos-invoice-actions">
                        @if (!empty($invoce))
                            <button type="button" class="btn btn-primary btn-sm" onclick="window.print()">
                                <i class="bi bi-printer me-1"></i> Imprimer
                            </button>
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="downloadInvoicePdf()">
                                <i class="bi bi-file-earmark-pdf me-1"></i> Télécharger PDF
                            </button>
                            <button type="button" class="btn btn-dark btn-sm" onclick="imprimerTicketCaisse()">
    <i class="bi bi-receipt-cutoff me-1"></i> Ticket caisse
</button>

                            @if ($invoce[0]->invoiced == 0)
                                <button type="button" class="btn btn-danger btn-sm"
                                    wire:click="invoice({{ $invoce[0]->pId }})">
                                    <i class="bi bi-check2-circle me-1"></i> Facturer
                                </button>
                            @endif
                        @endif
                    </div>

                    {{-- ============ FACTURE ============ --}}
                    <div id="printDiv" class="pos-invoice">

                        <header class="pos-invoice__head">
                            <div class="pos-invoice__brand">
                                <img src="assets/img/logo.png" alt="" class="pos-invoice__logo">
                                <div>
                                    <h2>Kin Marche</h2>
                                    <p>
                                        Avenue Square 23, 67<br>
                                        Tél. +243 994 445 56 · Fax 2233455
                                    </p>
                                </div>
                            </div>
                            <div class="pos-invoice__title">
                                <span>Facture</span>
                            </div>
                        </header>

                        <div class="pos-invoice__meta">
                            <div>
                                <small>Date d'émission</small>
                                <strong>{{ date('d/m/Y') }}</strong>
                            </div>
                            <div>
                                <small>Référence</small>
                                <strong class="pos-invoice__code">
                                    {{ !empty($invoce) ? ($invoce[0]->code ?? 'Aucun') : '—' }}
                                </strong>
                            </div>
                        </div>

                        <table class="pos-invoice__table">
                            <thead>
                                <tr>
                                    <th>Produit</th>
                                    <th class="num">Prix unitaire</th>
                                    <th class="center">Qté</th>
                                    <th class="num">Montant</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if (!empty($invoce))
                                    @foreach ($invoce as $item)
                                        <tr>
                                            <td class="pos-invoice__name">{{ $item->name }}</td>
                                            <td class="num">{{ $item->price }} {{ $devise }}</td>
                                            <td class="center">{{ $item->qty }}</td>
                                            <td class="num">{{ $item->qty * $item->price }} {{ $devise }}</td>
                                            <?php $facture_total += $item->qty * $item->price; ?>
                                        </tr>
                                    @endforeach
                                @endif
                            </tbody>
                        </table>

                        <div class="pos-invoice__totals">
                            <div class="line">
                                <span>Sous-total</span>
                                <span>{{ $facture_total }} {{ $devise }}</span>
                            </div>

                            @if ($invoce != null and $invoce[0]->pourcentage > 0)
                                <div class="line discount">
                                    <span>Réduction ({{ $invoce[0]->pourcentage }} %)</span>
                                    <span>− {{ ($facture_total / 100) * $invoce[0]->pourcentage }} {{ $devise }}</span>
                                </div>
                            @endif

                            <div class="grand">
                                <span>Total à payer</span>
                                <span>
                                    @if ($invoce != null and $invoce[0]->pourcentage != 0)
                                        {{ $facture_total - ($facture_total / 100) * $invoce[0]->pourcentage }}
                                    @else
                                        {{ $facture_total }}
                                    @endif
                                    {{ $devise }}
                                </span>
                            </div>
                        </div>

                        <footer class="pos-invoice__foot">
                            <span>Merci de votre confiance !</span>
                            <span>Kin Marche</span>
                        </footer>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Librairie en ligne pour le téléchargement PDF --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script>
        function downloadInvoicePdf() {
            var code = document.querySelector('#printDiv .pos-invoice__code');
            var name = code ? code.textContent.trim() : 'facture';

            html2pdf()
                .set({
                    margin: [10, 10, 10, 10],
                    filename: 'facture-' + name + '.pdf',
                    image: { type: 'jpeg', quality: 0.98 },
                    html2canvas: { scale: 2, useCORS: true },
                    jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' },
                    pagebreak: { mode: ['css', 'legacy'] }
                })
                .from(document.getElementById('printDiv'))
                .save();
        }
    </script>
@endif

{{-- =================== STYLES =================== --}}
<style>
    /* ---------- Écran ---------- */
    .pos-invoice-modal {
        border: 0;
        border-radius: 16px;
        overflow: hidden;
    }
    .pos-invoice-actions {
        display: flex;
        flex-wrap: wrap;
        gap: .5rem;
        padding: .9rem 1.25rem;
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
    }

    .pos-invoice {
        padding: 2rem 2.25rem;
        background: #fff;
        color: #0f172a;
        font-size: .9rem;
    }
    .pos-invoice__head {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 1rem;
        padding-bottom: 1.25rem;
        border-bottom: 3px solid #4f46e5;
    }
    .pos-invoice__brand {
        display: flex;
        align-items: center;
        gap: .9rem;
    }
    .pos-invoice__logo {
        width: 56px;
        height: 56px;
        object-fit: contain;
    }
    .pos-invoice__brand h2 {
        margin: 0;
        font-size: 1.25rem;
        font-weight: 800;
    }
    .pos-invoice__brand p {
        margin: .2rem 0 0;
        color: #64748b;
        font-size: .8rem;
        line-height: 1.5;
    }
    .pos-invoice__title span {
        display: block;
        font-size: 1.6rem;
        font-weight: 900;
        letter-spacing: .14em;
        text-transform: uppercase;
        color: #4f46e5;
    }

    .pos-invoice__meta {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: .75rem;
        margin: 1.25rem 0 1.5rem;
    }
    .pos-invoice__meta div {
        padding: .7rem .9rem;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
    }
    .pos-invoice__meta small {
        display: block;
        font-size: .68rem;
        font-weight: 700;
        letter-spacing: .06em;
        text-transform: uppercase;
        color: #64748b;
    }
    .pos-invoice__meta strong {
        font-size: .95rem;
    }

    .pos-invoice__table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
    }
    .pos-invoice__table thead th {
        padding: .7rem .8rem;
        background: #eef2ff;
        color: #3730a3;
        font-size: .72rem;
        font-weight: 800;
        letter-spacing: .06em;
        text-transform: uppercase;
    }
    .pos-invoice__table thead th:first-child { border-radius: 8px 0 0 8px; }
    .pos-invoice__table thead th:last-child  { border-radius: 0 8px 8px 0; }
    .pos-invoice__table td {
        padding: .75rem .8rem;
        border-bottom: 1px solid #eef2f7;
        vertical-align: middle;
    }
    .pos-invoice__table tbody tr:nth-child(even) td {
        background: #fafbff;
    }
    .pos-invoice__table .num {
        text-align: right;
        white-space: nowrap;
        font-variant-numeric: tabular-nums;
    }
    .pos-invoice__table .center {
        text-align: center;
    }
    .pos-invoice__name {
        font-weight: 700;
        text-transform: uppercase;
    }

    .pos-invoice__totals {
        width: min(340px, 100%);
        margin: 1.25rem 0 0 auto;
    }
    .pos-invoice__totals .line {
        display: flex;
        justify-content: space-between;
        padding: .4rem 0;
        color: #475569;
    }
    .pos-invoice__totals .line.discount {
        color: #15803d;
    }
    .pos-invoice__totals .grand {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: .6rem;
        padding: .9rem 1rem;
        background: #4f46e5;
        color: #fff;
        border-radius: 12px;
        font-size: 1.05rem;
        font-weight: 800;
        font-variant-numeric: tabular-nums;
    }

    .pos-invoice__foot {
        display: flex;
        justify-content: space-between;
        margin-top: 2rem;
        padding-top: 1rem;
        border-top: 1px dashed #cbd5e1;
        color: #64748b;
        font-size: .8rem;
    }

    /* ---------- Impression ---------- */
    @media print {
    @page {
        size: A4;
        margin: 12mm;
    }

    * {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    /* Le fond gris de l'application ne doit pas être imprimé */
    body:not(.print-ticket),
    body:not(.print-ticket) .pos-app {
        background: #fff !important;
    }

    /* Tout est invisible (mais garde sa place), sauf la facture */
    body:not(.print-ticket) * {
        visibility: hidden !important;
    }
    body:not(.print-ticket) #printDiv,
    body:not(.print-ticket) #printDiv * {
        visibility: visible !important;
    }

    /* Retirer les contraintes du modal pour que la facture soit à plat */
    body:not(.print-ticket) #facture,
    body:not(.print-ticket) #facture .modal-dialog,
    body:not(.print-ticket) #facture .modal-content,
    body:not(.print-ticket) #facture .modal-body {
        position: static !important;
        transform: none !important;
        max-width: none !important;
        margin: 0 !important;
        padding: 0 !important;
        border: 0 !important;
        box-shadow: none !important;
        border-radius: 0 !important;
        overflow: visible !important;
    }

    body:not(.print-ticket) #printDiv {
        position: absolute !important;
        top: 0;
        left: 0;
        width: 100%;
    }

    body:not(.print-ticket) .pos-invoice {
        padding: 0 !important;
    }

    body:not(.print-ticket) .pos-invoice__table tr,
    body:not(.print-ticket) .pos-invoice__totals,
    body:not(.print-ticket) .pos-invoice__foot {
        page-break-inside: avoid;
    }
}
</style>