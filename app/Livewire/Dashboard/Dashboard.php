<?php

namespace App\Livewire\Dashboard;

use App\Enums\RoleEnum;
use App\Models\Categorie;
use App\Models\Commande;
use App\Models\Depense;
use App\Models\HystoryProduct;
use App\Models\Precommande;
use App\Models\Produit;
use App\Models\Reduction;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;

class Dashboard extends Component
{
    public string $periode = '30j'; // 7j | 30j | 12m

    public function mount(): void
    {
        abort_unless(
            in_array(Auth::user()->role_id, [RoleEnum::ADMIN, RoleEnum::GERANT]),
            403
        );
    }

    public function updatedPeriode(): void
    {
        if (! in_array($this->periode, ['7j', '30j', '12m'], true)) {
            $this->periode = '30j';
        }
    }

    public function render()
    {
        Carbon::setLocale('fr');

        $cid   = $this->companyId();
        $plage = $this->plages();
        $couts = $this->coutsMoyens();

        $actuel = $this->collecter($plage['debut'], $plage['fin']);
        $passe  = $this->collecter($plage['debutPrec'], $plage['finPrec']);

        $kpi  = $this->indicateurs($actuel, $couts);
        $kpiP = $this->indicateurs($passe, $couts);

        $var = [];
        foreach (['ca', 'commandes', 'depenses', 'marge', 'resultat'] as $cle) {
            $var[$cle] = $this->variation($kpi[$cle], $kpiP[$cle]);
        }

        $mois  = $this->projectionMois();
        $stock = $this->stock();

        // Top produits et concentration des ventes
        $produitsTop   = $actuel['produits']->sortByDesc('ca')->take(5)->values();
        $totalBrut     = (float) $actuel['produits']->sum('ca');
        $concentration = $totalBrut > 0
            ? (float) ($produitsTop->first()?->ca ?? 0) / $totalBrut
            : 0.0;

        // Heures de pointe (nombre de commandes par heure)
        $heures = array_fill(0, 24, 0);
        foreach ($actuel['ventes'] as $v) {
            $heures[$v->date->hour]++;
        }

        // Répartition par catégorie
        $noms = Categorie::where('company_id', $cid)->pluck('name', 'id');
        $parCategorie = $actuel['produits']
            ->groupBy(fn ($l) => (int) ($l->categorie_id ?? 0))
            ->map(fn ($g) => (float) $g->sum('ca'))
            ->sortDesc()
            ->take(6);

        $charts = [
            'evolution' => $this->serie($plage['debut'], $plage['fin'], $actuel['ventes'], $actuel['depenses']),
            'categories' => [
                'labels' => $parCategorie->keys()->map(fn ($id) => $noms[$id] ?? 'Sans catégorie')->values()->all(),
                'values' => $parCategorie->values()->map(fn ($v) => round($v))->values()->all(),
            ],
            'produits' => [
                'labels' => $produitsTop->map(fn ($l) => $l->name ?? 'Produit supprimé')->values()->all(),
                'values' => $produitsTop->map(fn ($l) => round((float) $l->ca))->values()->all(),
            ],
            'heures' => [
                'labels' => array_map(fn ($h) => sprintf('%02dh', $h), range(0, 23)),
                'values' => $heures,
            ],
        ];

        // Opérations
        $enCours = Precommande::where('company_id', $cid)
            ->where('status', false)
            ->where('created_at', '<', now()->subHours(2))
            ->count();

        $reductions = Reduction::where('status', false)
            ->whereHas('precommande', fn ($q) => $q->where('company_id', $cid))
            ->count();

        // Personnel
        $roles = User::where('company_id', $cid)
            ->selectRaw('role_id, COUNT(*) AS n')
            ->groupBy('role_id')
            ->pluck('n', 'role_id');

        $personnel = [
            'total'         => (int) $roles->sum(),
            'gerants'       => (int) ($roles[RoleEnum::GERANT] ?? 0),
            'comptoiristes' => (int) ($roles[RoleEnum::RECEPTEUR] ?? 0),
            'serveurs'      => (int) ($roles[RoleEnum::SERVER] ?? 0),
        ];

        $serveurs = $this->topServeurs($actuel['ventes']);

        // Analyse intelligente
        $insights = $this->insights([
            'ca'            => $kpi['ca'],
            'commandes'     => $kpi['commandes'],
            'marge'         => $kpi['marge'],
            'depenses'      => $kpi['depenses'],
            'var_ca'        => $var['ca'],
            'ruptures'      => $stock['ruptures'],
            'faibles'       => $stock['faibles']->count(),
            'dormants'      => $stock['dormants'],
            'en_cours'      => $enCours,
            'reductions'    => $reductions,
            'heures'        => $heures,
            'concentration' => $concentration,
            'top_nom'       => $produitsTop->first()?->name,
        ]);
        $sante = $this->sante($insights);

        // Cartes d'indicateurs
        $cartes = [
            [
                'icone' => 'bi-cash-stack', 'label' => "Chiffre d'affaires net",
                'valeur' => $this->fmt($kpi['ca']), 'unite' => 'Fc',
                'variation' => $var['ca'], 'inverse' => false,
                'sous' => 'Brut : ' . $this->fmt($kpi['brut']) . ' Fc',
            ],
            [
                'icone' => 'bi-receipt', 'label' => 'Commandes',
                'valeur' => $this->fmt($kpi['commandes']), 'unite' => '',
                'variation' => $var['commandes'], 'inverse' => false,
                'sous' => $this->fmt($kpi['articles']) . ' articles · panier moyen ' . $this->fmt($kpi['panier']) . ' Fc',
            ],
            [
                'icone' => 'bi-wallet2', 'label' => 'Dépenses',
                'valeur' => $this->fmt($kpi['depenses']), 'unite' => 'Fc',
                'variation' => $var['depenses'], 'inverse' => true,
                'sous' => 'Une hausse est un point d\'attention',
            ],
            [
                'icone' => 'bi-graph-up', 'label' => 'Marge brute estimée',
                'valeur' => $this->fmt($kpi['marge']), 'unite' => 'Fc',
                'variation' => $var['marge'], 'inverse' => false,
                'sous' => 'Coût des produits : ' . $this->fmt($kpi['cout']) . ' Fc',
            ],
            [
                'icone' => 'bi-bank', 'label' => 'Résultat net estimé',
                'valeur' => $this->fmt($kpi['resultat']), 'unite' => 'Fc',
                'variation' => $var['resultat'], 'inverse' => false,
                'sous' => 'Marge brute après dépenses',
            ],
            [
                'icone' => 'bi-calendar-check', 'label' => 'Projection fin de mois',
                'valeur' => $this->fmt($mois['projection']), 'unite' => 'Fc',
                'variation' => false, 'inverse' => false,
                'sous' => 'Réalisé ce mois : ' . $this->fmt($mois['mtd']) . ' Fc',
            ],
        ];

        return view('livewire.dashboard.dashboard', [
            'cartes'        => $cartes,
            'insights'      => $insights,
            'sante'         => $sante,
            'charts'        => $charts,
            'stock'         => $stock,
            'personnel'     => $personnel,
            'serveurs'      => $serveurs,
            'enCours'       => $enCours,
            'reductions'    => $reductions,
            'produitsTop'   => $produitsTop,
            'concentration' => $concentration,
        ]);
    }

