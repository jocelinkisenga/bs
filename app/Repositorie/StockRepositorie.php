<?php

namespace App\Repositorie;

use App\Models\Commande;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class StockRepositorie
{
    /** Bornes d'une période : du premier instant du jour de début au dernier instant du jour de fin. */
    private function bornes(Carbon $from, Carbon $to): array
    {
        return [$from->copy()->startOfDay(), $to->copy()->endOfDay()];
    }

    /** Prix unitaire : snapshot de la commande si la colonne existe, sinon prix actuel du produit. */
    private function expressionPrix(): string
    {
        return Schema::hasColumn((new Commande)->getTable(), 'prix_unitaire')
            ? 'COALESCE(c.prix_unitaire, pr.price, 0)'
            : 'COALESCE(pr.price, 0)';
    }

    /**
     * Mouvements de stock par produit sur la période.
     *
     * Stock de fin = stock actuel moins les mouvements postérieurs à la période.
     * Stock de début = stock de fin moins les entrées, plus les sorties de la période.
     */
    public function produits(int $companyId, Carbon $from, Carbon $to): Collection
    {
        [$debut, $fin] = $this->bornes($from, $to);
        $expr = $this->expressionPrix();

        $produits = DB::table('produits as pr')
            ->leftJoin('categories as ca', 'ca.id', '=', 'pr.categorie_id')->get(['pr.id', 'pr.name', 'pr.price', 'pr.quantity', 'ca.name as categorie']);

        // Entrées de la période (approvisionnements), avec prix d'achat moyen pondéré
        $entrees = DB::table('hystory_products')->whereBetween('created_at', [$debut, $fin])
            ->groupBy('product_id')
            ->selectRaw('product_id,
                SUM(new_quantity) AS entrees,
                SUM(new_quantity * prix_achat) / NULLIF(SUM(CASE WHEN prix_achat IS NOT NULL THEN new_quantity END), 0) AS achat_periode')
            ->get()
            ->keyBy('product_id');

        // Sorties de la période : toutes les lignes de commande (le stock est décrémenté à la saisie),
        // dont le détail des ventes confirmées (précommande au statut confirmé)
        $sorties = DB::table('commandes as c')
            ->join('precommandes as p', 'p.id', '=', 'c.precommande_id')
            ->join('produits as pr', 'pr.id', '=', 'c.produit_id')->whereBetween('c.created_at', [$debut, $fin])
            ->groupBy('c.produit_id')
            ->selectRaw("c.produit_id,
                SUM(c.quantity_commande) AS sorties,
                SUM(CASE WHEN p.status = 1 THEN c.quantity_commande ELSE 0 END) AS vendues,
                SUM(CASE WHEN p.status = 1 THEN c.quantity_commande * {$expr} ELSE 0 END) AS ca")
            ->get()
            ->keyBy('produit_id');

        // Mouvements postérieurs à la période, pour remonter au stock de fin
        $entreesApres = DB::table('hystory_products')->where('created_at', '>', $fin)
            ->groupBy('product_id')
            ->selectRaw('product_id, SUM(new_quantity) AS qte')
            ->pluck('qte', 'product_id');

        $sortiesApres = DB::table('commandes')->where('created_at', '>', $fin)
            ->groupBy('produit_id')
            ->selectRaw('produit_id, SUM(quantity_commande) AS qte')
            ->pluck('qte', 'produit_id');

        // Prix d'achat moyen historique, utilisé si le produit n'a pas eu d'entrée sur la période
        $achatGlobal = DB::table('hystory_products')->whereNotNull('prix_achat')
            ->groupBy('product_id')
            ->selectRaw('product_id, AVG(prix_achat) AS achat')
            ->pluck('achat', 'product_id');

        return $produits->map(function ($p) use ($entrees, $sorties, $entreesApres, $sortiesApres, $achatGlobal) {
            $e = $entrees->get($p->id);
            $s = $sorties->get($p->id);

            $qteEntrees = (float) ($e->entrees ?? 0);
            $qteSorties = (float) ($s->sorties ?? 0);
            $vendues    = (float) ($s->vendues ?? 0);
            $ca         = (float) ($s->ca ?? 0);

            $stockFin   = (float) $p->quantity
                - (float) $entreesApres->get($p->id, 0)
                + (float) $sortiesApres->get($p->id, 0);
            $stockDebut = $stockFin - $qteEntrees + $qteSorties;

            $achat = $e && $e->achat_periode !== null
                ? (float) $e->achat_periode
                : ($achatGlobal->get($p->id) !== null ? (float) $achatGlobal->get($p->id) : null);

            $cout = $achat !== null ? $vendues * $achat : null;

            return (object) [
                'id'          => $p->id,
                'nom'         => $p->name,
                'categorie'   => $p->categorie ?? '—',
                'prix_vente'  => (float) $p->price,
                'stock_debut' => $stockDebut,
                'entrees'     => $qteEntrees,
                'sorties'     => $qteSorties,
                'stock_fin'   => $stockFin,
                'vendues'     => $vendues,
                'ca'          => $ca,
                'prix_achat'  => $achat,
                'cout'        => $cout,
                'marge'       => $cout !== null ? $ca - $cout : null,
            ];
        })->sortByDesc('ca')->values();
    }

    /**
     * Précommandes créées sur la période, avec montants brut, remise et net.
     * Seules les réductions confirmées (status = true) sont appliquées.
     */
    public function commandes(int $companyId, Carbon $from, Carbon $to): Collection
    {
        [$debut, $fin] = $this->bornes($from, $to);
        $expr = $this->expressionPrix();

        $lignes = DB::table('commandes as c')
            ->join('produits as pr', 'pr.id', '=', 'c.produit_id')->groupBy('c.precommande_id')
            ->selectRaw("c.precommande_id,
                SUM(c.quantity_commande) AS quantite,
                SUM(c.quantity_commande * {$expr}) AS brut");

        $remises = DB::table('reductions')
            ->where('status', true)
            ->groupBy('precommande_id')
            ->selectRaw('precommande_id, MAX(pourcentage) AS pct');

        return DB::table('precommandes as p')
            ->leftJoinSub($lignes, 'l', 'l.precommande_id', '=', 'p.id')
            ->leftJoinSub($remises, 'r', 'r.precommande_id', '=', 'p.id')->whereBetween('p.created_at', [$debut, $fin])
            ->orderByDesc('p.created_at')
            ->get([
                'p.id',
                'p.code',
                'p.created_at',
                'p.status',
                'p.invoiced',
                DB::raw('COALESCE(l.quantite, 0) AS quantite'),
                DB::raw('COALESCE(l.brut, 0) AS brut'),
                DB::raw('COALESCE(r.pct, 0) AS remise_pct'),
            ])
            ->map(function ($p) {
                $brut   = (float) $p->brut;
                $pct    = (float) $p->remise_pct;
                $remise = $brut * $pct / 100;

                return (object) [
                    'id'        => $p->id,
                    'code'      => $p->code,
                    'date'      => Carbon::parse($p->created_at),
                    'confirmee' => (bool) $p->status,
                    'facturee'  => (bool) $p->invoiced,
                    'quantite'  => (int) $p->quantite,
                    'brut'      => $brut,
                    'pct'       => $pct,
                    'remise'    => $remise,
                    'net'       => $brut - $remise,
                ];
            });
    }

    /**
     * Indicateurs de synthèse. Les ventes ne comptent que pour les précommandes confirmées.
     */
    public function synthese(Collection $produits, Collection $commandes): array
    {
        $confirmees = $commandes->where('confirmee', true);
        $nbConfirmees = $confirmees->count();

        $brut   = (float) $confirmees->sum('brut');
        $remise = (float) $confirmees->sum('remise');
        $net    = (float) $confirmees->sum('net');
        $cout   = (float) $produits->sum(fn ($p) => (float) ($p->cout ?? 0));

        return [
            'commandes'    => $nbConfirmees,
            'en_cours'     => $commandes->where('confirmee', false)->count(),
            'articles'     => (int) $produits->sum('vendues'),
            'brut'         => $brut,
            'remises'      => $remise,
            'net'          => $net,
            'cout'         => $cout,
            'marge'        => $net - $cout,
            'taux_marge'   => $net > 0 ? ($net - $cout) / $net * 100 : null,
            'panier'       => $nbConfirmees > 0 ? $net / $nbConfirmees : 0.0,
            'entrees'      => (float) $produits->sum('entrees'),
            'sorties'      => (float) $produits->sum('sorties'),
            'valeur_stock' => (float) $produits->sum(fn ($p) => max(0, $p->stock_fin) * $p->prix_vente),
        ];
    }
}