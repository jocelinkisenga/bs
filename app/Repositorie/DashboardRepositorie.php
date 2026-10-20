<?php

namespace App\Repositorie;

use App\Models\Commande;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardRepositorie
{
    public function __construct(private int $companyId) {}

    /** Prix unitaire : snapshot de la commande si la colonne existe, sinon prix actuel. */
    private function expressionPrix(): string
    {
        return Schema::hasColumn((new Commande)->getTable(), 'prix_unitaire')
            ? 'COALESCE(c.prix_unitaire, pr.price, 0)'
            : 'COALESCE(pr.price, 0)';
    }

    /**
     * Une ligne par précommande créée sur la période (tous statuts) :
     * montant brut, remise validée, avance des dettes, nombre de lignes.
     */
    public function precommandes(Carbon $debut, Carbon $fin): Collection
    {
        $lignes = DB::table('commandes as c')
            ->leftJoin('produits as pr', 'pr.id', '=', 'c.produit_id')->groupBy('c.precommande_id')
            ->selectRaw('c.precommande_id,
                COUNT(*) AS nb_lignes,
                SUM(c.quantity_commande) AS articles,
                SUM(c.quantity_commande * ' . $this->expressionPrix() . ') AS brut');

        $remises = DB::table('reductions')
            ->where('reduit', true)
            ->whereNotNull('pourcentage')
            ->groupBy('precommande_id')
            ->selectRaw('precommande_id, MAX(pourcentage) AS pct');

        $dettes = DB::table('dettes')->groupBy('precommande_id')
            ->selectRaw('precommande_id, SUM(avance) AS avance, COUNT(*) AS nb_dettes');

        return DB::table('precommandes as p')
            ->leftJoinSub($lignes, 'l', 'l.precommande_id', '=', 'p.id')
            ->leftJoinSub($remises, 'r', 'r.precommande_id', '=', 'p.id')
            ->leftJoinSub($dettes, 'd', 'd.precommande_id', '=', 'p.id')->whereBetween('p.created_at', [$debut, $fin])
            ->get([
                'p.id', 'p.status', 'p.invoiced', 'p.created_at', 'p.server_id', 'p.table_id',
                DB::raw('COALESCE(l.nb_lignes, 0) AS nb_lignes'),
                DB::raw('COALESCE(l.articles, 0) AS articles'),
                DB::raw('COALESCE(l.brut, 0) AS brut'),
                DB::raw('COALESCE(r.pct, 0) AS remise_pct'),
                DB::raw('COALESCE(d.avance, 0) AS avance'),
                DB::raw('COALESCE(d.nb_dettes, 0) AS nb_dettes'),
            ]);
    }

    /** Quantités et CA brut par produit, sur les ventes confirmées de la période. */
    public function produitsVendus(Carbon $debut, Carbon $fin): Collection
    {
        return DB::table('commandes as c')
            ->join('precommandes as p', 'p.id', '=', 'c.precommande_id')
            ->join('produits as pr', 'pr.id', '=', 'c.produit_id')->where('p.status', true)
            ->whereBetween('p.created_at', [$debut, $fin])
            ->groupBy('pr.id', 'pr.name', 'pr.categorie_id')
            ->get([
                'pr.id', 'pr.name', 'pr.categorie_id',
                DB::raw('SUM(c.quantity_commande) AS qty'),
                DB::raw('SUM(c.quantity_commande * ' . $this->expressionPrix() . ') AS ca'),
            ]);
    }

    /** Prix d'achat moyen par produit, d'après l'historique d'approvisionnement. */
    public function coutsAchat(): Collection
    {
        return DB::table('hystory_products')->whereNotNull('prix_achat')
            ->groupBy('product_id')
            ->selectRaw('product_id, AVG(prix_achat) AS cout')
            ->pluck('cout', 'product_id');
    }

    public function depenses(Carbon $debut, Carbon $fin): Collection
    {
        return DB::table('depenses')->whereBetween('created_at', [$debut, $fin])
            ->get(['montant', 'created_at']);
    }

    /** Dettes des précommandes données, pour afficher les noms des clients. */
    public function dettes(array $precommandeIds): Collection
    {
        if ($precommandeIds === []) {
            return collect();
        }

        return DB::table('dettes')->whereIn('precommande_id', $precommandeIds)
            ->get(['precommande_id', 'client_name', 'client_phone', 'avance']);
    }

    /** Produits de l'entreprise, avec stock et prix actuels. */
    public function produits(): Collection
    {
        return DB::table('produits')->get(['id', 'name', 'quantity', 'price', 'categorie_id']);
    }

    /** Quantités vendues sur 30 jours, par produit (vitesse de vente). */
    public function quantitesVendues30j(): Collection
    {
        return DB::table('commandes as c')
            ->join('precommandes as p', 'p.id', '=', 'c.precommande_id')->where('p.status', true)
            ->where('c.created_at', '>=', now()->subDays(30))
            ->groupBy('c.produit_id')
            ->selectRaw('c.produit_id, SUM(c.quantity_commande) AS qty')
            ->pluck('qty', 'produit_id');
    }

    /** Tables avec leur nombre de commandes ouvertes. */
    public function tables(): Collection
    {
        return DB::table('tables as t')
            ->leftJoin('precommandes as p', function ($j) {
                $j->on('p.table_id', '=', 't.id')->where('p.status', '=', false);
            })->groupBy('t.id', 't.name', 't.places', 't.status')
            ->get([
                't.id', 't.name', 't.places', 't.status',
                DB::raw('COUNT(p.id) AS commandes_ouvertes'),
            ]);
    }

    public function commandesOuvertesAnciennes(int $heures = 2): int
    {
        return DB::table('precommandes')
            ->where('status', false)
            ->where('created_at', '<', now()->subHours($heures))
            ->count();
    }

    public function reductionsEnAttente(): int
    {
        return DB::table('reductions as r')
            ->join('precommandes as p', 'p.id', '=', 'r.precommande_id')->where('r.reduit', true)
            ->where('r.status', false)
            ->count();
    }
}