    /* ==========================================================
     |  Périodes
     ========================================================== */

    /** Période courante et période précédente de même durée. */
    private function plages(): array
    {
        $fin = now();

        if ($this->periode === '12m') {
            $debut = now()->subMonths(11)->startOfMonth();
        } else {
            $jours = $this->periode === '7j' ? 7 : 30;
            $debut = now()->subDays($jours - 1)->startOfDay();
        }

        $duree = (int) abs($debut->diffInSeconds($fin));

        return [
            'debut'     => $debut,
            'fin'       => $fin,
            'debutPrec' => $debut->copy()->subSeconds($duree),
            'finPrec'   => $debut->copy(),
        ];
    }

    /** Toutes les données brutes d'une période, une seule fois. */
    private function collecter(Carbon $debut, Carbon $fin): array
    {
        return [
            'ventes'   => $this->ventes($debut, $fin),
            'produits' => $this->produitsVendus($debut, $fin),
            'depenses' => $this->depenses($debut, $fin),
        ];
    }

    /* ==========================================================
     |  Requêtes
     ========================================================== */

    /**
     * Une ligne par précommande confirmée : montant brut, réduction, net.
     * Les réductions sont agrégées dans une sous-requête pour ne pas
     * multiplier les lignes de commande.
     */
    private function ventes(Carbon $debut, Carbon $fin): Collection
    {
        $c  = $this->t(Commande::class);
        $p  = $this->t(Precommande::class);
        $pr = $this->t(Produit::class);
        $r  = $this->t(Reduction::class);

        $reductions = DB::table($r)
            ->selectRaw('precommande_id, MAX(pourcentage) AS pct')
            ->groupBy('precommande_id');

        $select = 'p.id, p.created_at, COALESCE(r.pct, 0) AS pct,
                   SUM(c.quantity_commande * ' . $this->expressionPrix() . ') AS brut,
                   SUM(c.quantity_commande) AS articles';
        $groupes = ['p.id', 'p.created_at', 'r.pct'];

        if ($this->colonneUserExiste()) {
            $select .= ', p.user_id';
            $groupes[] = 'p.user_id';
        }

        return DB::table("$c as c")
            ->join("$p as p", 'p.id', '=', 'c.precommande_id')
            ->leftJoin("$pr as pr", 'pr.id', '=', 'c.produit_id')
            ->leftJoinSub($reductions, 'r', 'r.precommande_id', '=', 'p.id')
            ->where('c.company_id', $this->companyId())
            ->where('p.status', true)
            ->whereBetween('p.created_at', [$debut, $fin])
            ->groupBy($groupes)
            ->selectRaw($select)
            ->get()
            ->map(function ($l) {
                $brut = (float) $l->brut;
                $pct  = (float) $l->pct;

                return (object) [
                    'id'       => $l->id,
                    'date'     => Carbon::parse($l->created_at),
                    'brut'     => $brut,
                    'pct'      => $pct,
                    'net'      => $brut * (1 - $pct / 100),
                    'articles' => (int) $l->articles,
                    'user_id'  => $l->user_id ?? null,
                ];
            });
    }

