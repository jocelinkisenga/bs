<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RapportExport
{
    private const SEPARATEUR = ';';

    public function csv(string $onglet, Collection $produits, Collection $commandes, Carbon $from, Carbon $to): StreamedResponse
    {
        [$entete, $lignes] = $onglet === 'commandes'
            ? $this->lignesCommandes($commandes)
            : $this->lignesProduits($produits);

        return response()->streamDownload(function () use ($entete, $lignes) {
            $sortie = fopen('php://output', 'w');

            // BOM UTF-8 : Excel affiche correctement les accents
            fwrite($sortie, "\xEF\xBB\xBF");
            fputcsv($sortie, $entete, self::SEPARATEUR);

            foreach ($lignes as $ligne) {
                fputcsv($sortie, array_map([$this, 'cellule'], $ligne), self::SEPARATEUR);
            }

            fclose($sortie);
        }, $this->nomFichier($onglet, $from, $to, 'csv'), [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function xlsx(Collection $produits, Collection $commandes, Carbon $from, Carbon $to): StreamedResponse
    {
        $classeur = new Spreadsheet();

        [$enteteP, $lignesP] = $this->lignesProduits($produits);
        $this->feuille(
            $classeur->getActiveSheet(), 'Produits', $enteteP, $lignesP,
            [null, null, '#,##0', '#,##0', '#,##0', '#,##0', '#,##0', '#,##0', '#,##0', '#,##0', '#,##0', '#,##0']
        );

        [$enteteC, $lignesC] = $this->lignesCommandes($commandes);
        $this->feuille(
            $classeur->createSheet(), 'Commandes', $enteteC, $lignesC,
            [null, null, null, null, '#,##0', '#,##0', '0.0', '#,##0', '#,##0']
        );

        $classeur->setActiveSheetIndex(0);
        $ecrivain = new Xlsx($classeur);

        return response()->streamDownload(
            fn () => $ecrivain->save('php://output'),
            $this->nomFichier('rapport', $from, $to, 'xlsx'),
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        );
    }

    /** En-têtes et valeurs brutes de l'onglet produits. */
    public function lignesProduits(Collection $produits): array
    {
        $entete = [
            'Produit', 'Catégorie', 'Stock début', 'Entrées', 'Sorties', 'Stock fin',
            'Prix vente', 'Prix achat moyen', 'Qté vendue', 'CA brut', 'Coût', 'Marge',
        ];

        $lignes = $produits->map(fn ($p) => [
            $p->nom, $p->categorie, $p->stock_debut, $p->entrees, $p->sorties, $p->stock_fin,
            $p->prix_vente, $p->prix_achat, $p->vendues, $p->ca, $p->cout, $p->marge,
        ])->all();

        return [$entete, $lignes];
    }

    /** En-têtes et valeurs brutes de l'onglet commandes. */
    public function lignesCommandes(Collection $commandes): array
    {
        $entete = ['Code', 'Date', 'Statut', 'Facturée', 'Quantité', 'Brut', 'Remise %', 'Remise', 'Net'];

        $lignes = $commandes->map(fn ($c) => [
            $c->code,
            $c->date->format('d/m/Y H:i'),
            $c->confirmee ? 'Confirmée' : 'En cours',
            $c->facturee ? 'Oui' : 'Non',
            $c->quantite,
            $c->brut,
            $c->pct,
            $c->remise,
            $c->net,
        ])->all();

        return [$entete, $lignes];
    }

    /** Valeur d'une cellule CSV : nombres au format français (virgule décimale). */
    private function cellule(mixed $valeur): string
    {
        if ($valeur === null) {
            return '';
        }

        if (is_int($valeur)) {
            return (string) $valeur;
        }

        if (is_float($valeur)) {
            return number_format($valeur, 2, ',', '');
        }

        return (string) $valeur;
    }

    private function feuille(Worksheet $feuille, string $titre, array $entete, array $lignes, array $formats): void
    {
        $feuille->setTitle($titre);
        $feuille->fromArray($entete, null, 'A1');

        if ($lignes !== []) {
            $feuille->fromArray($lignes, null, 'A2');
        }

        $derniereColonne = chr(64 + count($entete));
        $derniereLigne   = count($lignes) + 1;

        // En-tête stylé et figé
        $feuille->getStyle("A1:{$derniereColonne}1")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F46E5']],
        ]);
        $feuille->freezePane('A2');

        // Format des colonnes numériques
        foreach ($formats as $index => $format) {
            if ($format === null) {
                continue;
            }

            $lettre = chr(65 + $index);
            $feuille->getStyle("{$lettre}2:{$lettre}{$derniereLigne}")
                ->getNumberFormat()
                ->setFormatCode($format);
        }

        foreach (range(0, count($entete) - 1) as $index) {
            $feuille->getColumnDimension(chr(65 + $index))->setAutoSize(true);
        }
    }

    private function nomFichier(string $onglet, Carbon $from, Carbon $to, string $extension): string
    {
        return sprintf(
            'rapport-%s-%s-au-%s.%s',
            $onglet,
            $from->format('Y-m-d'),
            $to->format('Y-m-d'),
            $extension
        );
    }
}