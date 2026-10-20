@php
    $fmt = fn ($n) => number_format((float) $n, 0, ',', ' ');
    $pct = fn ($n) => rtrim(rtrim(number_format((float) $n, 1, ',', ' '), '0'), ',');

    $aff = function (array $i) use ($fmt, $pct) {
        if ($i['valeur'] === null) {
            return '—';
        }

        return match ($i['format']) {
            'pct'    => $pct($i['valeur']) . ' %',
            'signed' => ($i['valeur'] > 0 ? '+' : '') . $pct($i['valeur']) . ' %',
            default  => $fmt($i['valeur']),
        };
    };

    $libStatut = ['ok' => 'Bon', 'watch' => 'Vigilance', 'bad' => 'Action', 'neutral' => 'Sans donnée'];
    $periodes  = ['jour' => "Aujourd'hui", '7j' => '7 jours', '30j' => '30 jours', '12m' => '12 mois'];

    $base = max(1, $funnel['creees']);
    $etapes = [
        ['Précommandes créées',      $funnel['creees']],
        ['Avec au moins un produit', $funnel['avec_lignes']],
        ['Ventes confirmées',        $funnel['confirmees']],
        ['Facturées',                $funnel['facturees']],
        ['Vendues à crédit',         $funnel['a_credit']],
    ];

    $priorites = $indicateurs->where('statut', 'bad')->values();
    $surveiller = $indicateurs->where('statut', 'watch')->values();
@endphp

