/* =====================================================
   SELLIT · Fonctions d'impression
   Fichier statique chargé par le layout (hors Livewire).
   ===================================================== */

// Impression d'un bloc par ID (fenêtre séparée)
function printDiv() {
    var divContents = document.getElementById("printDiv").innerHTML;
    var printWindow = window.open('', '', 'height=200,width=400');
    printWindow.document.write('<html><head><title>Impression</title>');
    printWindow.document.write('</head><body>');
    printWindow.document.write(divContents);
    printWindow.document.write('</body></html>');
    printWindow.document.close();
    printWindow.print();
}

// Impression du coupon
function printCoupon() {
    var divContents = document.getElementById("printCoupon").innerHTML;
    var printWindow = window.open('', '', 'height=200,width=400');
    printWindow.document.write('<html><head><title>Coupon</title>');
    printWindow.document.write('</head><body>');
    printWindow.document.write(divContents);
    printWindow.document.write('</body></html>');
    printWindow.document.close();
    printWindow.print();
}

// Téléchargement de la facture en PDF (html2pdf.js)
function downloadInvoicePdf() {
    if (typeof html2pdf === 'undefined') {
        Swal.fire('Erreur', 'La librairie PDF n\'est pas chargée.', 'error');
        return;
    }

    var el = document.getElementById('printDiv');
    if (!el) return;

    var code = el.querySelector('.pos-invoice__code');
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
        .from(el)
        .save();
}

// Impression du ticket caisse 80 mm
function imprimerTicketCaisse() {
    document.body.classList.add('print-ticket');

    window.addEventListener('afterprint', function restaurer() {
        document.body.classList.remove('print-ticket');
        window.removeEventListener('afterprint', restaurer);
    });

    window.print();
}