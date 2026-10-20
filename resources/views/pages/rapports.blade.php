@extends('layouts.app')

@section('content')
@php
    $fmt = fn ($n) => number_format((float) $n, 0, ',', ' ');
    $pct = fn ($n) => rtrim(rtrim(number_format((float) $n, 1, ',', ' '), '0'), ',');

    $libellePreset = [
        'aujourdhui'     => "Aujourd'hui",
        '7j'             => '7 derniers jours',
        'mois'           => 'Mois en cours',
        'mois_precedent' => 'Mois précédent',
        'perso'          => 'Période personnalisée',
    ];

    // Paramètres communs aux exports et au lien d'onglet
    $parametres = [
        'from'   => $from->format('Y-m-d'),
        'to'     => $to->format('Y-m-d'),
        'onglet' => $onglet,
    ];

    $totalProduits = [
        'entrees' => $produits->sum('entrees'),
        'sorties' => $produits->sum('sorties'),
        'vendues' => $produits->sum('vendues'),
        'ca'      => $produits->sum('ca'),
        'cout'    => $produits->sum(fn ($p) => (float) ($p->cout ?? 0)),
        'marge'   => $produits->sum(fn ($p) => (float) ($p->marge ?? 0)),
    ];

    $totalCommandes = [
        'quantite' => $commandes->where('confirmee', true)->sum('quantite'),
        'brut'     => $commandes->where('confirmee', true)->sum('brut'),
        'remise'   => $commandes->where('confirmee', true)->sum('remise'),
        'net'      => $commandes->where('confirmee', true)->sum('net'),
    ];
@endphp

