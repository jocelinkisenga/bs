@php
    $fmtPct = fn ($v) => rtrim(rtrim(number_format((float) $v, 1, ',', ' '), '0'), ',');

    $badge = function ($v, bool $inverse = false) use ($fmtPct) {
        if ($v === null) {
            return ['cls' => 'neutral', 'icon' => 'bi-dash', 'txt' => '—'];
        }
        if ($v == 0) {
            return ['cls' => 'neutral', 'icon' => 'bi-dash', 'txt' => '0 %'];
        }
        $bon = $inverse ? $v < 0 : $v > 0;

        return [
            'cls'  => $bon ? 'up' : 'down',
            'icon' => $v > 0 ? 'bi-arrow-up-right' : 'bi-arrow-down-right',
            'txt'  => ($v > 0 ? '+' : '') . $fmtPct($v) . ' %',
        ];
    };
@endphp

<div class="page-wrapper dsh" id="dash-root">
    <div class="content">

        {{-- En-tête --}}
        <div class="dsh-header">
            <div>
                <h4 class="dsh-title">Tableau de bord</h4>
                <p class="dsh-sub">Évolution de votre activité, comparée à la période précédente</p>
            </div>

            <div class="dsh-periods" role="group" aria-label="Période">
                @foreach (['7j' => '7 jours', '30j' => '30 jours', '12m' => '12 mois'] as $valeur => $libelle)
                    <button type="button"
                        class="dsh-period {{ $periode === $valeur ? 'is-active' : '' }}"
                        wire:click="$set('periode', '{{ $valeur }}')">
                        {{ $libelle }}
                    </button>
                @endforeach
            </div>
        </div>

        {{-- Santé et recommandations --}}
        <section class="dsh-insights">
            <div class="dsh-health dsh-health--{{ $sante['niveau'] }}">
                <i class="bi bi-heart-pulse"></i>
                <div>
                    <span>Santé du business</span>
                    <strong>{{ $sante['label'] }}</strong>
                </div>
            </div>

            <div class="dsh-insight-list">
                @forelse ($insights as $i)
                    <div class="dsh-insight dsh-insight--{{ $i['niveau'] }}">
                        <i class="bi {{ $i['icone'] }}"></i>
                        <div>
                            <strong>{{ $i['titre'] }}</strong>
                            <p>{{ $i['texte'] }}</p>
                        </div>
                    </div>
                @empty
                    <div class="dsh-insight dsh-insight--success">
                        <i class="bi bi-check2-circle"></i>
                        <div>
                            <strong>Rien à signaler</strong>
                            <p>Aucun point d'attention sur la période.</p>
                        </div>
                    </div>
                @endforelse
            </div>
        </section>

        {{-- Indicateurs --}}
        <section class="dsh-kpis">
            @foreach ($cartes as $carte)
                <article class="dsh-kpi">
                    <div class="dsh-kpi__top">
                        <span class="dsh-kpi__icon"><i class="bi {{ $carte['icone'] }}"></i></span>

                        @if ($carte['variation'] !== false)
                            @php $b = $badge($carte['variation'], $carte['inverse']); @endphp
                            <span class="dsh-delta dsh-delta--{{ $b['cls'] }}">
                                <i class="bi {{ $b['icon'] }}"></i> {{ $b['txt'] }}
                            </span>
                        @endif
                    </div>

                    <span class="dsh-kpi__label">{{ $carte['label'] }}</span>
                    <strong class="dsh-kpi__value">
                        {{ $carte['valeur'] }}
                        @if ($carte['unite'] !== '')
                            <small>{{ $carte['unite'] }}</small>
                        @endif
                    </strong>
                    <span class="dsh-kpi__sub">{{ $carte['sous'] }}</span>
                </article>
            @endforeach
        </section>

        {{-- Graphiques --}}
        <section class="dsh-grid">

            <div class="dsh-panel dsh-panel--wide">
                <div class="dsh-panel__head">
                    <h5>Évolution : CA net et dépenses</h5>
                </div>
                <div class="dsh-chart" wire:ignore>
                    <canvas id="chart-evolution"></canvas>
                </div>
            </div>

            <div class="dsh-panel">
                <div class="dsh-panel__head">
                    <h5>Répartition par catégorie</h5>
                </div>
                <div class="dsh-chart dsh-chart--sm" wire:ignore>
                    <canvas id="chart-categories"></canvas>
                </div>
                @if (empty($charts['categories']['labels']))
                    <p class="dsh-empty">Aucune vente sur la période.</p>
                @endif
            </div>

            <div class="dsh-panel dsh-panel--half">
                <div class="dsh-panel__head">
                    <h5>Top 5 produits</h5>
                    <span class="dsh-muted">CA brut</span>
                </div>
                <div class="dsh-chart" wire:ignore>
                    <canvas id="chart-produits"></canvas>
                </div>
                @if (empty($charts['produits']['labels']))
                    <p class="dsh-empty">Aucun produit vendu sur la période.</p>
                @endif
            </div>

            <div class="dsh-panel dsh-panel--half">
                <div class="dsh-panel__head">
                    <h5>Heures de pointe</h5>
                    <span class="dsh-muted">Commandes par heure</span>
                </div>
                <div class="dsh-chart" wire:ignore>
                    <canvas id="chart-heures"></canvas>
                </div>
            </div>
        </section>

        {{-- Stock, opérations, personnel --}}
        <section class="dsh-grid">

            <div class="dsh-panel">
                <div class="dsh-panel__head">
                    <h5>Stock</h5>
                    <span class="dsh-muted">{{ $stock['nb_produits'] }} produit(s)</span>
                </div>
                <ul class="dsh-list">
                    <li>
                        <span>Valeur du stock (prix de vente)</span>
                        <strong>{{ number_format($stock['valeur'], 0, ',', ' ') }} Fc</strong>
                    </li>
                    <li>
                        <span>En rupture</span>
                        <strong class="dsh-text-danger">{{ $stock['ruptures']->count() }}</strong>
                    </li>
                    <li>
                        <span>Stock faible (moins de 5)</span>
                        <strong class="dsh-text-warning">{{ $stock['faibles']->count() }}</strong>
                    </li>
                    <li>
                        <span>Sans vente depuis 30 jours</span>
                        <strong>{{ $stock['dormants'] }}</strong>
                    </li>
                </ul>

                @if ($stock['ruptures']->isNotEmpty())
                    <div class="dsh-tags">
                        @foreach ($stock['ruptures']->take(8) as $nom)
                            <span class="dsh-tag dsh-tag--danger">{{ $nom }}</span>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="dsh-panel">
                <div class="dsh-panel__head">
                    <h5>Opérations</h5>
                </div>
                <ul class="dsh-list">
                    <li>
                        <span>Commandes en cours depuis plus de 2 h</span>
                        <strong class="{{ $enCours > 0 ? 'dsh-text-warning' : '' }}">{{ $enCours }}</strong>
                    </li>
                    <li>
                        <span>Réductions en attente</span>
                        <strong>{{ $reductions }}</strong>
                    </li>
                    <li>
                        <span>Concentration du top produit</span>
                        <strong>{{ $fmtPct($concentration * 100) }} %</strong>
                    </li>
                </ul>
                <div class="dsh-links">
                    <a href="{{ route('admin-commande') }}">Voir les commandes <i class="bi bi-arrow-right"></i></a>
                    <a href="{{ route('reductions') }}">Voir les réductions <i class="bi bi-arrow-right"></i></a>
                    <a href="{{ route('products') }}">Gérer les produits <i class="bi bi-arrow-right"></i></a>
                </div>
            </div>

            <div class="dsh-panel">
                <div class="dsh-panel__head">
                    <h5>Personnel</h5>
                    <span class="dsh-muted">{{ $personnel['total'] }} membre(s)</span>
                </div>
                <ul class="dsh-list">
                    <li>
                        <span>Gérants</span>
                        <strong>{{ $personnel['gerants'] }}</strong>
                    </li>
                    <li>
                        <span>Comptoiristes</span>
                        <strong>{{ $personnel['comptoiristes'] }}</strong>
                    </li>
                    <li>
                        <span>Serveurs</span>
                        <strong>{{ $personnel['serveurs'] }}</strong>
                    </li>
                </ul>
            </div>
        </section>

        {{-- Performance des serveurs --}}
        @if ($serveurs->isNotEmpty())
            <section class="dsh-grid">
                <div class="dsh-panel dsh-panel--full">
                    <div class="dsh-panel__head">
                        <h5>Performance des serveurs</h5>
                        <span class="dsh-muted">Sur la période</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table dsh-table">
                            <thead>
                                <tr>
                                    <th>Serveur</th>
                                    <th class="text-center">Commandes</th>
                                    <th class="text-end">CA net</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($serveurs as $s)
                                    <tr>
                                        <td class="fw-bold">{{ $s['nom'] }}</td>
                                        <td class="text-center">{{ $s['commandes'] }}</td>
                                        <td class="text-end">{{ number_format($s['ca'], 0, ',', ' ') }} Fc</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        @endif

        <p class="dsh-note">
            <i class="bi bi-info-circle"></i>
            Estimations : le CA est calculé avec le prix unitaire enregistré (ou le prix actuel du produit), net des réductions.
            Les coûts utilisent la moyenne des prix d'achat enregistrés. Seules les commandes confirmées sont comptées.
        </p>

        {{-- Données des graphiques : hors wire:ignore pour être mises à jour à chaque rendu --}}
        <div id="dash-data" data-charts="{{ json_encode($charts) }}" hidden></div>
    </div>

    <style>
        .dsh {
            --dsh-primary: #4f46e5;
            --dsh-primary-soft: #eef2ff;
            --dsh-ink: #0f172a;
            --dsh-ink-2: #475569;
            --dsh-ink-3: #94a3b8;
            --dsh-line: #e2e8f0;
            --dsh-up: #15803d;
            --dsh-up-soft: #dcfce7;
            --dsh-down: #dc2626;
            --dsh-down-soft: #fee2e2;
            --dsh-warn: #b45309;
            --dsh-warn-soft: #fef3c7;
            --dsh-info: #1d4ed8;
            --dsh-info-soft: #dbeafe;

            font-family: "Nunito", system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            color: var(--dsh-ink);
        }

        /* En-tête */
        .dsh-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1.25rem;
        }
        .dsh-title { margin: 0; font-size: 1.5rem; font-weight: 800; letter-spacing: -.02em; }
        .dsh-sub { margin: .15rem 0 0; color: var(--dsh-ink-2); font-size: .9rem; }

        .dsh-periods {
            display: inline-flex;
            padding: 4px;
            background: #fff;
            border: 1px solid var(--dsh-line);
            border-radius: 12px;
        }
        .dsh-period {
            padding: .45rem .9rem;
            border: 0;
            border-radius: 9px;
            background: transparent;
            color: var(--dsh-ink-2);
            font-weight: 700;
        }
        .dsh-period.is-active {
            background: var(--dsh-primary);
            color: #fff;
            box-shadow: 0 6px 14px rgba(79, 70, 229, .25);
        }

        /* Santé et recommandations */
        .dsh-insights {
            display: grid;
            grid-template-columns: 240px 1fr;
            gap: 1rem;
            margin-bottom: 1.25rem;
        }
        .dsh-health {
            display: flex;
            align-items: center;
            gap: .85rem;
            padding: 1.1rem 1.2rem;
            border-radius: 16px;
            border: 1px solid var(--dsh-line);
            background: #fff;
        }
        .dsh-health i {
            display: grid;
            place-items: center;
            width: 46px;
            height: 46px;
            border-radius: 14px;
            font-size: 1.3rem;
        }
        .dsh-health span { display: block; color: var(--dsh-ink-3); font-size: .72rem; font-weight: 800; letter-spacing: .05em; text-transform: uppercase; }
        .dsh-health strong { font-size: 1.05rem; font-weight: 800; }
        .dsh-health--success i { background: var(--dsh-up-soft); color: var(--dsh-up); }
        .dsh-health--warning i { background: var(--dsh-warn-soft); color: var(--dsh-warn); }
        .dsh-health--danger i  { background: var(--dsh-down-soft); color: var(--dsh-down); }

        .dsh-insight-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: .6rem;
        }
        .dsh-insight {
            display: flex;
            gap: .7rem;
            padding: .8rem .95rem;
            border-radius: 14px;
            border: 1px solid var(--dsh-line);
            background: #fff;
        }
        .dsh-insight > i { font-size: 1.15rem; margin-top: .1rem; }
        .dsh-insight strong { display: block; font-size: .9rem; font-weight: 800; }
        .dsh-insight p { margin: .15rem 0 0; color: var(--dsh-ink-2); font-size: .84rem; line-height: 1.45; }
        .dsh-insight--danger  { background: var(--dsh-down-soft); border-color: transparent; color: var(--dsh-down); }
        .dsh-insight--warning { background: var(--dsh-warn-soft); border-color: transparent; color: var(--dsh-warn); }
        .dsh-insight--info    { background: var(--dsh-info-soft); border-color: transparent; color: var(--dsh-info); }
        .dsh-insight--success { background: var(--dsh-up-soft);   border-color: transparent; color: var(--dsh-up); }
        .dsh-insight--danger p,
        .dsh-insight--warning p,
        .dsh-insight--info p,
        .dsh-insight--success p { color: var(--dsh-ink); opacity: .8; }

        /* Indicateurs */
        .dsh-kpis {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
            gap: .85rem;
            margin-bottom: 1rem;
        }
        .dsh-kpi {
            display: flex;
            flex-direction: column;
            gap: .25rem;
            padding: 1.1rem 1.15rem;
            background: #fff;
            border: 1px solid var(--dsh-line);
            border-radius: 16px;
            box-shadow: 0 1px 2px rgba(15, 23, 42, .05);
        }
        .dsh-kpi__top { display: flex; align-items: center; justify-content: space-between; margin-bottom: .4rem; }
        .dsh-kpi__icon {
            display: grid;
            place-items: center;
            width: 40px;
            height: 40px;
            border-radius: 12px;
            background: var(--dsh-primary-soft);
            color: var(--dsh-primary);
            font-size: 1.1rem;
        }
        .dsh-kpi__label { color: var(--dsh-ink-3); font-size: .74rem; font-weight: 800; letter-spacing: .05em; text-transform: uppercase; }
        .dsh-kpi__value { font-size: 1.5rem; font-weight: 800; font-variant-numeric: tabular-nums; letter-spacing: -.01em; }
        .dsh-kpi__value small { font-size: .8rem; color: var(--dsh-ink-3); }
        .dsh-kpi__sub { color: var(--dsh-ink-2); font-size: .8rem; }

        .dsh-delta {
            display: inline-flex;
            align-items: center;
            gap: .25rem;
            padding: .2rem .55rem;
            border-radius: 999px;
            font-size: .78rem;
            font-weight: 800;
        }
        .dsh-delta--up      { background: var(--dsh-up-soft);   color: var(--dsh-up); }
        .dsh-delta--down    { background: var(--dsh-down-soft); color: var(--dsh-down); }
        .dsh-delta--neutral { background: #f1f5f9; color: var(--dsh-ink-2); }

        /* Grille et panneaux */
        .dsh-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        .dsh-panel {
            min-width: 0;
            padding: 1.2rem 1.25rem;
            background: #fff;
            border: 1px solid var(--dsh-line);
            border-radius: 16px;
            box-shadow: 0 1px 2px rgba(15, 23, 42, .05);
        }
        .dsh-panel__head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: .5rem;
            margin-bottom: .9rem;
        }
        .dsh-panel__head h5 { margin: 0; font-size: .98rem; font-weight: 800; }
        .dsh-muted { color: var(--dsh-ink-3); font-size: .82rem; font-weight: 700; }
        .dsh-empty { margin: .6rem 0 0; text-align: center; color: var(--dsh-ink-3); font-weight: 600; }

        .dsh-chart { position: relative; height: 290px; }
        .dsh-chart--sm { height: 260px; }

        .dsh-list { list-style: none; margin: 0; padding: 0; }
        .dsh-list li {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: .6rem 0;
            border-bottom: 1px solid #f1f5f9;
            font-size: .92rem;
        }
        .dsh-list li:last-child { border-bottom: 0; }
        .dsh-list strong { font-variant-numeric: tabular-nums; font-weight: 800; }
        .dsh-text-danger  { color: var(--dsh-down) !important; }
        .dsh-text-warning { color: var(--dsh-warn) !important; }

        .dsh-tags { display: flex; flex-wrap: wrap; gap: .35rem; margin-top: .9rem; }
        .dsh-tag {
            padding: .2rem .6rem;
            border-radius: 999px;
            font-size: .76rem;
            font-weight: 700;
        }
        .dsh-tag--danger { background: var(--dsh-down-soft); color: var(--dsh-down); }

        .dsh-links { display: flex; flex-direction: column; gap: .45rem; margin-top: 1rem; }
        .dsh-links a {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: .5rem .2rem;
            color: var(--dsh-primary);
            font-size: .88rem;
            font-weight: 700;
            text-decoration: none;
        }
        .dsh-links a:hover { color: #4338ca; }

        .dsh-table { margin: 0; }
        .dsh-table thead th {
            padding: .6rem .8rem;
            border-bottom: 1px solid var(--dsh-line);
            color: var(--dsh-ink-2);
            font-size: .72rem;
            font-weight: 800;
            letter-spacing: .06em;
            text-transform: uppercase;
        }
        .dsh-table tbody td {
            padding: .7rem .8rem;
            border-bottom: 1px solid #f1f5f9;
            font-variant-numeric: tabular-nums;
        }
        .dsh-table tbody tr:last-child td { border-bottom: 0; }

        .dsh-note {
            display: flex;
            gap: .5rem;
            margin: 1.25rem 0 0;
            color: var(--dsh-ink-3);
            font-size: .8rem;
            line-height: 1.5;
        }

        /* Largeurs sur grand écran */
        @media (min-width: 992px) {
            .dsh-grid { grid-template-columns: repeat(12, minmax(0, 1fr)); }
            .dsh-panel { grid-column: span 4; }
            .dsh-panel--wide { grid-column: span 8; }
            .dsh-panel--half { grid-column: span 6; }
            .dsh-panel--full { grid-column: span 12; }
        }

        @media (max-width: 991.98px) {
            .dsh-insights { grid-template-columns: 1fr; }
        }

        @media (max-width: 575.98px) {
            .dsh-header { align-items: stretch; }
            .dsh-periods { width: 100%; }
            .dsh-period { flex: 1 1 auto; }
            .dsh-kpi__value { font-size: 1.3rem; }
            .dsh-chart { height: 240px; }
        }
    </style>
</div>