    /** Ventes agrégées par produit sur la période. */
    private function produitsVendus(Carbon $debut, Carbon $fin): Collection
    {
        $c  = $this->t(Commande::class);
        $p  = $this->t(Precommande::class);
        $pr = $this->t(Produit::class);

        return DB::table("$c as c")
            ->join("$p as p", 'p.id', '=', 'c.precommande_id')
            ->leftJoin("$pr as pr", 'pr.id', '=', 'c.produit_id')
            ->where('c.company_id', $this->companyId())
            ->where('p.status', true)
            ->whereBetween('p.created_at', [$debut, $fin])
            ->whereNotNull('c.produit_id')
            ->groupBy('c.produit_id', 'pr.name', 'pr.categorie_id')
            ->selectRaw('c.produit_id, pr.name, pr.categorie_id,
                         SUM(c.quantity_commande) AS qte,
                         SUM(c.quantity_commande * ' . $this->expressionPrix() . ') AS ca')
            ->get();
    }

    private function depenses(Carbon $debut, Carbon $fin): Collection
    {
        return Depense::query()
            ->where('company_id', $this->companyId())
            ->whereBetween('created_at', [$debut, $fin])
            ->get(['montant', 'created_at']);
    }

    /** Coût d'achat moyen par produit, d'après l'historique d'approvisionnement. */
    private function coutsMoyens(): Collection
    {
        return DB::table($this->t(HystoryProduct::class))
            ->where('company_id', $this->companyId())
            ->whereNotNull('prix_achat')
            ->groupBy('product_id')
            ->selectRaw('product_id, AVG(prix_achat) AS cout')
            ->pluck('cout', 'product_id');
    }

    /* ==========================================================
     |  Indicateurs
     ========================================================== */

    private function indicateurs(array $d, Collection $couts): array
    {
        $ca        = (float) $d['ventes']->sum('net');
        $brut      = (float) $d['ventes']->sum('brut');
        $commandes = $d['ventes']->count();
        $articles  = (int) $d['ventes']->sum('articles');

        $cout = (float) $d['produits']->sum(
            fn ($l) => (float) $l->qte * (float) ($couts[$l->produit_id] ?? 0)
        );

        $depenses = (float) $d['depenses']->sum('montant');
        $marge    = $ca - $cout;

        return [
            'ca'        => $ca,
            'brut'      => $brut,
            'commandes' => $commandes,
            'articles'  => $articles,
            'panier'    => $commandes > 0 ? $ca / $commandes : 0.0,
            'cout'      => $cout,
            'depenses'  => $depenses,
            'marge'     => $marge,
            'resultat'  => $marge - $depenses,
        ];
    }

    /** Variation en pourcentage ; null si la période précédente est vide. */
    private function variation(float $actuel, float $precedent): ?float
    {
        if ($precedent == 0.0) {
            return null;
        }

        return round(($actuel - $precedent) / abs($precedent) * 100, 1);
    }

    /** Projection linéaire du chiffre d'affaires du mois en cours. */
    private function projectionMois(): array
    {
        $mtd     = (float) $this->ventes(now()->startOfMonth(), now())->sum('net');
        $ecoules = now()->day;

        return [
            'mtd'        => $mtd,
            'projection' => $ecoules > 0 ? $mtd / $ecoules * now()->daysInMonth : 0.0,
        ];
    }

    /** Série temporelle : CA net et dépenses, par jour ou par mois. */
    private function serie(Carbon $debut, Carbon $fin, Collection $ventes, Collection $depenses): array
    {
        $mensuel = $this->periode === '12m';

        $cle = fn (Carbon $d) => $mensuel ? $d->format('Y-m') : $d->format('Y-m-d');

        $debutPas = $mensuel ? $debut->copy()->startOfMonth() : $debut->copy()->startOfDay();
        $pas      = $mensuel ? '1 month' : '1 day';

        $ventesParCle = $ventes->groupBy(fn ($v) => $cle($v->date));
        $depParCle    = $depenses->groupBy(fn ($d) => $cle(Carbon::parse($d->created_at)));

        $labels = [];
        $ca     = [];
        $dep    = [];

        foreach (CarbonPeriod::create($debutPas, $pas, $fin) as $jour) {
            $k = $cle($jour);

            $labels[] = $mensuel ? $jour->translatedFormat('M Y') : $jour->format('d/m');
            $ca[]     = round((float) ($ventesParCle->get($k)?->sum('net') ?? 0));
            $dep[]    = round((float) ($depParCle->get($k)?->sum('montant') ?? 0));
        }

        return compact('labels', 'ca', 'dep');
    }

    /** Stock : ruptures, stock faible, produits dormants, valeur. */
    private function stock(): array
    {
        $cid  = $this->companyId();
        $base = Produit::query()->where('company_id', $cid);

        $vendusRecemment = DB::table($this->t(Commande::class))
            ->where('company_id', $cid)
            ->where('created_at', '>=', now()->subDays(30))
            ->whereNotNull('produit_id')
            ->select('produit_id');

        return [
            'ruptures'    => (clone $base)->where('quantity', '<=', 0)->orderBy('name')->pluck('name'),
            'faibles'     => (clone $base)->whereBetween('quantity', [1, 4])->orderBy('name')->pluck('name'),
            'dormants'    => (clone $base)->where('quantity', '>', 0)->whereNotIn('id', $vendusRecemment)->count(),
            'valeur'      => (float) (clone $base)->sum(DB::raw('quantity * price')),
            'nb_produits' => (clone $base)->count(),
        ];
    }

    /** Classement des serveurs, si les précommandes sont rattachées à un utilisateur. */
    private function topServeurs(Collection $ventes): Collection
    {
        if (! $this->colonneUserExiste()) {
            return collect();
        }

        $groupes = $ventes
            ->whereNotNull('user_id')
            ->groupBy('user_id')
            ->map(fn ($g) => [
                'ca'        => (float) $g->sum('net'),
                'commandes' => $g->count(),
            ])
            ->sortByDesc('ca')
            ->take(5);

        $noms = User::whereIn('id', $groupes->keys())->pluck('name', 'id');

        return $groupes->map(fn ($s, $id) => $s + ['nom' => $noms[$id] ?? '—']);
    }

    /* ==========================================================
     |  Analyse intelligente (règles métier)
     ========================================================== */

    private function insights(array $c): array
    {
        $out = [];
        $ajouter = function (string $niveau, string $icone, string $titre, string $texte) use (&$out) {
            $out[] = compact('niveau', 'icone', 'titre', 'texte');
        };

        if ($c['commandes'] === 0) {
            $ajouter('info', 'bi-hourglass-split', 'Pas encore de ventes',
                'Aucune commande confirmée sur la période. Les analyses s\'afficheront dès les premières ventes.');
        }

        if ($c['var_ca'] !== null) {
            if ($c['var_ca'] >= 10) {
                $ajouter('success', 'bi-graph-up-arrow', 'Chiffre d\'affaires en hausse',
                    '+' . $this->pct($c['var_ca']) . ' % par rapport à la période précédente.');
            } elseif ($c['var_ca'] <= -10) {
                $ajouter('warning', 'bi-graph-down-arrow', 'Chiffre d\'affaires en baisse',
                    $this->pct($c['var_ca']) . ' % par rapport à la période précédente. Vérifiez la disponibilité des produits et l\'affluence.');
            }
        }

        if ($c['ca'] > 0 && $c['marge'] < 0) {
            $ajouter('danger', 'bi-exclamation-triangle', 'Marge brute négative',
                'Le coût d\'achat des produits vendus dépasse le chiffre d\'affaires. Revoyez vos prix de vente ou vos prix d\'achat.');
        }

        if ($c['ca'] > 0 && ($c['depenses'] / $c['ca']) >= 0.4) {
            $ajouter('warning', 'bi-wallet2', 'Dépenses élevées',
                'Les dépenses représentent ' . $this->pct($c['depenses'] / $c['ca'] * 100) . ' % du CA (seuil indicatif : 40 %).');
        }

        if ($c['ruptures']->isNotEmpty()) {
            $ajouter('danger', 'bi-x-octagon', $c['ruptures']->count() . ' produit(s) en rupture',
                'Des ventes sont perdues. Réapprovisionnez en priorité : ' . $c['ruptures']->take(3)->implode(', ') . '.');
        }

        if ($c['faibles'] > 0) {
            $ajouter('warning', 'bi-battery-half', $c['faibles'] . ' produit(s) bientôt épuisé(s)',
                'Moins de 5 unités en stock.');
        }

        if ($c['dormants'] > 0) {
            $ajouter('info', 'bi-moon-stars', $c['dormants'] . ' produit(s) en stock sans vente depuis 30 jours',
                'Pensez à une promotion ou à retirer ces produits du catalogue pour libérer du stock.');
        }

        if ($c['en_cours'] > 0) {
            $ajouter('warning', 'bi-clock-history', $c['en_cours'] . ' commande(s) en cours depuis plus de 2 heures',
                'Vérifiez qu\'elles ne sont pas bloquées ou oubliées.');
        }

        if ($c['reductions'] > 0) {
            $ajouter('info', 'bi-percent', $c['reductions'] . ' réduction(s) en attente de validation',
                'Ces réductions ne sont pas encore appliquées aux commandes concernées.');
        }

        $max = max($c['heures']);
        if ($c['commandes'] >= 5 && $max > 0) {
            $heure = array_search($max, $c['heures'], true);
            $ajouter('info', 'bi-lightning-charge', 'Pic d\'activité',
                sprintf('Le plus d\'activité est vers %02dh (%d commande(s)). Renforcez l\'équipe à ce moment.', $heure, $max));
        }

        if ($c['top_nom'] !== null && $c['concentration'] >= 0.4) {
            $ajouter('info', 'bi-star', 'Produit phare',
                '« ' . $c['top_nom'] . ' » représente ' . $this->pct($c['concentration'] * 100) . ' % des ventes. Surveillez son stock en priorité.');
        }

        $ordre = ['danger' => 0, 'warning' => 1, 'info' => 2, 'success' => 3];
        usort($out, fn ($a, $b) => $ordre[$a['niveau']] <=> $ordre[$b['niveau']]);

        return $out;
    }

    private function sante(array $insights): array
    {
        $danger  = collect($insights)->where('niveau', 'danger')->count();
        $warning = collect($insights)->where('niveau', 'warning')->count();

        if ($danger > 0) {
            return ['label' => 'Attention requise', 'niveau' => 'danger'];
        }

        if ($warning >= 2) {
            return ['label' => 'À surveiller', 'niveau' => 'warning'];
        }

        return ['label' => 'En bonne santé', 'niveau' => 'success'];
    }

    /* ==========================================================
     |  Outils
     ========================================================== */

    /** Expression SQL du prix unitaire : snapshot de la commande si disponible, sinon prix actuel. */
    private function expressionPrix(): string
    {
        return Schema::hasColumn($this->t(Commande::class), 'prix_unitaire')
            ? 'COALESCE(c.prix_unitaire, pr.price, 0)'
            : 'COALESCE(pr.price, 0)';
    }

    private function colonneUserExiste(): bool
    {
        return Schema::hasColumn($this->t(Precommande::class), 'user_id');
    }

    private function t(string $modele): string
    {
        return (new $modele)->getTable();
    }

    private function fmt($n): string
    {
        return number_format((float) $n, 0, ',', ' ');
    }

    private function pct(float $v): string
    {
        return rtrim(rtrim(number_format($v, 1, ',', ' '), '0'), ',');
    }

    private function companyId(): int
    {
        return Auth::user()->id;
    }
}