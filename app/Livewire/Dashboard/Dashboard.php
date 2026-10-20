<?php

namespace App\Livewire\Dashboard;

use App\Enums\RoleEnum;
use App\Repositorie\DashboardRepositorie;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Dashboard extends Component
{
    public string $periode = '30j';

    private const PERIODES = ['jour', '7j', '30j', '12m'];

    private const JOURS = [
        1 => 'Lundi', 2 => 'Mardi', 3 => 'Mercredi', 4 => 'Jeudi',
        5 => 'Vendredi', 6 => 'Samedi', 7 => 'Dimanche',
    ];

    public function mount(): void
    {
        abort_unless(in_array(Auth::user()->role_id, [RoleEnum::ADMIN, RoleEnum::GERANT]), 403);
    }

    public function updatedPeriode(): void
    {
        if (! in_array($this->periode, self::PERIODES, true)) {
            $this->periode = '30j';
        }
    }

    public function render()
    {
        Carbon::setLocale('fr');

        $companyId = (int) Auth::user()->company_id;
        $repo = new DashboardRepositorie($companyId);

        [$debut, $fin, $debutPrec, $finPrec] = $this->plages();

        $actuel    = $this->analyser($repo, $debut, $fin);
        $precedent = $this->analyser($repo, $debutPrec, $finPrec);

        $variation  = $this->variation($actuel['kpi']['net'], $precedent['kpi']['net']);
        $stock      = $this->stock($repo);
        $tables     = $this->tables($repo);
        $ouvertes   = $repo->commandesOuvertesAnciennes(2);
        $reductions = $repo->reductionsEnAttente();
        $mois       = $this->projectionMois($repo);

        $produitsTop = $this->produitsTop($actuel);
        $partTop     = $this->partTop($produitsTop);

        $indicateurs = $this->indicateurs($actuel, $stock, $tables, $ouvertes, $reductions, $partTop, $variation);
        $diagnostic  = $this->diagnostic($indicateurs);

        $charts = [
            'evolution' => $this->serie($debut, $fin, $actuel),
            'jours'     => $this->parJourSemaine($actuel['ventes']),
            'produits'  => [
                'labels' => $produitsTop->take(5)->pluck('name')->all(),
                'values' => $produitsTop->take(5)->map(fn ($p) => round($p->ca))->values()->all(),
            ],
        ];

        return view('livewire.dashboard.dashboard', [
            'cartes'      => $this->cartes($actuel, $precedent, $mois),
            'indicateurs' => $indicateurs,
            'diagnostic'  => $diagnostic,
            'lectures'    => $this->lectures($actuel, $stock, $tables, $variation),
            'creances'    => $this->creancesARecouvrer($repo, $actuel),
            'serveurs'    => $this->serveurs($actuel, $companyId),
            'produitsTop' => $produitsTop->take(5),
            'stock'       => $stock,
            'tables'      => $tables,
            'ouvertes'    => $ouvertes,
            'reductions'  => $reductions,
            'mois'        => $mois,
            'charts'      => $charts,
            'funnel'      => $actuel['funnel'],
        ]);
    }

    /* ==========================================================
     |  Périodes
     ========================================================== */

    /** Période courante et période précédente de même durée. */
    private function plages(): array
    {
        $fin = now();

        $debut = match ($this->periode) {
            'jour'  => now()->startOfDay(),
            '7j'    => now()->subDays(6)->startOfDay(),
            '12m'   => now()->subMonths(11)->startOfMonth(),
            default => now()->subDays(29)->startOfDay(),
        };

        $duree = (int) abs($debut->diffInSeconds($fin));

        return [$debut, $fin, $debut->copy()->subSeconds($duree), $debut->copy()];
    }

    /* ==========================================================
     |  Analyse d'une période
     ========================================================== */

    private function analyser(DashboardRepositorie $repo, Carbon $debut, Carbon $fin): array
    {
        $toutes   = $repo->precommandes($debut, $fin);
        $ventes   = $toutes->filter(fn ($p) => (bool) $p->status)->map(fn ($p) => $this->vente($p))->values();
        $produits = $repo->produitsVendus($debut, $fin);
        $couts    = $repo->coutsAchat();
        $depRows  = $repo->depenses($debut, $fin);

        $cout = 0.0;
        $qtyTotale = 0.0;
        $qtyCouverte = 0.0;

        foreach ($produits as $p) {
            $qty = (float) $p->qty;
            $qtyTotale += $qty;

            if ($couts->has($p->id)) {
                $qtyCouverte += $qty;
                $cout += $qty * (float) $couts->get($p->id);
            }
        }

        $brut      = (float) $ventes->sum('brut');
        $remises   = (float) $ventes->sum('remise');
        $net       = (float) $ventes->sum('net');
        $creances  = (float) $ventes->sum('creance');
        $encaisse  = (float) $ventes->sum('encaisse');
        $tickets   = $ventes->count();
        $facturees = $ventes->where('facturee', true)->count();
        $depenses  = (float) $depRows->sum('montant');
        $marge     = $net - $cout;

        return [
            'kpi' => [
                'brut'             => $brut,
                'remises'          => $remises,
                'net'              => $net,
                'encaisse'         => $encaisse,
                'creances'         => $creances,
                'cout'             => $cout,
                'marge'            => $marge,
                'depenses'         => $depenses,
                'resultat'         => $marge - $depenses,
                'tickets'          => $tickets,
                'articles'         => (int) $ventes->sum('articles'),
                'panier'           => $tickets > 0 ? $net / $tickets : 0.0,
                'taux_remise'      => $brut > 0 ? $remises / $brut * 100 : 0.0,
                'taux_creance'     => $net > 0 ? $creances / $net * 100 : 0.0,
                'taux_facturation' => $tickets > 0 ? $facturees / $tickets * 100 : null,
                'couverture'       => $qtyTotale > 0 ? $qtyCouverte / $qtyTotale * 100 : null,
            ],
            'funnel'       => $this->funnel($toutes, $ventes),
            'toutes'       => $toutes,
            'ventes'       => $ventes,
            'produits'     => $produits,
            'depensesRows' => $depRows,
        ];
    }

    /** Normalise une précommande confirmée : brut, remise, net, encaissé et créance. */
    private function vente(object $p): object
    {
        $brut    = (float) $p->brut;
        $remise  = $brut * (float) $p->remise_pct / 100;
        $net     = $brut - $remise;
        $aDette  = (int) $p->nb_dettes > 0;
        $avance  = (float) $p->avance;

        return (object) [
            'id'        => $p->id,
            'date'      => Carbon::parse($p->created_at),
            'server_id' => $p->server_id,
            'table_id'  => $p->table_id,
            'brut'      => $brut,
            'remise'    => $remise,
            'net'       => $net,
            'articles'  => (int) $p->articles,
            'facturee'  => (bool) $p->invoiced,
            'dette'     => $aDette,
            'encaisse'  => $aDette ? min($net, $avance) : $net,
            'creance'   => $aDette ? max(0.0, $net - $avance) : 0.0,
        ];
    }

    /** Tunnel de la vente : de la création à la facturation. */
    private function funnel(Collection $toutes, Collection $ventes): array
    {
        return [
            'creees'        => $toutes->count(),
            'avec_lignes'   => $toutes->filter(fn ($p) => (int) $p->nb_lignes > 0)->count(),
            'confirmees'    => $ventes->count(),
            'facturees'     => $ventes->where('facturee', true)->count(),
            'non_facturees' => $ventes->where('facturee', false)->count(),
            'a_credit'      => $ventes->where('dette', true)->count(),
            'vides'         => $toutes->filter(fn ($p) => (int) $p->nb_lignes === 0)->count(),
            'en_cours'      => $toutes->filter(fn ($p) => ! (bool) $p->status)->count(),
        ];
    }

    /* ==========================================================
     |  Stock, tables, personnel, créances
     ========================================================== */

    private function stock(DashboardRepositorie $repo): array
    {
        $produits = $repo->produits();
        $qty30    = $repo->quantitesVendues30j();

        $ruptures = $produits->filter(fn ($p) => (int) $p->quantity <= 0);

        // Manque à gagner journalier : vitesse de vente sur 30 jours × prix de vente
        $manqueParJour = $ruptures->sum(function ($p) use ($qty30) {
            $vitesse = ((float) ($qty30->get($p->id) ?? 0)) / 30;
            return $vitesse * (float) $p->price;
        });

        return [
            'nb'          => $produits->count(),
            'ruptures'    => $ruptures->count(),
            'noms'        => $ruptures->pluck('name')->take(6)->values()->all(),
            'faibles'     => $produits->filter(fn ($p) => (int) $p->quantity > 0 && (int) $p->quantity < 5)->count(),
            'valeur'      => (float) $produits->sum(fn ($p) => (float) $p->quantity * (float) $p->price),
            'manque_jour' => (float) $manqueParJour,
        ];
    }

    private function tables(DashboardRepositorie $repo): array
    {
        $t = $repo->tables();

        $occupees = $t->filter(fn ($x) => (bool) $x->status);

        // Occupée sans commande ouverte, ou libre avec une commande ouverte
        $occupeeSansCommande = $occupees->filter(fn ($x) => (int) $x->commandes_ouvertes === 0)->count();
        $libreAvecCommande   = $t->filter(fn ($x) => ! (bool) $x->status && (int) $x->commandes_ouvertes > 0)->count();

        return [
            'total'           => $t->count(),
            'places'          => (int) $t->sum('places'),
            'occupees'        => $occupees->count(),
            'places_occupees' => (int) $occupees->sum('places'),
            'incoherences'    => $occupeeSansCommande + $libreAvecCommande,
        ];
    }

    /** Classement des serveurs (rattachement par server_id). */
    private function serveurs(array $a, int $companyId): Collection
    {
        $total = (float) $a['ventes']->sum('net');

        $groupes = $a['ventes']
            ->whereNotNull('server_id')
            ->groupBy('server_id')
            ->map(fn ($g) => ['ca' => (float) $g->sum('net'), 'tickets' => $g->count()])
            ->sortByDesc('ca')
            ->take(6);

        $noms = DB::table('users')
            ->where('company_id', $companyId)
            ->whereIn('id', $groupes->keys()->all())
            ->pluck('name', 'id');

        return $groupes->map(fn ($s, $id) => $s + [
            'nom'  => $noms->get($id, '—'),
            'part' => $total > 0 ? $s['ca'] / $total * 100 : 0.0,
        ]);
    }

    /** Principales créances de la période, avec le nom du client. */
    private function creancesARecouvrer(DashboardRepositorie $repo, array $a): Collection
    {
        $ventes = $a['ventes']
            ->filter(fn ($v) => $v->creance > 0)
            ->sortByDesc('creance')
            ->take(5)
            ->values();

        $dettes = $repo->dettes($ventes->pluck('id')->all())->keyBy('precommande_id');

        return $ventes->map(function ($v) use ($dettes) {
            $d = $dettes->get($v->id);

            return (object) [
                'id'        => $v->id,
                'client'    => $d->client_name ?? '—',
                'telephone' => $d->client_phone ?? '',
                'creance'   => $v->creance,
                'date'      => $v->date,
            ];
        });
    }

    /* ==========================================================
     |  Produits et séries
     ========================================================== */

    private function produitsTop(array $a): Collection
    {
        return $a['produits']
            ->map(fn ($p) => (object) [
                'name' => $p->name ?? 'Produit supprimé',
                'qty'  => (float) $p->qty,
                'ca'   => (float) $p->ca,
            ])
            ->sortByDesc('ca')
            ->values();
    }

    /** Part du CA brut du produit le plus vendu. */
    private function partTop(Collection $produits): ?float
    {
        $total = (float) $produits->sum('ca');

        return $total > 0 ? (float) $produits->first()->ca / $total * 100 : null;
    }

    /** Série CA net / créances / dépenses, par jour (12 mois : par mois). */
    private function serie(Carbon $debut, Carbon $fin, array $a): array
    {
        $mensuel = $this->periode === '12m';
        $cle = fn (Carbon $d) => $mensuel ? $d->format('Y-m') : $d->format('Y-m-d');

        $ventesParCle = $a['ventes']->groupBy(fn ($v) => $cle($v->date));
        $depParCle    = $a['depensesRows']->groupBy(fn ($d) => $cle(Carbon::parse($d->created_at)));

        $point = $mensuel ? $debut->copy()->startOfMonth() : $debut->copy()->startOfDay();
        $pas   = $mensuel ? '1 month' : '1 day';

        $labels = [];
        $net    = [];
        $cr     = [];
        $dep    = [];

        foreach (CarbonPeriod::create($point, $pas, $fin) as $jour) {
            $k = $cle($jour);
            $g = $ventesParCle->get($k);
            $d = $depParCle->get($k);

            $labels[] = $mensuel ? ucfirst($jour->translatedFormat('M Y')) : $jour->format('d/m');
            $net[]    = round((float) ($g ? $g->sum('net') : 0));
            $cr[]     = round((float) ($g ? $g->sum('creance') : 0));
            $dep[]    = round((float) ($d ? $d->sum('montant') : 0));
        }

        return compact('labels', 'net', 'cr', 'dep');
    }

    private function parJourSemaine(Collection $ventes): array
    {
        $total = array_fill(1, 7, 0.0);

        foreach ($ventes as $v) {
            $total[(int) $v->date->dayOfWeekIso] += $v->net;
        }

        return [
            'labels' => array_values(self::JOURS),
            'values' => array_map(fn ($x) => round($x), array_values($total)),
        ];
    }

    private function parHeure(Collection $ventes): array
    {
        $tickets = array_fill(0, 24, 0);

        foreach ($ventes as $v) {
            $tickets[(int) $v->date->format('G')]++;
        }

        return [
            'labels' => array_map(fn ($h) => sprintf('%02dh', $h), range(0, 23)),
            'values' => $tickets,
        ];
    }

    /** CA net du mois en cours, projeté sur le mois entier au rythme actuel. */
    private function projectionMois(DashboardRepositorie $repo): array
    {
        $mtd = (float) $repo->precommandes(now()->startOfMonth(), now())
            ->filter(fn ($p) => (bool) $p->status)
            ->map(fn ($p) => $this->vente($p))
            ->sum('net');

        $jours = now()->day;

        return [
            'mtd'        => $mtd,
            'projection' => $jours > 0 ? $mtd / $jours * now()->daysInMonth : 0.0,
        ];
    }

    /* ==========================================================
     |  Indicateurs de fonctionnement
     ========================================================== */

    private function indicateurs(array $a, array $stock, array $tables, int $ouvertes, int $reductions, ?float $partTop, ?float $variation): Collection
    {
        $k = $a['kpi'];
        $f = $a['funnel'];

        $tauxConfirmation = $f['creees'] > 0 ? $f['confirmees'] / $f['creees'] * 100 : null;
        $tauxMarge        = $k['net'] > 0 ? $k['marge'] / $k['net'] * 100 : null;

        return collect([
            $this->indicateur('Taux de confirmation', $tauxConfirmation, 'pct', 90, 75, true,
                'Part des précommandes créées qui deviennent des ventes encaissées.',
                'Relancez les commandes ouvertes et supprimez les précommandes abandonnées.'),

            $this->indicateur('Taux de facturation', $k['taux_facturation'], 'pct', 95, 80, true,
                "Ventes confirmées ayant une facture. Une vente sans facture n'est pas justifiée comptablement.",
                'Facturez les ventes confirmées restantes depuis la fiche de la commande.'),

            $this->indicateur('Taux de remise', $k['taux_remise'], 'pct', 8, 15, false,
                "Part du CA brut cédée en réductions. Une remise élevée érode la marge.",
                'Limitez les réductions aux cas justifiés et validez-les avant application.'),

            $this->indicateur('Créances clients', $k['taux_creance'], 'pct', 5, 15, false,
                "Part du CA net non encore encaissée. Ce montant reste à recouvrer.",
                'Relancez les clients listés dans « Créances à recouvrer ».'),

            $this->indicateur('Couverture des coûts', $k['couverture'], 'pct', 95, 70, true,
                "Part des quantités vendues dont le prix d'achat est connu. Sous ce seuil, la marge est peu fiable.",
                "Renseignez le prix d'achat à chaque approvisionnement."),

            $this->indicateur('Marge brute', $tauxMarge, 'pct', 30, 15, true,
                'Part du CA net qui reste après le coût d\'achat des produits vendus.',
                'Révisez les prix de vente des produits à faible marge.'),

            $this->indicateur('Précommandes vides', (float) $f['vides'], 'count', 0, 3, false,
                'Précommandes créées sans produit : commandes abandonnées ou erreur de saisie.',
                'Vérifiez avec les serveurs le processus de création des commandes.'),

            $this->indicateur('Produits en rupture', (float) $stock['ruptures'], 'count', 0, 2, false,
                'Produits à stock nul. Chaque jour de rupture coûte des ventes.',
                'Réapprovisionnez en priorité les produits listés dans « Stock ».'),

            $this->indicateur('Dépendance au produit phare', $partTop, 'pct', 25, 40, false,
                'Part du CA brut du produit le plus vendu. Une forte dépendance fragilise le chiffre d\'affaires.',
                'Développez les autres produits et sécurisez le stock du produit phare.'),

            $this->indicateur('Tendance du CA net', $variation, 'signed', 0, -10, true,
                'Évolution du CA net face à la période précédente de même durée.',
                'Analysez les jours et heures en baisse dans le graphique d\'évolution.'),

            $this->indicateur('Incohérences de tables', (float) $tables['incoherences'], 'count', 0, 2, false,
                'Tables occupées sans commande ouverte, ou commandes ouvertes sur des tables libres.',
                'Vérifiez les tables concernées : libérez-les ou rouvrez la commande.'),

            $this->indicateur('Commandes ouvertes depuis plus de 2 h', (float) $ouvertes, 'count', 0, 2, false,
                'Commandes non confirmées depuis longtemps : risque de ventes oubliées.',
                'Confirmez ou annulez ces commandes.'),

            $this->indicateur('Réductions en attente', (float) $reductions, 'count', 0, 3, false,
                'Réductions demandées mais non validées.',
                'Validez ou refusez les réductions en attente.'),
        ]);
    }

    private function indicateur(string $label, ?float $valeur, string $format, float $ok, float $alerte, bool $plusHaut, string $lecture, string $action): array
    {
        return [
            'label'   => $label,
            'valeur'  => $valeur,
            'format'  => $format,
            'statut'  => $this->statut($valeur, $ok, $alerte, $plusHaut),
            'cible'   => ($plusHaut ? '≥ ' : '≤ ') . $this->nombre($ok) . ($format === 'count' ? '' : ' %'),
            'lecture' => $lecture,
            'action'  => $action,
        ];
    }

    private function statut(?float $v, float $ok, float $alerte, bool $plusHaut): string
    {
        if ($v === null) {
            return 'neutral';
        }

        if ($plusHaut) {
            return $v >= $ok ? 'ok' : ($v >= $alerte ? 'watch' : 'bad');
        }

        return $v <= $ok ? 'ok' : ($v <= $alerte ? 'watch' : 'bad');
    }

    /** Score global : bon = 1 point, vigilance = 0,5 point, action = 0. */
    private function diagnostic(Collection $indicateurs): array
    {
        $mesures = $indicateurs->reject(fn ($i) => $i['statut'] === 'neutral');

        if ($mesures->isEmpty()) {
            return ['score' => null, 'label' => 'Données insuffisantes', 'niveau' => 'neutral', 'couleur' => '#94a3b8', 'mesures' => 0];
        }

        $points = $mesures->sum(fn ($i) => match ($i['statut']) {
            'ok'    => 1,
            'watch' => 0.5,
            default => 0,
        });

        $score  = (int) round($points / $mesures->count() * 100);
        $niveau = $score >= 80 ? 'ok' : ($score >= 50 ? 'watch' : 'bad');

        return [
            'score'   => $score,
            'label'   => $score >= 85 ? 'Excellent' : ($score >= 65 ? 'Bon' : ($score >= 40 ? 'À surveiller' : 'Critique')),
            'niveau'  => $niveau,
            'couleur' => ['ok' => '#15803d', 'watch' => '#b45309', 'bad' => '#dc2626'][$niveau],
            'mesures' => $mesures->count(),
        ];
    }

    /* ==========================================================
     |  Cartes financières et lectures
     ========================================================== */

    private function cartes(array $a, array $p, array $mois): array
    {
        $k  = $a['kpi'];
        $kp = $p['kpi'];
        $v  = fn (string $cle) => $this->variation($k[$cle], $kp[$cle]);

        return [
            ['label' => "Chiffre d'affaires brut", 'valeur' => $k['brut'], 'var' => $v('brut'), 'inverse' => false,
             'icone' => 'bi-cash-coin', 'principal' => false,
             'sous' => $k['tickets'] . ' ticket(s) · ' . $k['articles'] . ' article(s)'],

            ['label' => 'Remises accordées', 'valeur' => $k['remises'], 'var' => $v('remises'), 'inverse' => true,
             'icone' => 'bi-percent', 'principal' => false,
             'sous' => $this->pct($k['taux_remise']) . ' % du brut cédé'],

            ['label' => "Chiffre d'affaires net", 'valeur' => $k['net'], 'var' => $v('net'), 'inverse' => false,
             'icone' => 'bi-graph-up-arrow', 'principal' => true,
             'sous' => 'Panier moyen : ' . $this->fmt($k['panier']) . ' Fc'],

            ['label' => 'Encaissé estimé', 'valeur' => $k['encaisse'], 'var' => null, 'inverse' => false,
             'icone' => 'bi-bank', 'principal' => false,
             'sous' => 'Ce qui est réellement en caisse'],

            ['label' => 'Créances clients', 'valeur' => $k['creances'], 'var' => null, 'inverse' => true,
             'icone' => 'bi-person-exclamation', 'principal' => false,
             'sous' => $this->pct($k['taux_creance']) . ' % du net à recouvrer'],

            ['label' => 'Marge brute', 'valeur' => $k['marge'], 'var' => $v('marge'), 'inverse' => false,
             'icone' => 'bi-pie-chart', 'principal' => false,
             'sous' => "Coût d'achat : " . $this->fmt($k['cout']) . ' Fc'],

            ['label' => 'Résultat net estimé', 'valeur' => $k['resultat'], 'var' => $v('resultat'), 'inverse' => false,
             'icone' => 'bi-clipboard-data', 'principal' => true,
             'sous' => 'Après ' . $this->fmt($k['depenses']) . ' Fc de dépenses'],

            ['label' => 'Projection fin de mois', 'valeur' => $mois['projection'], 'var' => null, 'inverse' => false,
             'icone' => 'bi-calendar-check', 'principal' => false,
             'sous' => 'Réalisé ce mois : ' . $this->fmt($mois['mtd']) . ' Fc'],
        ];
    }

    /** Phrases d'explication, rédigées à partir des chiffres de la période. */
    private function lectures(array $a, array $stock, array $tables, ?float $variation): array
    {
        $k = $a['kpi'];
        $out = [];

        if ($k['net'] > 0) {
            $out[] = 'Sur 100 Fc de CA net, ' . $this->fmt($k['marge'] / $k['net'] * 100)
                . " Fc subsistent après le coût d'achat des produits.";
        }

        $out[] = $k['resultat'] >= 0
            ? 'Résultat net estimé positif : ' . $this->fmt($k['resultat']) . ' Fc après ' . $this->fmt($k['depenses']) . ' Fc de dépenses.'
            : 'Résultat net estimé négatif de ' . $this->fmt(abs($k['resultat'])) . " Fc : coûts et dépenses dépassent les ventes.";

        if ($k['creances'] > 0) {
            $out[] = $this->fmt($k['creances']) . " Fc de ventes sont à crédit : cet argent n'est pas encore en caisse.";
        }

        if ($variation !== null) {
            $out[] = $variation >= 0
                ? 'Le CA net progresse de ' . $this->pct($variation) . ' % par rapport à la période précédente.'
                : 'Le CA net recule de ' . $this->pct(abs($variation)) . ' % par rapport à la période précédente.';
        }

        $jours = $this->parJourSemaine($a['ventes']);
        if (max($jours['values']) > 0) {
            $i = array_search(max($jours['values']), $jours['values'], true);
            $out[] = 'Le meilleur jour est le ' . $jours['labels'][$i] . ' avec ' . $this->fmt($jours['values'][$i]) . ' Fc de CA net.';
        }

        $heures = $this->parHeure($a['ventes']);
        if ($a['ventes']->count() >= 5 && max($heures['values']) > 0) {
            $h = array_search(max($heures['values']), $heures['values'], true);
            $out[] = "Le pic d'activité se situe vers " . sprintf('%02dh', $h)
                . ' (' . max($heures['values']) . " ticket(s)). Renforcez l'équipe à ce moment.";
        }

        if ($stock['manque_jour'] > 0) {
            $out[] = 'Les ruptures actuelles coûtent environ ' . $this->fmt($stock['manque_jour'])
                . ' Fc de ventes par jour, au rythme des 30 derniers jours.';
        }

        if ($tables['incoherences'] > 0) {
            $out[] = $tables['incoherences'] . " table(s) ont un état incohérent avec leurs commandes ouvertes : le plan de salle n'est pas fiable.";
        }

        return $out;
    }

    /* ==========================================================
     |  Utilitaires
     ========================================================== */

    private function variation(float $actuel, float $precedent): ?float
    {
        if ($precedent == 0.0) {
            return null;
        }

        return round(($actuel - $precedent) / abs($precedent) * 100, 1);
    }

    private function fmt($n): string
    {
        return number_format((float) $n, 0, ',', ' ');
    }

    private function pct($n): string
    {
        return rtrim(rtrim(number_format((float) $n, 1, ',', ' '), '0'), ',');
    }

    private function nombre(float $n): string
    {
        return number_format($n, 0, ',', ' ');
    }
}