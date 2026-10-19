@extends('layouts.app')

@section('content')
@php
    $devise = 'Fc';
    $montant = fn ($n) => number_format((float) $n, 0, ',', ' ');
    $pourcent = fn ($n) => rtrim(rtrim(number_format((float) $n, 2, ',', ' '), '0'), ',');
@endphp

<div class="page-wrapper cmd-page">
    <div class="content">

        {{-- En-tête --}}
        <div class="cmd-header">
            <div>
                <h4 class="cmd-header__title">Commandes</h4>
                <p class="cmd-header__sub">{{ $commandes->total() }} commande(s) enregistrée(s)</p>
            </div>
        </div>

        <div class="cmd-card">

            {{-- Filtres --}}
            <form method="GET" action="{{ route('admin-commande') }}" class="cmd-toolbar">
                <label class="cmd-search">
                    <i class="bi bi-search"></i>
                    <input type="search" name="q" value="{{ $recherche }}"
                        placeholder="Rechercher un code de commande…" autocomplete="off">
                </label>

                <select name="statut" class="cmd-select" aria-label="Filtrer par statut" onchange="this.form.submit()">
                    <option value="">Tous les statuts</option>
                    <option value="confirme" @selected($statut === 'confirme')>Confirmées</option>
                    <option value="en_cours" @selected($statut === 'en_cours')>En cours</option>
                </select>

                <button type="submit" class="cmd-btn">Rechercher</button>

                @if ($recherche !== '' || $statut)
                    <a href="{{ route('admin-commande') }}" class="cmd-btn-ghost">Réinitialiser</a>
                @endif
            </form>

            {{-- Tableau --}}
            <div class="table-responsive">
                <table class="table cmd-table align-middle">
                    <thead>
                        <tr>
                            <th>Commande</th>
                            <th class="text-center">Qté</th>
                            <th class="text-end">Sous-total</th>
                            <th class="text-center">Remise</th>
                            <th class="text-end">Total</th>
                            <th>Date</th>
                            <th class="text-center">Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($commandes as $cmd)
                            <tr>
                                <td>
                                    <details class="cmd-details">
                                        <summary>
                                            <span class="cmd-code">{{ $cmd['code'] }}</span>
                                            <span class="cmd-count">{{ $cmd['lignes']->count() }} article(s)</span>
                                        </summary>

                                        <ul class="cmd-lines">
                                            @foreach ($cmd['lignes'] as $ligne)
                                                <li>
                                                    <span class="cmd-lines__name">
                                                        {{ $ligne->produit?->name ?? 'Produit supprimé' }}
                                                    </span>
                                                    <span class="cmd-lines__calc">
                                                        {{ $ligne->quantity_commande }} × {{ $montant($ligne->produit?->price) }} {{ $devise }}
                                                    </span>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </details>
                                </td>

                                <td class="text-center">{{ $cmd['quantite'] }}</td>

                                <td class="text-end">{{ $montant($cmd['sous_total']) }} {{ $devise }}</td>

                                <td class="text-center">
                                    @if ($cmd['pourcentage'] > 0)
                                        <span class="cmd-badge cmd-badge--info">-{{ $pourcent($cmd['pourcentage']) }} %</span>
                                    @else
                                        <span class="cmd-muted">Aucune</span>
                                    @endif
                                </td>

                                <td class="text-end cmd-total">{{ $montant($cmd['total']) }} {{ $devise }}</td>

                                <td class="cmd-muted">{{ $cmd['date']?->format('d/m/Y H:i') }}</td>

                                <td class="text-center">
                                    @if ($cmd['confirme'])
                                        <span class="cmd-badge cmd-badge--success">Confirmée</span>
                                    @else
                                        <span class="cmd-badge cmd-badge--warning">En cours</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="cmd-empty">
                                    <i class="bi bi-receipt"></i>
                                    <p>Aucune commande trouvée.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($commandes->hasPages())
                <div class="cmd-pagination">
                    {{ $commandes->links('pagination::bootstrap-5') }}
                </div>
            @endif
        </div>
    </div>

    <style>
        .cmd-page {
            --cmd-primary: #4f46e5;
            --cmd-primary-hover: #4338ca;
            --cmd-primary-soft: #eef2ff;
            --cmd-ink: #0f172a;
            --cmd-ink-2: #475569;
            --cmd-ink-3: #94a3b8;
            --cmd-line: #e2e8f0;
            --cmd-success: #15803d;
            --cmd-success-soft: #dcfce7;
            --cmd-warning: #b45309;
            --cmd-warning-soft: #fef3c7;

            font-family: "Nunito", system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            color: var(--cmd-ink);
        }

        .cmd-header { margin-bottom: 1.25rem; }
        .cmd-header__title { margin: 0; font-size: 1.5rem; font-weight: 800; letter-spacing: -.02em; }
        .cmd-header__sub { margin: .15rem 0 0; color: var(--cmd-ink-2); font-size: .9rem; }

        .cmd-card {
            background: #fff;
            border: 1px solid var(--cmd-line);
            border-radius: 16px;
            box-shadow: 0 1px 2px rgba(15, 23, 42, .05);
            overflow: hidden;
        }

        .cmd-toolbar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: .6rem;
            margin: 0;
            padding: 1rem 1.25rem;
            border-bottom: 1px solid var(--cmd-line);
        }
        .cmd-search { position: relative; flex: 1 1 260px; max-width: 360px; margin: 0; }
        .cmd-search i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--cmd-ink-3);
            pointer-events: none;
        }
        .cmd-search input,
        .cmd-select {
            width: 100%;
            height: 44px;
            border: 1px solid var(--cmd-line);
            border-radius: 12px;
            background: #f8fafc;
            font: inherit;
            color: var(--cmd-ink);
            transition: border-color .15s ease, box-shadow .15s ease, background .15s ease;
        }
        .cmd-search input { padding: 0 1rem 0 2.6rem; }
        .cmd-select { flex: 0 1 220px; padding: 0 .9rem; }
        .cmd-search input:focus,
        .cmd-select:focus {
            outline: 0;
            background: #fff;
            border-color: var(--cmd-primary);
            box-shadow: 0 0 0 4px rgba(79, 70, 229, .12);
        }
        .cmd-btn {
            height: 44px;
            padding: 0 1.1rem;
            border: 0;
            border-radius: 12px;
            background: var(--cmd-primary);
            color: #fff;
            font-weight: 700;
        }
        .cmd-btn:hover { background: var(--cmd-primary-hover); color: #fff; }
        .cmd-btn-ghost {
            display: inline-flex;
            align-items: center;
            height: 44px;
            padding: 0 1rem;
            border: 1px solid var(--cmd-line);
            border-radius: 12px;
            color: var(--cmd-ink-2);
            font-weight: 700;
            text-decoration: none;
        }
        .cmd-btn-ghost:hover { background: #f8fafc; color: var(--cmd-ink); }

        .cmd-table { margin: 0; }
        .cmd-table thead th {
            padding: .85rem 1.25rem;
            background: #f8fafc;
            border-bottom: 1px solid var(--cmd-line);
            color: var(--cmd-ink-2);
            font-size: .72rem;
            font-weight: 800;
            letter-spacing: .06em;
            text-transform: uppercase;
            white-space: nowrap;
        }
        .cmd-table tbody td {
            padding: .85rem 1.25rem;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
            font-variant-numeric: tabular-nums;
        }
        .cmd-table tbody tr:last-child td { border-bottom: 0; }
        .cmd-table tbody tr:hover td { background: #fafbff; }
        .cmd-total { font-weight: 800; white-space: nowrap; }
        .cmd-muted { color: var(--cmd-ink-3); font-size: .88rem; }

        .cmd-details summary {
            display: inline-flex;
            align-items: center;
            gap: .6rem;
            cursor: pointer;
            list-style: none;
        }
        .cmd-details summary::-webkit-details-marker { display: none; }
        .cmd-details summary::before {
            content: "\F282";
            font-family: "bootstrap-icons";
            color: var(--cmd-ink-3);
            transition: transform .15s ease;
        }
        .cmd-details[open] summary::before { transform: rotate(90deg); }
        .cmd-code { font-weight: 800; }
        .cmd-count {
            padding: .15rem .55rem;
            border-radius: 999px;
            background: var(--cmd-primary-soft);
            color: var(--cmd-primary);
            font-size: .75rem;
            font-weight: 700;
        }
        .cmd-lines {
            margin: .6rem 0 0 1.4rem;
            padding: .6rem .9rem;
            list-style: none;
            background: #f8fafc;
            border: 1px solid var(--cmd-line);
            border-radius: 12px;
        }
        .cmd-lines li {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            padding: .3rem 0;
            font-size: .88rem;
        }
        .cmd-lines__name { font-weight: 700; }
        .cmd-lines__calc { color: var(--cmd-ink-2); white-space: nowrap; }

        .cmd-badge {
            display: inline-block;
            padding: .25rem .7rem;
            border-radius: 999px;
            font-size: .78rem;
            font-weight: 800;
            white-space: nowrap;
        }
        .cmd-badge--success { background: var(--cmd-success-soft); color: var(--cmd-success); }
        .cmd-badge--warning { background: var(--cmd-warning-soft); color: var(--cmd-warning); }
        .cmd-badge--info    { background: var(--cmd-primary-soft); color: var(--cmd-primary); }

        .cmd-empty { padding: 2.5rem 1rem !important; text-align: center; color: var(--cmd-ink-3); }
        .cmd-empty i { font-size: 2rem; }
        .cmd-empty p { margin: .5rem 0 0; font-weight: 600; }

        .cmd-pagination { padding: 1rem 1.25rem; border-top: 1px solid var(--cmd-line); }

        @media (max-width: 767.98px) {
            .cmd-table thead th:nth-child(2),
            .cmd-table tbody td:nth-child(2),
            .cmd-table thead th:nth-child(6),
            .cmd-table tbody td:nth-child(6) { display: none; }
        }
        @media (max-width: 575.98px) {
            .cmd-select { flex: 1 1 100%; }
            .cmd-search { max-width: none; }
            .cmd-table thead th,
            .cmd-table tbody td { padding: .7rem .8rem; }
        }
    </style>
</div>
@endsection