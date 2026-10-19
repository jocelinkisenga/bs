@php
    $fmt = fn ($n) => rtrim(rtrim(number_format((float) $n, 2, ',', ' '), '0'), ',');
@endphp

<div class="page-wrapper dep-page">
    <div class="content">

        {{-- En-tête --}}
        <div class="dep-header">
            <div>
                <h4 class="dep-header__title">Dépenses</h4>
                <p class="dep-header__sub">{{ $depenses->total() }} dépense(s) enregistrée(s)</p>
            </div>

            <button type="button" class="btn dep-btn-primary" wire:click="openCreate">
                <i class="bi bi-plus-lg"></i>
                <span>Ajouter une dépense</span>
            </button>
        </div>

        {{-- Indicateurs --}}
        <div class="dep-stats">
            <div class="dep-stat">
                <span class="dep-stat__icon dep-stat__icon--red"><i class="bi bi-wallet2"></i></span>
                <div>
                    <span class="dep-stat__label">Total des dépenses</span>
                    <span class="dep-stat__value">{{ $fmt($stats['total']) }} <small>Fc</small></span>
                </div>
            </div>
            <div class="dep-stat">
                <span class="dep-stat__icon"><i class="bi bi-calendar-month"></i></span>
                <div>
                    <span class="dep-stat__label">Ce mois-ci</span>
                    <span class="dep-stat__value">{{ $fmt($stats['mois']) }} <small>Fc</small></span>
                </div>
            </div>
        </div>

        {{-- Messages --}}
        @if (session('message'))
            <div class="dep-alert dep-alert--success" role="alert">
                <i class="bi bi-check-circle-fill"></i>
                <span>{{ session('message') }}</span>
            </div>
        @endif

        @if (session('error'))
            <div class="dep-alert dep-alert--danger" role="alert">
                <i class="bi bi-exclamation-octagon-fill"></i>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        <div class="dep-card">

            {{-- Barre d'outils --}}
            <div class="dep-toolbar">
                <label class="dep-search">
                    <i class="bi bi-search"></i>
                    <input type="search"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Rechercher par motif ou bénéficiaire…"
                        autocomplete="off">
                </label>
            </div>

            {{-- Tableau --}}
            <div class="table-responsive">
                <table class="table dep-table align-middle">
                    <thead>
                        <tr>
                            <th class="dep-col-num">N°</th>
                            <th>Bénéficiaire</th>
                            <th>Motif</th>
                            <th>Date</th>
                            <th class="text-end">Montant</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($depenses as $item)
                            <tr wire:key="depense-{{ $item->id }}">
                                <td class="dep-col-num">
                                    {{ $depenses->firstItem() + $loop->index }}
                                </td>
                                <td>
                                    <div class="dep-person">
                                        <span class="dep-avatar">
                                            {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($item->user?->name ?? '?', 0, 1)) }}
                                        </span>
                                        <span class="dep-name">{{ $item->user?->name ?? '—' }}</span>
                                    </div>
                                </td>
                                <td class="dep-motif">{{ $item->motif }}</td>
                                <td class="dep-muted">{{ $item->created_at?->format('d/m/Y') }}</td>
                                <td class="text-end">
                                    <span class="dep-amount">{{ $fmt($item->montant) }} Fc</span>
                                </td>
                                <td class="text-end">
                                    <div class="dep-actions">
                                        <button type="button" class="dep-action" title="Modifier"
                                            wire:click="edit({{ $item->id }})"
                                            wire:loading.attr="disabled">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>

                                        <button type="button" class="dep-action dep-action--danger" title="Supprimer"
                                            data-id="{{ $item->id }}"
                                            data-label="{{ $item->motif }}"
                                            onclick="confirmerSuppressionDepense(this)">
                                            <i class="bi bi-trash3"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="dep-empty">
                                    <i class="bi bi-wallet2"></i>
                                    <p>Aucune dépense trouvée.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($depenses->hasPages())
                <div class="dep-pagination">
                    {{ $depenses->links('livewire::bootstrap') }}
                </div>
            @endif
        </div>
    </div>

    {{-- Modal création / modification --}}
    <div wire:ignore.self class="modal fade" id="depenseModal" tabindex="-1"
        aria-labelledby="depenseModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content dep-modal">
                <form wire:submit="save" novalidate>

                    <div class="modal-header">
                        <h5 class="modal-title" id="depenseModalLabel">
                            {{ $depenseId ? 'Modifier la dépense' : 'Nouvelle dépense' }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>

                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="dep-montant" class="dep-label">Montant (Fc)</label>
                            <input id="dep-montant" type="text" inputmode="decimal" wire:model="montant"
                                placeholder="0"
                                class="form-control dep-input @error('montant') is-invalid @enderror">
                            @error('montant')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="dep-beneficiaire" class="dep-label">Bénéficiaire</label>
                            <select id="dep-beneficiaire" wire:model="user_id"
                                class="form-select dep-input @error('user_id') is-invalid @enderror">
                                <option value="">Sélectionner un bénéficiaire</option>
                                @foreach ($beneficiaires as $personne)
                                    <option value="{{ $personne->id }}">{{ $personne->name }}</option>
                                @endforeach
                            </select>
                            @error('user_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div>
                            <label for="dep-motif" class="dep-label">Motif</label>
                            <textarea id="dep-motif" rows="3" wire:model="motif" maxlength="255"
                                placeholder="Ex. Achat de produits d'entretien"
                                class="form-control dep-input dep-textarea @error('motif') is-invalid @enderror"></textarea>
                            @error('motif')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn dep-btn-ghost" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn dep-btn-primary" wire:loading.attr="disabled" wire:target="save">
                            <span wire:loading.remove wire:target="save">Enregistrer</span>
                            <span wire:loading wire:target="save">
                                <span class="spinner-border spinner-border-sm" aria-hidden="true"></span>
                            </span>
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>

    <style>
        .dep-page {
            --dep-primary: #4f46e5;
            --dep-primary-hover: #4338ca;
            --dep-primary-soft: #eef2ff;
            --dep-ink: #0f172a;
            --dep-ink-2: #475569;
            --dep-ink-3: #94a3b8;
            --dep-line: #e2e8f0;
            --dep-danger: #dc2626;
            --dep-danger-soft: #fee2e2;
            --dep-success: #15803d;
            --dep-success-soft: #dcfce7;

            font-family: "Nunito", system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            color: var(--dep-ink);
        }

        /* En-tête */
        .dep-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1.25rem;
        }
        .dep-header__title { margin: 0; font-size: 1.5rem; font-weight: 800; letter-spacing: -.02em; }
        .dep-header__sub { margin: .15rem 0 0; color: var(--dep-ink-2); font-size: .9rem; }

        /* Boutons */
        .dep-btn-primary {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            height: 44px;
            padding: 0 1.1rem;
            border: 0;
            border-radius: 12px;
            background: var(--dep-primary);
            color: #fff;
            font-weight: 700;
            box-shadow: 0 8px 18px rgba(79, 70, 229, .25);
            transition: background .15s ease, transform .15s ease;
        }
        .dep-btn-primary:hover { background: var(--dep-primary-hover); color: #fff; transform: translateY(-1px); }
        .dep-btn-primary:disabled { opacity: .7; }
        .dep-btn-ghost {
            height: 44px;
            padding: 0 1.1rem;
            border: 1px solid var(--dep-line);
            border-radius: 12px;
            background: #fff;
            color: var(--dep-ink-2);
            font-weight: 700;
        }
        .dep-btn-ghost:hover { background: #f8fafc; color: var(--dep-ink); }

        /* Indicateurs */
        .dep-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: .75rem;
            margin-bottom: 1.25rem;
        }
        .dep-stat {
            display: flex;
            align-items: center;
            gap: .85rem;
            padding: 1rem 1.1rem;
            background: #fff;
            border: 1px solid var(--dep-line);
            border-radius: 16px;
            box-shadow: 0 1px 2px rgba(15, 23, 42, .05);
        }
        .dep-stat__icon {
            display: grid;
            place-items: center;
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: var(--dep-primary-soft);
            color: var(--dep-primary);
            font-size: 1.2rem;
        }
        .dep-stat__icon--red { background: var(--dep-danger-soft); color: var(--dep-danger); }
        .dep-stat__label {
            display: block;
            color: var(--dep-ink-3);
            font-size: .72rem;
            font-weight: 800;
            letter-spacing: .05em;
            text-transform: uppercase;
        }
        .dep-stat__value {
            display: block;
            font-size: 1.35rem;
            font-weight: 800;
            font-variant-numeric: tabular-nums;
        }
        .dep-stat__value small { font-size: .75rem; color: var(--dep-ink-3); }

        /* Alertes */
        .dep-alert {
            display: flex;
            align-items: center;
            gap: .6rem;
            margin-bottom: 1rem;
            padding: .8rem 1rem;
            border-radius: 12px;
            font-weight: 600;
        }
        .dep-alert--success { background: var(--dep-success-soft); color: var(--dep-success); }
        .dep-alert--danger  { background: var(--dep-danger-soft);  color: var(--dep-danger); }

        /* Carte */
        .dep-card {
            background: #fff;
            border: 1px solid var(--dep-line);
            border-radius: 16px;
            box-shadow: 0 1px 2px rgba(15, 23, 42, .05);
            overflow: hidden;
        }
        .dep-toolbar {
            padding: 1rem 1.25rem;
            border-bottom: 1px solid var(--dep-line);
        }
        .dep-search { position: relative; display: block; max-width: 420px; margin: 0; }
        .dep-search i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--dep-ink-3);
            pointer-events: none;
        }
        .dep-search input {
            width: 100%;
            height: 44px;
            padding: 0 1rem 0 2.6rem;
            border: 1px solid var(--dep-line);
            border-radius: 12px;
            background: #f8fafc;
            font: inherit;
            color: var(--dep-ink);
            transition: border-color .15s ease, box-shadow .15s ease, background .15s ease;
        }
        .dep-search input:focus {
            outline: 0;
            background: #fff;
            border-color: var(--dep-primary);
            box-shadow: 0 0 0 4px rgba(79, 70, 229, .12);
        }

        /* Tableau */
        .dep-table { margin: 0; }
        .dep-table thead th {
            padding: .85rem 1.25rem;
            background: #f8fafc;
            border-bottom: 1px solid var(--dep-line);
            color: var(--dep-ink-2);
            font-size: .72rem;
            font-weight: 800;
            letter-spacing: .06em;
            text-transform: uppercase;
            white-space: nowrap;
        }
        .dep-table tbody td {
            padding: .85rem 1.25rem;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
        }
        .dep-table tbody tr:last-child td { border-bottom: 0; }
        .dep-table tbody tr:hover td { background: #fafbff; }
        .dep-col-num { width: 70px; color: var(--dep-ink-3); font-weight: 700; }
        .dep-muted { color: var(--dep-ink-2); font-size: .9rem; white-space: nowrap; }
        .dep-motif { max-width: 320px; }

        .dep-person { display: flex; align-items: center; gap: .6rem; }
        .dep-avatar {
            display: grid;
            place-items: center;
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: var(--dep-primary-soft);
            color: var(--dep-primary);
            font-size: .85rem;
            font-weight: 800;
        }
        .dep-name { font-weight: 700; }

        .dep-amount {
            display: inline-block;
            padding: .25rem .7rem;
            border-radius: 999px;
            background: var(--dep-danger-soft);
            color: var(--dep-danger);
            font-weight: 800;
            font-variant-numeric: tabular-nums;
            white-space: nowrap;
        }

        .dep-actions { display: inline-flex; gap: .4rem; }
        .dep-action {
            display: grid;
            place-items: center;
            width: 36px;
            height: 36px;
            border: 0;
            border-radius: 10px;
            background: var(--dep-primary-soft);
            color: var(--dep-primary);
            font-size: .95rem;
            transition: transform .15s ease;
        }
        .dep-action:hover { transform: translateY(-1px); }
        .dep-action--danger { background: var(--dep-danger-soft); color: var(--dep-danger); }
        .dep-action:focus-visible { outline: 3px solid rgba(79, 70, 229, .35); outline-offset: 2px; }

        .dep-empty { padding: 2.5rem 1rem !important; text-align: center; color: var(--dep-ink-3); }
        .dep-empty i { font-size: 2rem; }
        .dep-empty p { margin: .5rem 0 0; font-weight: 600; }

        .dep-pagination { padding: 1rem 1.25rem; border-top: 1px solid var(--dep-line); }

        /* Modal */
        .dep-modal {
            border: 0;
            border-radius: 16px;
            box-shadow: 0 25px 60px rgba(15, 23, 42, .25);
            font-family: "Nunito", system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
        }
        .dep-modal .modal-header { padding: 1.1rem 1.25rem; border-bottom: 1px solid var(--dep-line); }
        .dep-modal .modal-title { font-weight: 800; }
        .dep-modal .modal-body { padding: 1.25rem; }
        .dep-modal .modal-footer { gap: .5rem; padding: 1rem 1.25rem; border-top: 1px solid var(--dep-line); }

        .dep-label {
            display: block;
            margin-bottom: .4rem;
            color: var(--dep-ink-2);
            font-size: .85rem;
            font-weight: 700;
        }
        .dep-input {
            min-height: 48px;
            border: 1px solid var(--dep-line);
            border-radius: 12px;
            font-size: 1rem;
        }
        .dep-textarea { min-height: 96px; resize: vertical; }
        .dep-input:focus {
            border-color: var(--dep-primary);
            box-shadow: 0 0 0 4px rgba(79, 70, 229, .12);
        }

        /* Responsive */
        @media (max-width: 767.98px) {
            .dep-table thead th:nth-child(4),
            .dep-table tbody td:nth-child(4) { display: none; }
        }
        @media (max-width: 575.98px) {
            .dep-header { align-items: stretch; }
            .dep-btn-primary { justify-content: center; }
            .dep-search { max-width: none; }
            .dep-table thead th,
            .dep-table tbody td { padding: .75rem .8rem; }
            .dep-table thead th:nth-child(1),
            .dep-table tbody td:nth-child(1) { display: none; }
        }
    </style>
</div>