<div class="page-wrapper rv" id="rv-root">
    <div class="content">

        {{-- En-tête --}}
        <div class="rv-header">
            <div>
                <h4 class="rv-title">Tableau de bord financier</h4>
                <p class="rv-sub">Chiffre d'affaires, encaissements, marge et fonctionnement de la plateforme</p>
            </div>

            <div class="rv-periods" role="group" aria-label="Période">
                @foreach ($periodes as $valeur => $libelle)
                    <button type="button"
                        class="rv-period {{ $periode === $valeur ? 'is-active' : '' }}"
                        wire:click="$set('periode', '{{ $valeur }}')">
                        {{ $libelle }}
                    </button>
                @endforeach
            </div>
        </div>

        {{-- Diagnostic et actions --}}
        <section class="rv-diag">
            <div class="rv-score"
                style="--score: {{ $diagnostic['score'] ?? 0 }}; --c: {{ $diagnostic['couleur'] }};">
                <div class="rv-score__inner">{{ $diagnostic['score'] ?? '—' }}</div>
            </div>

            <div class="rv-diag__text">
                <span>Santé de la plateforme</span>
                <strong>{{ $diagnostic['label'] }}</strong>
                <p>Score calculé sur {{ $diagnostic['mesures'] }} indicateur(s) de fonctionnement.</p>
            </div>

            <div class="rv-diag__actions">
                <h6>Actions prioritaires</h6>
                @forelse ($priorites as $i)
                    <div class="rv-action rv-action--bad">
                        <i class="bi bi-exclamation-circle"></i>
                        <div><strong>{{ $i['label'] }}</strong><p>{{ $i['action'] }}</p></div>
                    </div>
                @empty
                    <p class="rv-muted">Aucune action urgente sur la période.</p>
                @endforelse

                @foreach ($surveiller->take(3) as $i)
                    <div class="rv-action rv-action--watch">
                        <i class="bi bi-eye"></i>
                        <div><strong>{{ $i['label'] }}</strong><p>{{ $i['action'] }}</p></div>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- Indicateurs financiers --}}
        <section class="rv-kpis">
            @foreach ($cartes as $c)
                <article class="rv-kpi {{ $c['principal'] ? 'is-principal' : '' }}">
                    <div class="rv-kpi__top">
                        <span class="rv-kpi__icon"><i class="bi {{ $c['icone'] }}"></i></span>

                        @if ($c['var'] !== null)
                            @php
                                $bon = $c['inverse'] ? $c['var'] < 0 : $c['var'] > 0;
                                $neutre = $c['var'] == 0;
                            @endphp
                            <span class="rv-delta {{ $neutre ? 'is-neutral' : ($bon ? 'is-up' : 'is-down') }}">
                                <i class="bi {{ $c['var'] >= 0 ? 'bi-arrow-up-right' : 'bi-arrow-down-right' }}"></i>
                                {{ $c['var'] > 0 ? '+' : '' }}{{ $pct($c['var']) }} %
                            </span>
                        @endif
                    </div>

                    <span class="rv-kpi__label">{{ $c['label'] }}</span>
                    <strong class="rv-kpi__value">{{ $fmt($c['valeur']) }} <small>Fc</small></strong>
                    <span class="rv-kpi__sub">{{ $c['sous'] }}</span>
                </article>
            @endforeach
        </section>

        {{-- Lectures financières --}}
        <section class="rv-panel rv-panel--lecture">
            <div class="rv-panel__head"><h5>Lecture financière de la période</h5></div>
            <ul class="rv-lectures">
                @foreach ($lectures as $l)
                    <li>{{ $l }}</li>
                @endforeach
            </ul>
        </section>

        {{-- Graphiques --}}
        <section class="rv-grid">
            <div class="rv-panel rv-panel--wide">
                <div class="rv-panel__head">
                    <h5>Évolution : CA net, créances et dépenses</h5>
                </div>
                <div class="rv-chart" wire:ignore>
                    <canvas id="rv-evolution"></canvas>
                </div>
            </div>

            <div class="rv-panel">
                <div class="rv-panel__head"><h5>CA net par jour de semaine</h5></div>
                <div class="rv-chart rv-chart--sm" wire:ignore>
                    <canvas id="rv-jours"></canvas>
                </div>
            </div>

            <div class="rv-panel rv-panel--half">
                <div class="rv-panel__head">
                    <h5>Top 5 produits</h5>
                    <span class="rv-muted">CA brut</span>
                </div>
                <div class="rv-chart" wire:ignore>
                    <canvas id="rv-produits"></canvas>
                </div>
                @if ($produitsTop->isEmpty())
                    <p class="rv-empty">Aucun produit vendu sur la période.</p>
                @endif
            </div>

            {{-- Fonctionnement : tunnel de vente --}}
            <div class="rv-panel rv-panel--half">
                <div class="rv-panel__head">
                    <h5>Tunnel de vente</h5>
                    <span class="rv-muted">Comment les commandes avancent</span>
                </div>

                @foreach ($etapes as [$nom, $val])
                    <div class="rv-step">
                        <span class="rv-step__name">{{ $nom }}</span>
                        <div class="rv-step__bar"><span style="width: {{ $val / $base * 100 }}%"></span></div>
                        <strong>{{ $val }}</strong>
                    </div>
                @endforeach

                <p class="rv-note">
                    Taux de confirmation : {{ $funnel['creees'] > 0 ? $pct($funnel['confirmees'] / $funnel['creees'] * 100) : '—' }} %.
                    Ventes non facturées : {{ $funnel['non_facturees'] }}.
                    Précommandes vides : {{ $funnel['vides'] }}.
                </p>
            </div>
        </section>

        {{-- Indicateurs de fonctionnement --}}
        <section class="rv-panel">
            <div class="rv-panel__head">
                <h5>Indicateurs de fonctionnement</h5>
                <span class="rv-muted">Valeur mesurée face à la cible</span>
            </div>

            <div class="table-responsive">
                <table class="table rv-table">
                    <thead>
                        <tr>
                            <th>Indicateur</th>
                            <th class="text-end">Valeur</th>
                            <th class="text-end">Cible</th>
                            <th>Statut</th>
                            <th>Lecture</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($indicateurs as $i)
                            <tr>
                                <td class="fw-bold">{{ $i['label'] }}</td>
                                <td class="text-end fw-bold">{{ $aff($i) }}</td>
                                <td class="text-end rv-muted">{{ $i['cible'] }}</td>
                                <td><span class="rv-badge rv-badge--{{ $i['statut'] }}">{{ $libStatut[$i['statut']] }}</span></td>
                                <td class="rv-muted">{{ $i['lecture'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        {{-- Situation en temps réel, créances, serveurs --}}
        <section class="rv-grid">

            <div class="rv-panel">
                <div class="rv-panel__head"><h5>Salle et opérations</h5></div>
                <ul class="rv-list">
                    <li><span>Tables occupées</span><strong>{{ $tables['occupees'] }} / {{ $tables['total'] }}</strong></li>
                    <li><span>Places occupées</span><strong>{{ $tables['places_occupees'] }} / {{ $tables['places'] }}</strong></li>
                    <li><span>Commandes ouvertes depuis plus de 2 h</span>
                        <strong class="{{ $ouvertes > 0 ? 'rv-text-warn' : '' }}">{{ $ouvertes }}</strong></li>
                    <li><span>Réductions en attente</span><strong>{{ $reductions }}</strong></li>
                    <li><span>Incohérences de tables</span>
                        <strong class="{{ $tables['incoherences'] > 0 ? 'rv-text-bad' : '' }}">{{ $tables['incoherences'] }}</strong></li>
                </ul>
            </div>

            <div class="rv-panel">
                <div class="rv-panel__head">
                    <h5>Stock</h5>
                    <span class="rv-muted">{{ $stock['nb'] }} produit(s)</span>
                </div>
                <ul class="rv-list">
                    <li><span>Valeur du stock (prix de vente)</span><strong>{{ $fmt($stock['valeur']) }} Fc</strong></li>
                    <li><span>En rupture</span>
                        <strong class="{{ $stock['ruptures'] > 0 ? 'rv-text-bad' : '' }}">{{ $stock['ruptures'] }}</strong></li>
                    <li><span>Stock faible (moins de 5)</span><strong>{{ $stock['faibles'] }}</strong></li>
                    <li><span>Manque à gagner estimé par jour</span>
                        <strong class="{{ $stock['manque_jour'] > 0 ? 'rv-text-bad' : '' }}">{{ $fmt($stock['manque_jour']) }} Fc</strong></li>
                </ul>
                @if (! empty($stock['noms']))
                    <div class="rv-tags">
                        @foreach ($stock['noms'] as $nom)
                            <span class="rv-tag">{{ $nom }}</span>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="rv-panel rv-panel--wide">
                <div class="rv-panel__head">
                    <h5>Créances à recouvrer</h5>
                    <span class="rv-muted">Principales ventes à crédit de la période</span>
                </div>
                <div class="table-responsive">
                    <table class="table rv-table">
                        <thead>
                            <tr>
                                <th>Client</th>
                                <th>Téléphone</th>
                                <th>Date</th>
                                <th class="text-end">Reste à payer</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($creances as $c)
                                <tr>
                                    <td class="fw-bold">{{ $c->client }}</td>
                                    <td class="rv-muted">{{ $c->telephone ?: '—' }}</td>
                                    <td class="rv-muted">{{ $c->date->format('d/m/Y') }}</td>
                                    <td class="text-end fw-bold">{{ $fmt($c->creance) }} Fc</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="rv-empty">Aucune créance sur la période.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="rv-panel rv-panel--full">
                <div class="rv-panel__head">
                    <h5>Performance des serveurs</h5>
                    <span class="rv-muted">Sur la période</span>
                </div>
                @if ($serveurs->isEmpty())
                    <p class="rv-empty">Aucune vente rattachée à un serveur sur la période.</p>
                @else
                    <div class="table-responsive">
                        <table class="table rv-table">
                            <thead>
                                <tr>
                                    <th>Serveur</th>
                                    <th class="text-center">Tickets</th>
                                    <th class="text-end">CA net</th>
                                    <th class="text-end">Part</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($serveurs as $s)
                                    <tr>
                                        <td class="fw-bold">{{ $s['nom'] }}</td>
                                        <td class="text-center">{{ $s['tickets'] }}</td>
                                        <td class="text-end">{{ $fmt($s['ca']) }} Fc</td>
                                        <td class="text-end">{{ $pct($s['part']) }} %</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </section>

        <p class="rv-note-foot">
            <i class="bi bi-info-circle"></i>
            Le CA est calculé sur les précommandes confirmées, net des remises validées. Le coût utilise le prix d'achat moyen enregistré.
            Les créances sont le reste à payer après avance. Les périodes sont comptées à la date de création des précommandes.
        </p>

        {{-- Données des graphiques (relues par revenue.js à chaque mise à jour) --}}
        <div id="rv-data" data-charts="{{ json_encode($charts) }}" hidden></div>
    </div>

    <style>
        .rv {
            --rv-primary: #4f46e5;
            --rv-primary-soft: #eef2ff;
            --rv-ink: #0f172a;
            --rv-ink-2: #475569;
            --rv-ink-3: #94a3b8;
            --rv-line: #e2e8f0;
            --rv-ok: #15803d;
            --rv-ok-soft: #dcfce7;
            --rv-watch: #b45309;
            --rv-watch-soft: #fef3c7;
            --rv-bad: #dc2626;
            --rv-bad-soft: #fee2e2;

            font-family: "Nunito", system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            color: var(--rv-ink);
        }

        .rv-header { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.25rem; }
        .rv-title { margin: 0; font-size: 1.5rem; font-weight: 800; letter-spacing: -.02em; }
        .rv-sub { margin: .15rem 0 0; color: var(--rv-ink-2); font-size: .9rem; }

        .rv-periods { display: inline-flex; padding: 4px; background: #fff; border: 1px solid var(--rv-line); border-radius: 12px; }
        .rv-period { padding: .45rem .9rem; border: 0; border-radius: 9px; background: transparent; color: var(--rv-ink-2); font-weight: 700; }
        .rv-period.is-active { background: var(--rv-primary); color: #fff; box-shadow: 0 6px 14px rgba(79, 70, 229, .25); }

        .rv-panel, .rv-diag, .rv-kpi {
            background: #fff; border: 1px solid var(--rv-line); border-radius: 16px;
            box-shadow: 0 1px 2px rgba(15, 23, 42, .05);
        }
        .rv-panel { min-width: 0; padding: 1.2rem 1.25rem; margin-bottom: 1rem; }
        .rv-panel__head { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: .5rem; margin-bottom: .9rem; }
        .rv-panel__head h5 { margin: 0; font-size: .98rem; font-weight: 800; }
        .rv-muted { color: var(--rv-ink-3); font-size: .85rem; font-weight: 600; }
        .rv-empty { margin: .6rem 0 0; text-align: center; color: var(--rv-ink-3); font-weight: 600; }

        /* Diagnostic */
        .rv-diag { display: grid; grid-template-columns: auto 1fr 1.4fr; gap: 1.25rem; align-items: center; padding: 1.25rem 1.4rem; margin-bottom: 1rem; }
        .rv-score {
            width: 104px; height: 104px; border-radius: 50%;
            background: conic-gradient(var(--c) calc(var(--score) * 1%), #e2e8f0 0);
            display: grid; place-items: center;
        }
        .rv-score__inner { width: 84px; height: 84px; border-radius: 50%; background: #fff; display: grid; place-items: center; font-size: 1.6rem; font-weight: 800; }
        .rv-diag__text span { display: block; color: var(--rv-ink-3); font-size: .72rem; font-weight: 800; letter-spacing: .05em; text-transform: uppercase; }
        .rv-diag__text strong { display: block; font-size: 1.35rem; font-weight: 800; }
        .rv-diag__text p { margin: .2rem 0 0; color: var(--rv-ink-2); font-size: .85rem; }
        .rv-diag__actions h6 { margin: 0 0 .5rem; font-size: .8rem; font-weight: 800; text-transform: uppercase; letter-spacing: .05em; color: var(--rv-ink-2); }
        .rv-action { display: flex; gap: .6rem; padding: .55rem .7rem; border-radius: 12px; margin-bottom: .4rem; font-size: .85rem; }
        .rv-action i { font-size: 1rem; margin-top: .1rem; }
        .rv-action strong { display: block; font-weight: 800; }
        .rv-action p { margin: .1rem 0 0; color: var(--rv-ink-2); }
        .rv-action--bad { background: var(--rv-bad-soft); color: var(--rv-bad); }
        .rv-action--watch { background: var(--rv-watch-soft); color: var(--rv-watch); }

        /* KPI */
        .rv-kpis { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: .85rem; margin-bottom: 1rem; }
        .rv-kpi { display: flex; flex-direction: column; gap: .25rem; padding: 1.05rem 1.1rem; }
        .rv-kpi.is-principal { border-color: rgba(79, 70, 229, .45); box-shadow: 0 10px 24px rgba(79, 70, 229, .12); }
        .rv-kpi__top { display: flex; align-items: center; justify-content: space-between; margin-bottom: .35rem; }
        .rv-kpi__icon { display: grid; place-items: center; width: 40px; height: 40px; border-radius: 12px; background: var(--rv-primary-soft); color: var(--rv-primary); font-size: 1.1rem; }
        .rv-kpi__label { color: var(--rv-ink-3); font-size: .72rem; font-weight: 800; letter-spacing: .05em; text-transform: uppercase; }
        .rv-kpi__value { font-size: 1.45rem; font-weight: 800; font-variant-numeric: tabular-nums; }
        .rv-kpi__value small { font-size: .8rem; color: var(--rv-ink-3); }
        .rv-kpi__sub { color: var(--rv-ink-2); font-size: .8rem; }
        .rv-delta { display: inline-flex; align-items: center; gap: .2rem; padding: .2rem .55rem; border-radius: 999px; font-size: .76rem; font-weight: 800; }
        .rv-delta.is-up { background: var(--rv-ok-soft); color: var(--rv-ok); }
        .rv-delta.is-down { background: var(--rv-bad-soft); color: var(--rv-bad); }
        .rv-delta.is-neutral { background: #f1f5f9; color: var(--rv-ink-2); }

        /* Lectures */
        .rv-lectures { margin: 0; padding-left: 1.2rem; color: var(--rv-ink-2); line-height: 1.7; font-size: .92rem; }

        /* Grille */
        .rv-grid { display: grid; grid-template-columns: 1fr; gap: 1rem; margin-bottom: 1rem; }
        .rv-grid .rv-panel { margin-bottom: 0; }
        .rv-chart { position: relative; height: 280px; }
        .rv-chart--sm { height: 240px; }

        /* Tunnel */
        .rv-step { display: grid; grid-template-columns: 1fr 1.4fr auto; align-items: center; gap: .75rem; padding: .45rem 0; font-size: .88rem; }
        .rv-step__bar { height: 10px; border-radius: 999px; background: #f1f5f9; overflow: hidden; }
        .rv-step__bar span { display: block; height: 100%; border-radius: 999px; background: linear-gradient(90deg, #4f46e5, #818cf8); }
        .rv-step strong { font-variant-numeric: tabular-nums; min-width: 2.5rem; text-align: right; }
        .rv-note { margin: .8rem 0 0; color: var(--rv-ink-3); font-size: .8rem; line-height: 1.5; }

        /* Tables et badges */
        .rv-table { margin: 0; }
        .rv-table thead th { padding: .7rem .8rem; background: #f8fafc; border-bottom: 1px solid var(--rv-line); color: var(--rv-ink-2); font-size: .7rem; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; white-space: nowrap; }
        .rv-table tbody td { padding: .75rem .8rem; border-bottom: 1px solid #f1f5f9; vertical-align: middle; font-variant-numeric: tabular-nums; }
        .rv-table tbody tr:last-child td { border-bottom: 0; }
        .rv-badge { display: inline-block; padding: .22rem .65rem; border-radius: 999px; font-size: .75rem; font-weight: 800; white-space: nowrap; }
        .rv-badge--ok { background: var(--rv-ok-soft); color: var(--rv-ok); }
        .rv-badge--watch { background: var(--rv-watch-soft); color: var(--rv-watch); }
        .rv-badge--bad { background: var(--rv-bad-soft); color: var(--rv-bad); }
        .rv-badge--neutral { background: #f1f5f9; color: var(--rv-ink-2); }

        .rv-list { list-style: none; margin: 0; padding: 0; }
        .rv-list li { display: flex; justify-content: space-between; align-items: center; gap: 1rem; padding: .55rem 0; border-bottom: 1px solid #f1f5f9; font-size: .9rem; }
        .rv-list li:last-child { border-bottom: 0; }
        .rv-list strong { font-variant-numeric: tabular-nums; font-weight: 800; }
        .rv-text-warn { color: var(--rv-watch) !important; }
        .rv-text-bad { color: var(--rv-bad) !important; }

        .rv-tags { display: flex; flex-wrap: wrap; gap: .35rem; margin-top: .8rem; }
        .rv-tag { padding: .2rem .6rem; border-radius: 999px; background: var(--rv-bad-soft); color: var(--rv-bad); font-size: .76rem; font-weight: 700; }

        .rv-note-foot { display: flex; gap: .5rem; margin: 1rem 0 0; color: var(--rv-ink-3); font-size: .8rem; line-height: 1.5; }

        @media (min-width: 992px) {
            .rv-grid { grid-template-columns: repeat(12, minmax(0, 1fr)); }
            .rv-grid > .rv-panel { grid-column: span 4; }
            .rv-grid > .rv-panel--wide { grid-column: span 8; }
            .rv-grid > .rv-panel--half { grid-column: span 6; }
            .rv-grid > .rv-panel--full { grid-column: span 12; }
        }

        @media (max-width: 991.98px) {
            .rv-diag { grid-template-columns: 1fr; text-align: center; justify-items: center; }
            .rv-diag__actions { width: 100%; text-align: left; }
        }

        @media (max-width: 575.98px) {
            .rv-header { align-items: stretch; }
            .rv-periods { width: 100%; }
            .rv-period { flex: 1 1 auto; }
            .rv-kpi__value { font-size: 1.25rem; }
            .rv-chart { height: 230px; }
            .rv-step { grid-template-columns: 1fr auto; }
            .rv-step__bar { display: none; }
        }
    </style>
</div>