<div class="page-wrapper rpt-page">
    <div class="content">

        {{-- En-tête --}}
        <div class="rpt-header rpt-no-print">
            <div>
                <h4 class="rpt-title">Rapports</h4>
                <p class="rpt-sub">
                    {{ $libellePreset[$preset] ?? 'Période' }} :
                    du {{ $from->format('d/m/Y') }} au {{ $to->format('d/m/Y') }}
                </p>
            </div>

            <div class="rpt-actions">
                <button type="button" class="rpt-btn rpt-btn--ghost" onclick="window.print()">
                    <i class="bi bi-printer"></i><span>Imprimer</span>
                </button>
                <a class="rpt-btn rpt-btn--ghost" href="{{ route('rapports.export', array_merge($parametres, ['format' => 'csv'])) }}">
                    <i class="bi bi-filetype-csv"></i><span>CSV</span>
                </a>
                <a class="rpt-btn rpt-btn--primary" href="{{ route('rapports.export', array_merge($parametres, ['format' => 'xlsx'])) }}">
                    <i class="bi bi-file-earmark-excel"></i><span>Excel</span>
                </a>
            </div>
        </div>

        {{-- Visible uniquement à l'impression --}}
        <div class="rpt-print-only">
            <h2>Rapport d'activité</h2>
            <p>Du {{ $from->format('d/m/Y') }} au {{ $to->format('d/m/Y') }} · édité le {{ now()->format('d/m/Y H:i') }}</p>
        </div>

        {{-- Filtres --}}
        <div class="rpt-card rpt-no-print rpt-filters">
            <div class="rpt-chips">
                @foreach (['aujourdhui', '7j', 'mois', 'mois_precedent'] as $cle)
                    <a href="{{ route('rapports', ['preset' => $cle, 'onglet' => $onglet]) }}"
                        class="rpt-chip {{ $preset === $cle ? 'is-active' : '' }}">
                        {{ $libellePreset[$cle] }}
                    </a>
                @endforeach
            </div>

            <form method="GET" action="{{ route('rapports') }}" class="rpt-form">
                <input type="hidden" name="onglet" value="{{ $onglet }}">

                <label class="rpt-field">
                    <span>Du</span>
                    <input type="date" name="from" value="{{ $from->format('Y-m-d') }}" required>
                </label>
                <label class="rpt-field">
                    <span>Au</span>
                    <input type="date" name="to" value="{{ $to->format('Y-m-d') }}" required>
                </label>

                <button type="submit" class="rpt-btn rpt-btn--primary">
                    <i class="bi bi-search"></i><span>Afficher</span>
                </button>
            </form>
        </div>

        {{-- Indicateurs --}}
        <section class="rpt-kpis">
            <article class="rpt-kpi rpt-kpi--main">
                <span class="rpt-kpi__label">Chiffre d'affaires net</span>
                <strong class="rpt-kpi__value">{{ $fmt($synthese['net']) }} <small>Fc</small></strong>
                <span class="rpt-kpi__sub">Brut {{ $fmt($synthese['brut']) }} Fc · remises {{ $fmt($synthese['remises']) }} Fc</span>
            </article>

            <article class="rpt-kpi">
                <span class="rpt-kpi__label">Marge brute</span>
                <strong class="rpt-kpi__value">{{ $fmt($synthese['marge']) }} <small>Fc</small></strong>
                <span class="rpt-kpi__sub">
                    {{ $synthese['taux_marge'] === null ? 'Taux non calculable' : $pct($synthese['taux_marge']) . ' % du net' }}
                </span>
            </article>

            <article class="rpt-kpi">
                <span class="rpt-kpi__label">Coût des ventes</span>
                <strong class="rpt-kpi__value">{{ $fmt($synthese['cout']) }} <small>Fc</small></strong>
                <span class="rpt-kpi__sub">Prix d'achat moyen × quantités vendues</span>
            </article>

            <article class="rpt-kpi">
                <span class="rpt-kpi__label">Commandes confirmées</span>
                <strong class="rpt-kpi__value">{{ $synthese['commandes'] }}</strong>
                <span class="rpt-kpi__sub">
                    Panier moyen {{ $fmt($synthese['panier']) }} Fc · {{ $synthese['en_cours'] }} en cours
                </span>
            </article>

            <article class="rpt-kpi">
                <span class="rpt-kpi__label">Mouvements de stock</span>
                <strong class="rpt-kpi__value">+{{ $fmt($synthese['entrees']) }} / −{{ $fmt($synthese['sorties']) }}</strong>
                <span class="rpt-kpi__sub">Entrées et sorties sur la période</span>
            </article>

            <article class="rpt-kpi">
                <span class="rpt-kpi__label">Valeur du stock</span>
                <strong class="rpt-kpi__value">{{ $fmt($synthese['valeur_stock']) }} <small>Fc</small></strong>
                <span class="rpt-kpi__sub">Au prix de vente, à la date du rapport</span>
            </article>
        </section>

        {{-- Onglets --}}
        <nav class="rpt-tabs rpt-no-print" aria-label="Type de rapport">
            <a href="{{ route('rapports', array_merge($parametres, ['onglet' => 'produits'])) }}"
                class="rpt-tab {{ $onglet === 'produits' ? 'is-active' : '' }}">
                <i class="bi bi-box-seam"></i> Produits et stock
            </a>
            <a href="{{ route('rapports', array_merge($parametres, ['onglet' => 'commandes'])) }}"
                class="rpt-tab {{ $onglet === 'commandes' ? 'is-active' : '' }}">
                <i class="bi bi-receipt"></i> Commandes
            </a>
        </nav>

        {{-- Onglet produits --}}
        @if ($onglet === 'produits')
            <div class="rpt-card">
                <div class="rpt-card__head">
                    <h5>Mouvements de stock par produit</h5>
                    <span class="rpt-muted">{{ $produits->count() }} produit(s)</span>
                </div>

                <div class="table-responsive">
                    <table class="table rpt-table">
                        <thead>
                            <tr>
                                <th>Produit</th>
                                <th>Catégorie</th>
                                <th class="text-end">Stock début</th>
                                <th class="text-end">Entrées</th>
                                <th class="text-end">Sorties</th>
                                <th class="text-end">Stock fin</th>
                                <th class="text-end">Vendues</th>
                                <th class="text-end">CA brut</th>
                                <th class="text-end">Marge</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($produits as $p)
                                @php
                                    $alerte = $p->stock_fin < 0 ? 'bad' : ($p->stock_fin < 5 ? 'warn' : '');
                                @endphp
                                <tr>
                                    <td class="fw-bold">{{ $p->nom }}</td>
                                    <td class="rpt-muted">{{ $p->categorie }}</td>
                                    <td class="text-end">{{ $fmt($p->stock_debut) }}</td>
                                    <td class="text-end rpt-pos">+{{ $fmt($p->entrees) }}</td>
                                    <td class="text-end rpt-neg">−{{ $fmt($p->sorties) }}</td>
                                    <td class="text-end">
                                        <span class="rpt-stock {{ $alerte ? 'rpt-stock--' . $alerte : '' }}">{{ $fmt($p->stock_fin) }}</span>
                                    </td>
                                    <td class="text-end">{{ $fmt($p->vendues) }}</td>
                                    <td class="text-end">{{ $fmt($p->ca) }} Fc</td>
                                    <td class="text-end">{{ $p->marge === null ? '—' : $fmt($p->marge) . ' Fc' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="rpt-empty">Aucun produit dans l'entreprise.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if ($produits->isNotEmpty())
                            <tfoot>
                                <tr>
                                    <th colspan="3">Total</th>
                                    <th class="text-end">+{{ $fmt($totalProduits['entrees']) }}</th>
                                    <th class="text-end">−{{ $fmt($totalProduits['sorties']) }}</th>
                                    <th></th>
                                    <th class="text-end">{{ $fmt($totalProduits['vendues']) }}</th>
                                    <th class="text-end">{{ $fmt($totalProduits['ca']) }} Fc</th>
                                    <th class="text-end">{{ $fmt($totalProduits['marge']) }} Fc</th>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>

                <p class="rpt-note">
                    Stock fin en rouge : stock négatif, à corriger. En orange : moins de 5 unités.
                    Le stock de début est reconstitué à partir du stock actuel et des mouvements enregistrés.
                </p>
            </div>
        @endif

        {{-- Onglet commandes --}}
        @if ($onglet === 'commandes')
            <div class="rpt-card">
                <div class="rpt-card__head">
                    <h5>Commandes de la période</h5>
                    <span class="rpt-muted">{{ $commandes->count() }} précommande(s)</span>
                </div>

                <div class="table-responsive">
                    <table class="table rpt-table">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Date</th>
                                <th>Statut</th>
                                <th>Facturée</th>
                                <th class="text-center">Qté</th>
                                <th class="text-end">Brut</th>
                                <th class="text-center">Remise</th>
                                <th class="text-end">Net</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($commandes as $c)
                                <tr>
                                    <td class="fw-bold">{{ $c->code }}</td>
                                    <td class="rpt-muted">{{ $c->date->format('d/m/Y H:i') }}</td>
                                    <td>
                                        <span class="rpt-badge {{ $c->confirmee ? 'rpt-badge--ok' : 'rpt-badge--warn' }}">
                                            {{ $c->confirmee ? 'Confirmée' : 'En cours' }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="rpt-badge {{ $c->facturee ? 'rpt-badge--ok' : 'rpt-badge--neutral' }}">
                                            {{ $c->facturee ? 'Oui' : 'Non' }}
                                        </span>
                                    </td>
                                    <td class="text-center">{{ $c->quantite }}</td>
                                    <td class="text-end">{{ $fmt($c->brut) }} Fc</td>
                                    <td class="text-center">{{ $c->pct > 0 ? '−' . $pct($c->pct) . ' %' : '—' }}</td>
                                    <td class="text-end fw-bold">{{ $fmt($c->net) }} Fc</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="rpt-empty">Aucune commande sur la période.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if ($commandes->isNotEmpty())
                            <tfoot>
                                <tr>
                                    <th colspan="4">Total des commandes confirmées</th>
                                    <th class="text-center">{{ $fmt($totalCommandes['quantite']) }}</th>
                                    <th class="text-end">{{ $fmt($totalCommandes['brut']) }} Fc</th>
                                    <th class="text-center">−{{ $fmt($totalCommandes['remise']) }} Fc</th>
                                    <th class="text-end">{{ $fmt($totalCommandes['net']) }} Fc</th>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>

                <p class="rpt-note">
                    Les totaux ne comptent que les commandes confirmées. Les commandes en cours sont listées pour information.
                </p>
            </div>
        @endif

        <p class="rpt-note-foot rpt-no-print">
            <i class="bi bi-info-circle"></i>
            Les dates sont incluses dans la période. Le CSV contient l'onglet affiché ; le fichier Excel contient les deux onglets.
        </p>
    </div>

    <style>
        .rpt-page {
            --rpt-primary: #4f46e5;
            --rpt-primary-hover: #4338ca;
            --rpt-primary-soft: #eef2ff;
            --rpt-ink: #0f172a;
            --rpt-ink-2: #475569;
            --rpt-ink-3: #94a3b8;
            --rpt-line: #e2e8f0;
            --rpt-ok: #15803d;
            --rpt-ok-soft: #dcfce7;
            --rpt-warn: #b45309;
            --rpt-warn-soft: #fef3c7;
            --rpt-bad: #dc2626;
            --rpt-bad-soft: #fee2e2;

            font-family: "Nunito", system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            color: var(--rpt-ink);
        }

        /* En-tête et actions */
        .rpt-header { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.25rem; }
        .rpt-title { margin: 0; font-size: 1.5rem; font-weight: 800; letter-spacing: -.02em; }
        .rpt-sub { margin: .15rem 0 0; color: var(--rpt-ink-2); font-size: .9rem; }
        .rpt-actions { display: flex; flex-wrap: wrap; gap: .5rem; }

        .rpt-btn {
            display: inline-flex; align-items: center; gap: .45rem;
            height: 42px; padding: 0 1rem; border-radius: 12px;
            font-weight: 700; text-decoration: none; cursor: pointer;
            transition: background .15s ease, transform .15s ease;
        }
        .rpt-btn--primary { background: var(--rpt-primary); color: #fff; border: 0; box-shadow: 0 8px 18px rgba(79, 70, 229, .25); }
        .rpt-btn--primary:hover { background: var(--rpt-primary-hover); color: #fff; transform: translateY(-1px); }
        .rpt-btn--ghost { background: #fff; color: var(--rpt-ink-2); border: 1px solid var(--rpt-line); }
        .rpt-btn--ghost:hover { background: #f8fafc; color: var(--rpt-ink); }

        .rpt-print-only { display: none; }

        /* Cartes et filtres */
        .rpt-card {
            background: #fff; border: 1px solid var(--rpt-line); border-radius: 16px;
            box-shadow: 0 1px 2px rgba(15, 23, 42, .05); overflow: hidden; margin-bottom: 1rem;
        }
        .rpt-filters { padding: 1rem 1.25rem; display: flex; flex-direction: column; gap: .9rem; }
        .rpt-chips { display: flex; flex-wrap: wrap; gap: .45rem; }
        .rpt-chip {
            padding: .4rem .85rem; border-radius: 999px; border: 1px solid var(--rpt-line);
            color: var(--rpt-ink-2); font-size: .85rem; font-weight: 700; text-decoration: none;
        }
        .rpt-chip:hover { border-color: var(--rpt-primary); color: var(--rpt-primary); }
        .rpt-chip.is-active { background: var(--rpt-primary); border-color: var(--rpt-primary); color: #fff; }

        .rpt-form { display: flex; flex-wrap: wrap; align-items: flex-end; gap: .75rem; }
        .rpt-field { display: flex; flex-direction: column; gap: .3rem; font-size: .8rem; font-weight: 700; color: var(--rpt-ink-2); }
        .rpt-field input {
            height: 42px; padding: 0 .8rem; border: 1px solid var(--rpt-line); border-radius: 12px;
            background: #f8fafc; font: inherit; color: var(--rpt-ink); min-width: 160px;
        }
        .rpt-field input:focus { outline: 0; border-color: var(--rpt-primary); box-shadow: 0 0 0 4px rgba(79, 70, 229, .12); background: #fff; }

        /* Indicateurs */
        .rpt-kpis { display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: .85rem; margin-bottom: 1rem; }
        .rpt-kpi {
            display: flex; flex-direction: column; gap: .25rem; padding: 1.05rem 1.1rem;
            background: #fff; border: 1px solid var(--rpt-line); border-radius: 16px;
            box-shadow: 0 1px 2px rgba(15, 23, 42, .05);
        }
        .rpt-kpi--main { border-color: rgba(79, 70, 229, .45); box-shadow: 0 10px 24px rgba(79, 70, 229, .12); }
        .rpt-kpi__label { color: var(--rpt-ink-3); font-size: .72rem; font-weight: 800; letter-spacing: .05em; text-transform: uppercase; }
        .rpt-kpi__value { font-size: 1.35rem; font-weight: 800; font-variant-numeric: tabular-nums; }
        .rpt-kpi__value small { font-size: .8rem; color: var(--rpt-ink-3); }
        .rpt-kpi__sub { color: var(--rpt-ink-2); font-size: .8rem; }

        /* Onglets */
        .rpt-tabs { display: inline-flex; gap: .3rem; padding: 4px; margin-bottom: 1rem; background: #fff; border: 1px solid var(--rpt-line); border-radius: 12px; }
        .rpt-tab {
            display: inline-flex; align-items: center; gap: .4rem; padding: .5rem .95rem; border-radius: 9px;
            color: var(--rpt-ink-2); font-weight: 700; text-decoration: none;
        }
        .rpt-tab:hover { color: var(--rpt-ink); }
        .rpt-tab.is-active { background: var(--rpt-primary); color: #fff; }

        /* Tableaux */
        .rpt-card__head { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: .5rem; padding: 1rem 1.25rem; border-bottom: 1px solid var(--rpt-line); }
        .rpt-card__head h5 { margin: 0; font-size: 1rem; font-weight: 800; }
        .rpt-muted { color: var(--rpt-ink-3); font-size: .85rem; font-weight: 600; }
        .rpt-table { margin: 0; }
        .rpt-table thead th {
            padding: .75rem 1rem; background: #f8fafc; border-bottom: 1px solid var(--rpt-line);
            color: var(--rpt-ink-2); font-size: .72rem; font-weight: 800; letter-spacing: .06em;
            text-transform: uppercase; white-space: nowrap;
        }
        .rpt-table tbody td { padding: .8rem 1rem; border-bottom: 1px solid #f1f5f9; vertical-align: middle; font-variant-numeric: tabular-nums; white-space: nowrap; }
        .rpt-table tbody tr:hover td { background: #fafbff; }
        .rpt-table tfoot th { padding: .8rem 1rem; background: #f8fafc; border-top: 2px solid var(--rpt-line); font-weight: 800; white-space: nowrap; }
        .rpt-pos { color: var(--rpt-ok); font-weight: 700; }
        .rpt-neg { color: var(--rpt-bad); font-weight: 700; }
        .rpt-empty { padding: 2.5rem 1rem !important; text-align: center; color: var(--rpt-ink-3); font-weight: 600; }
        .rpt-note { margin: 0; padding: .8rem 1.25rem; color: var(--rpt-ink-3); font-size: .8rem; border-top: 1px solid var(--rpt-line); }
        .rpt-note-foot { display: flex; gap: .5rem; color: var(--rpt-ink-3); font-size: .8rem; margin-top: 1rem; }

        .rpt-stock { display: inline-block; min-width: 2.5rem; padding: .15rem .55rem; border-radius: 999px; font-weight: 800; text-align: center; }
        .rpt-stock--warn { background: var(--rpt-warn-soft); color: var(--rpt-warn); }
        .rpt-stock--bad { background: var(--rpt-bad-soft); color: var(--rpt-bad); }

        .rpt-badge { display: inline-block; padding: .2rem .6rem; border-radius: 999px; font-size: .75rem; font-weight: 800; }
        .rpt-badge--ok { background: var(--rpt-ok-soft); color: var(--rpt-ok); }
        .rpt-badge--warn { background: var(--rpt-warn-soft); color: var(--rpt-warn); }
        .rpt-badge--neutral { background: #f1f5f9; color: var(--rpt-ink-2); }

        /* Responsive */
        @media (max-width: 575.98px) {
            .rpt-header { align-items: stretch; }
            .rpt-actions { width: 100%; }
            .rpt-actions .rpt-btn { flex: 1 1 auto; justify-content: center; }
            .rpt-form { width: 100%; }
            .rpt-field { flex: 1 1 100%; }
            .rpt-field input { min-width: 0; width: 100%; }
            .rpt-form .rpt-btn { width: 100%; justify-content: center; }
            .rpt-kpi__value { font-size: 1.2rem; }
        }

        /* Impression */
        @media print {
            @page { size: A4 landscape; margin: 12mm; }

            * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }

            .rpt-no-print,
            .pos-sidebar,
            .pos-header,
            .sidebar,
            .header,
            .mobile-user-menu { display: none !important; }

            .rpt-print-only { display: block; margin-bottom: 1rem; }
            .rpt-print-only h2 { margin: 0; font-size: 1.3rem; font-weight: 800; }
            .rpt-print-only p { margin: .2rem 0 0; color: #475569; font-size: .85rem; }

            .page-wrapper, .main-wrapper > .page-wrapper { margin: 0 !important; padding: 0 !important; background: #fff !important; }
            .rpt-page { background: #fff !important; }
            .rpt-card, .rpt-kpi { box-shadow: none !important; break-inside: avoid; }
            .rpt-table thead { display: table-header-group; }
            .rpt-table tr { break-inside: avoid; }
        }
    </style>
</div>
@endsection