<div class="page-wrapper tbl-page">
    <div class="content">

        {{-- En-tête --}}
        <div class="tbl-header">
            <div>
                <h4 class="tbl-header__title">Tables</h4>
                <p class="tbl-header__sub">Gérez les tables de votre salle</p>
            </div>

            <button type="button" class="btn tbl-btn-primary" wire:click="openCreate">
                <i class="bi bi-plus-lg"></i>
                <span>Ajouter une table</span>
            </button>
        </div>

        {{-- Indicateurs --}}
        <div class="tbl-stats">
            <div class="tbl-stat">
                <span class="tbl-stat__icon"><i class="bi bi-grid-3x3-gap"></i></span>
                <div>
                    <span class="tbl-stat__label">Tables</span>
                    <span class="tbl-stat__value">{{ $stats['total'] }}</span>
                </div>
            </div>
            <div class="tbl-stat">
                <span class="tbl-stat__icon tbl-stat__icon--green"><i class="bi bi-people"></i></span>
                <div>
                    <span class="tbl-stat__label">Places au total</span>
                    <span class="tbl-stat__value">{{ $stats['places'] }}</span>
                </div>
            </div>
        </div>

        {{-- Messages --}}
        @if (session('message'))
            <div class="tbl-alert tbl-alert--success" role="alert">
                <i class="bi bi-check-circle-fill"></i>
                <span>{{ session('message') }}</span>
            </div>
        @endif

        @if (session('error'))
            <div class="tbl-alert tbl-alert--danger" role="alert">
                <i class="bi bi-exclamation-octagon-fill"></i>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        <div class="tbl-card">

            {{-- Barre d'outils --}}
            <div class="tbl-toolbar">
                <label class="tbl-search">
                    <i class="bi bi-search"></i>
                    <input type="search"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Rechercher une table…"
                        autocomplete="off">
                </label>
            </div>

            {{-- Tableau --}}
            <div class="table-responsive">
                <table class="table tbl-table align-middle">
                    <thead>
                        <tr>
                            <th class="tbl-col-num">N°</th>
                            <th>Nom</th>
                            <th class="text-center">Places</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($tables as $item)
                            <tr wire:key="table-{{ $item->id }}">
                                <td class="tbl-col-num">
                                    {{ $tables->firstItem() + $loop->index }}
                                </td>
                                <td>
                                    <span class="tbl-name">{{ $item->name }}</span>
                                </td>
                                <td class="text-center">
                                    <span class="tbl-places">
                                        <i class="bi bi-people"></i>
                                        {{ $item->places }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <div class="tbl-actions">
                                        <button type="button" class="tbl-action" title="Modifier"
                                            wire:click="edit({{ $item->id }})"
                                            wire:loading.attr="disabled">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>

                                        <button type="button" class="tbl-action tbl-action--danger" title="Supprimer"
                                            data-id="{{ $item->id }}"
                                            data-name="{{ $item->name }}"
                                            onclick="confirmerSuppressionTable(this)">
                                            <i class="bi bi-trash3"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="tbl-empty">
                                    <i class="bi bi-grid-3x3-gap"></i>
                                    <p>Aucune table trouvée.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($tables->hasPages())
                <div class="tbl-pagination">
                    {{ $tables->links('livewire::bootstrap') }}
                </div>
            @endif
        </div>
    </div>

    {{-- Modal création / modification --}}
    <div wire:ignore.self class="modal fade" id="tableModal" tabindex="-1"
        aria-labelledby="tableModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content tbl-modal">
                <form wire:submit="save" novalidate>

                    <div class="modal-header">
                        <h5 class="modal-title" id="tableModalLabel">
                            {{ $tableId ? 'Modifier la table' : 'Nouvelle table' }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>

                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="table-name" class="tbl-label">Nom de la table</label>
                            <input id="table-name" type="text" wire:model="name" maxlength="100"
                                placeholder="Ex. Terrasse 1"
                                autocomplete="off"
                                class="form-control tbl-input @error('name') is-invalid @enderror">
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div>
                            <label for="table-places" class="tbl-label">Nombre de places</label>
                            <input id="table-places" type="number" min="1" max="999" step="1" inputmode="numeric"
                                wire:model="places" placeholder="4"
                                class="form-control tbl-input @error('places') is-invalid @enderror">
                            @error('places')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn tbl-btn-ghost" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn tbl-btn-primary" wire:loading.attr="disabled" wire:target="save">
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
        .tbl-page {
            --tbl-primary: #4f46e5;
            --tbl-primary-hover: #4338ca;
            --tbl-primary-soft: #eef2ff;
            --tbl-ink: #0f172a;
            --tbl-ink-2: #475569;
            --tbl-ink-3: #94a3b8;
            --tbl-line: #e2e8f0;
            --tbl-danger: #dc2626;
            --tbl-danger-soft: #fee2e2;
            --tbl-success: #15803d;
            --tbl-success-soft: #dcfce7;

            font-family: "Nunito", system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            color: var(--tbl-ink);
        }

        /* En-tête */
        .tbl-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1.25rem;
        }
        .tbl-header__title { margin: 0; font-size: 1.5rem; font-weight: 800; letter-spacing: -.02em; }
        .tbl-header__sub { margin: .15rem 0 0; color: var(--tbl-ink-2); font-size: .9rem; }

        /* Boutons */
        .tbl-btn-primary {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            height: 44px;
            padding: 0 1.1rem;
            border: 0;
            border-radius: 12px;
            background: var(--tbl-primary);
            color: #fff;
            font-weight: 700;
            box-shadow: 0 8px 18px rgba(79, 70, 229, .25);
            transition: background .15s ease, transform .15s ease;
        }
        .tbl-btn-primary:hover { background: var(--tbl-primary-hover); color: #fff; transform: translateY(-1px); }
        .tbl-btn-primary:disabled { opacity: .7; }
        .tbl-btn-ghost {
            height: 44px;
            padding: 0 1.1rem;
            border: 1px solid var(--tbl-line);
            border-radius: 12px;
            background: #fff;
            color: var(--tbl-ink-2);
            font-weight: 700;
        }
        .tbl-btn-ghost:hover { background: #f8fafc; color: var(--tbl-ink); }

        /* Indicateurs */
        .tbl-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: .75rem;
            margin-bottom: 1.25rem;
        }
        .tbl-stat {
            display: flex;
            align-items: center;
            gap: .85rem;
            padding: 1rem 1.1rem;
            background: #fff;
            border: 1px solid var(--tbl-line);
            border-radius: 16px;
            box-shadow: 0 1px 2px rgba(15, 23, 42, .05);
        }
        .tbl-stat__icon {
            display: grid;
            place-items: center;
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: var(--tbl-primary-soft);
            color: var(--tbl-primary);
            font-size: 1.2rem;
        }
        .tbl-stat__icon--green { background: var(--tbl-success-soft); color: var(--tbl-success); }
        .tbl-stat__label {
            display: block;
            color: var(--tbl-ink-3);
            font-size: .72rem;
            font-weight: 800;
            letter-spacing: .05em;
            text-transform: uppercase;
        }
        .tbl-stat__value {
            display: block;
            font-size: 1.35rem;
            font-weight: 800;
            font-variant-numeric: tabular-nums;
        }

        /* Alertes */
        .tbl-alert {
            display: flex;
            align-items: center;
            gap: .6rem;
            margin-bottom: 1rem;
            padding: .8rem 1rem;
            border-radius: 12px;
            font-weight: 600;
        }
        .tbl-alert--success { background: var(--tbl-success-soft); color: var(--tbl-success); }
        .tbl-alert--danger  { background: var(--tbl-danger-soft);  color: var(--tbl-danger); }

        /* Carte */
        .tbl-card {
            background: #fff;
            border: 1px solid var(--tbl-line);
            border-radius: 16px;
            box-shadow: 0 1px 2px rgba(15, 23, 42, .05);
            overflow: hidden;
        }
        .tbl-toolbar {
            padding: 1rem 1.25rem;
            border-bottom: 1px solid var(--tbl-line);
        }
        .tbl-search { position: relative; display: block; max-width: 360px; margin: 0; }
        .tbl-search i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--tbl-ink-3);
            pointer-events: none;
        }
        .tbl-search input {
            width: 100%;
            height: 44px;
            padding: 0 1rem 0 2.6rem;
            border: 1px solid var(--tbl-line);
            border-radius: 12px;
            background: #f8fafc;
            font: inherit;
            color: var(--tbl-ink);
            transition: border-color .15s ease, box-shadow .15s ease, background .15s ease;
        }
        .tbl-search input:focus {
            outline: 0;
            background: #fff;
            border-color: var(--tbl-primary);
            box-shadow: 0 0 0 4px rgba(79, 70, 229, .12);
        }

        /* Tableau */
        .tbl-table { margin: 0; }
        .tbl-table thead th {
            padding: .85rem 1.25rem;
            background: #f8fafc;
            border-bottom: 1px solid var(--tbl-line);
            color: var(--tbl-ink-2);
            font-size: .72rem;
            font-weight: 800;
            letter-spacing: .06em;
            text-transform: uppercase;
        }
        .tbl-table tbody td {
            padding: .9rem 1.25rem;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
        }
        .tbl-table tbody tr:last-child td { border-bottom: 0; }
        .tbl-table tbody tr:hover td { background: #fafbff; }
        .tbl-col-num { width: 70px; color: var(--tbl-ink-3); font-weight: 700; }
        .tbl-name { font-weight: 700; }

        .tbl-places {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            padding: .25rem .7rem;
            border-radius: 999px;
            background: var(--tbl-primary-soft);
            color: var(--tbl-primary);
            font-weight: 800;
            font-variant-numeric: tabular-nums;
        }

        .tbl-actions { display: inline-flex; gap: .4rem; }
        .tbl-action {
            display: grid;
            place-items: center;
            width: 36px;
            height: 36px;
            border: 0;
            border-radius: 10px;
            background: var(--tbl-primary-soft);
            color: var(--tbl-primary);
            font-size: .95rem;
            transition: transform .15s ease;
        }
        .tbl-action:hover { transform: translateY(-1px); }
        .tbl-action--danger { background: var(--tbl-danger-soft); color: var(--tbl-danger); }
        .tbl-action:focus-visible { outline: 3px solid rgba(79, 70, 229, .35); outline-offset: 2px; }

        .tbl-empty { padding: 2.5rem 1rem !important; text-align: center; color: var(--tbl-ink-3); }
        .tbl-empty i { font-size: 2rem; }
        .tbl-empty p { margin: .5rem 0 0; font-weight: 600; }

        .tbl-pagination { padding: 1rem 1.25rem; border-top: 1px solid var(--tbl-line); }

        /* Modal */
        .tbl-modal {
            border: 0;
            border-radius: 16px;
            box-shadow: 0 25px 60px rgba(15, 23, 42, .25);
            font-family: "Nunito", system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
        }
        .tbl-modal .modal-header { padding: 1.1rem 1.25rem; border-bottom: 1px solid var(--tbl-line); }
        .tbl-modal .modal-title { font-weight: 800; }
        .tbl-modal .modal-body { padding: 1.25rem; }
        .tbl-modal .modal-footer { gap: .5rem; padding: 1rem 1.25rem; border-top: 1px solid var(--tbl-line); }

        .tbl-label {
            display: block;
            margin-bottom: .4rem;
            color: var(--tbl-ink-2);
            font-size: .85rem;
            font-weight: 700;
        }
        .tbl-input {
            min-height: 48px;
            border: 1px solid var(--tbl-line);
            border-radius: 12px;
            font-size: 1rem;
        }
        .tbl-input:focus {
            border-color: var(--tbl-primary);
            box-shadow: 0 0 0 4px rgba(79, 70, 229, .12);
        }

        /* Responsive */
        @media (max-width: 575.98px) {
            .tbl-header { align-items: stretch; }
            .tbl-btn-primary { justify-content: center; }
            .tbl-search { max-width: none; }
            .tbl-table thead th,
            .tbl-table tbody td { padding: .75rem .9rem; }
            .tbl-col-num { display: none; }
        }
    </style>
</div>