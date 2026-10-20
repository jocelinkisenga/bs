<?php

namespace App\Http\Controllers;

use App\Enums\RoleEnum;
use App\Repositorie\StockRepositorie;
use App\Services\RapportExport;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Throwable;

class RapportController extends Controller
{
    private const PERIODE_MAX_JOURS = 730;

    public function __construct(
        private StockRepositorie $stocks,
        private RapportExport $export,
    ) {}

    public function index(Request $request)
    {
        $this->autoriser();

        [$from, $to, $preset] = $this->periode($request);
        $onglet = $this->onglet($request);
        $companyId = $this->companyId();

        $produits  = $this->stocks->produits($companyId, $from, $to);
        $commandes = $this->stocks->commandes($companyId, $from, $to);
        $synthese  = $this->stocks->synthese($produits, $commandes);

        return view('pages.rapports', compact(
            'from', 'to', 'preset', 'onglet', 'produits', 'commandes', 'synthese'
        ));
    }

    public function export(Request $request, string $format)
    {
        $this->autoriser();
        abort_unless(in_array($format, ['csv', 'xlsx'], true), 404);

        [$from, $to] = $this->periode($request);
        $onglet    = $this->onglet($request);
        $companyId = $this->companyId();

        $produits  = $this->stocks->produits($companyId, $from, $to);
        $commandes = $this->stocks->commandes($companyId, $from, $to);

        return $format === 'csv'
            ? $this->export->csv($onglet, $produits, $commandes, $from, $to)
            : $this->export->xlsx($produits, $commandes, $from, $to);
    }

    /**
     * Période demandée : dates explicites (from/to) ou raccourci (preset).
     * Retourne [début, fin, nom du raccourci].
     */
    private function periode(Request $request): array
    {
        $aujourdhui = Carbon::today();
        $moisPrecedent = Carbon::today()->subMonthNoOverflow();

        $raccourcis = [
            'aujourdhui'     => [$aujourdhui, $aujourdhui],
            '7j'             => [$aujourdhui->copy()->subDays(6), $aujourdhui],
            'mois'           => [$aujourdhui->copy()->startOfMonth(), $aujourdhui],
            'mois_precedent' => [
                $moisPrecedent->copy()->startOfMonth(),
                $moisPrecedent->copy()->endOfMonth()->startOfDay(),
            ],
        ];

        if ($request->filled('from') || $request->filled('to')) {
            $preset = 'perso';
            $from = $this->date($request->query('from'), $aujourdhui->copy()->startOfMonth());
            $to   = $this->date($request->query('to'), $aujourdhui);
        } else {
            $preset = (string) $request->query('preset', 'mois');

            if (! isset($raccourcis[$preset])) {
                $preset = 'mois';
            }

            [$from, $to] = $raccourcis[$preset];
        }

        // Dates inversées : on les remet dans l'ordre
        if ($from->gt($to)) {
            [$from, $to] = [$to, $from];
        }

        // Limite de période pour éviter les requêtes trop lourdes
        if ($from->diffInDays($to) > self::PERIODE_MAX_JOURS) {
            $from = $to->copy()->subDays(self::PERIODE_MAX_JOURS);
        }

        return [$from->copy()->startOfDay(), $to->copy()->startOfDay(), $preset];
    }

    private function date(?string $valeur, Carbon $defaut): Carbon
    {
        try {
            $date = Carbon::createFromFormat('Y-m-d', (string) $valeur);

            return $date ? $date->startOfDay() : $defaut->copy();
        } catch (Throwable) {
            return $defaut->copy();
        }
    }

    private function onglet(Request $request): string
    {
        return $request->query('onglet') === 'commandes' ? 'commandes' : 'produits';
    }

    private function autoriser(): void
    {
        abort_unless(
            in_array(Auth::user()->role_id, [RoleEnum::ADMIN, RoleEnum::GERANT]),
            403
        );
    }

    private function companyId(): int
    {
        return (int) Auth::user()->id;
    }
}