<div class="page-wrapper cat-page">
    <div class="content">

        {{-- En-tête --}}
        <div class="cat-header">
            <div>
                <h4 class="cat-header__title">Catégories</h4>
                <p class="cat-header__sub">
                    {{ $categories->total() }} catégorie(s) enregistrée(s)
                </p>
            </div>

            <button type="button" class="btn cat-btn-primary" wire:click="openCreate">
                <i class="bi bi-plus-lg"></i>
                <span>Ajouter une catégorie</span>
            </button>
        </div>

        {{-- Messages --}}
        @if (session('message'))
            <div class="cat-alert cat-alert--success" role="alert">
                <i class="bi bi-check-circle-fill"></i>
                <span>{{ session('message') }}</span>
            </div>
        @endif

        @if (session('error'))
            <div class="cat-alert cat-alert--danger" role="alert">
                <i class="bi bi-exclamation-octagon-fill"></i>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        {{-- Carte principale --}}
        <div class="cat-card">

            <div class="cat-toolbar">
                <label class="cat-search">
                    <i class="bi bi-search"></i>
                    <input type="search"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Rechercher une catégorie…"
                        autocomplete="off">
                </label>
            </div>

            <div class="table-responsive">
                <table class="table cat-table align-middle">
                    <thead>
                        <tr>
                            <th class="cat-col-num">N°</th>
                            <th>Nom</th>
                            <th>Créée le</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($categories as $item)
                            <tr wire:key="categorie-{{ $item->id }}">
                                <td class="cat-col-num">
                                    {{ $categories->firstItem() + $loop->index }}
                                </td>
                                <td>
                                    <span class="cat-name">{{ $item->name }}</span>
                                </td>
                                <td class="cat-muted">
                                    {{ $item->created_at?->format('d/m/Y') }}
                                </td>
                                <td class="text-end">
                                    <div class="cat-actions">
                                        <button type="button"
                                            class="cat-action"
                                            title="Modifier"
                                            wire:click="edit({{ $item->id }})"
                                            wire:loading.attr="disabled">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>

                                        <button type="button"
                                            class="cat-action cat-action--danger"
                                            title="Supprimer"
                                            data-id="{{ $item->id }}"
                                            data-name="{{ $item->name }}"
                                            onclick="confirmerSuppressionCategorie(this)">
                                            <i class="bi bi-trash3"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="cat-empty">
                                    <i class="bi bi-tags"></i>
                                    <p>Aucune catégorie trouvée.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($categories->hasPages())
                <div class="cat-pagination">
                    {{ $categories->links('livewire::bootstrap') }}
                </div>
            @endif
        </div>
    </div>

    {{-- Modal création / modification --}}
    <div wire:ignore.self class="modal fade" id="categorieModal" tabindex="-1"
        aria-labelledby="categorieModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content cat-modal">
                <form wire:submit="save" novalidate>

                    <div class="modal-header">
                        <h5 class="modal-title" id="categorieModalLabel">
                            {{ $categorieId ? 'Modifier la catégorie' : 'Nouvelle catégorie' }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>

                    <div class="modal-body">
                        <label for="categorie-name" class="cat-label">Nom de la catégorie</label>
                        <input id="categorie-name"
                            type="text"
                            wire:model="name"
                            maxlength="100"
                            autocomplete="off"
                            placeholder="Ex. Boissons"
                            class="form-control cat-input @error('name') is-invalid @enderror">
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn cat-btn-ghost" data-bs-dismiss="modal">
                            Annuler
                        </button>
                        <button type="submit" class="btn cat-btn-primary" wire:loading.attr="disabled" wire:target="save">
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
        .cat-page {
            --cat-primary: #4f46e5;
            --cat-primary-hover: #4338ca;
            --cat-primary-soft: #eef2ff;
            --cat-ink: #0f172a;
            --cat-ink-2: #475569;
            --cat-ink-3: #94a3b8;
            --cat-line: #e2e8f0;
            --cat-danger: #dc2626;
            --cat-danger-soft: #fee2e2;
            --cat-success: #15803d;
            --cat-success-soft: #dcfce7;

            font-family: "Nunito", system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            color: var(--cat-ink);
        }

        .cat-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1.25rem;
        }
        .cat-header__title {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 800;
            letter-spacing: -.02em;
        }
        .cat-header__sub {
            margin: .15rem 0 0;
            color: var(--cat-ink-2);
            font-size: .9rem;
        }

        .cat-btn-primary {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            height: 44px;
            padding: 0 1.1rem;
            border: 0;
            border-radius: 12px;
            background: var(--cat-primary);
            color: #fff;
            font-weight: 700;
            box-shadow: 0 8px 18px rgba(79, 70, 229, .25);
            transition: background .15s ease, transform .15s ease;
        }
        .cat-btn-primary:hover {
            background: var(--cat-primary-hover);
            color: #fff;
            transform: translateY(-1px);
        }
        .cat-btn-primary:disabled { opacity: .7; }

        .cat-btn-ghost {
            height: 44px;
            padding: 0 1.1rem;
            border: 1px solid var(--cat-line);
            border-radius: 12px;
            background: #fff;
            color: var(--cat-ink-2);
            font-weight: 700;
        }
        .cat-btn-ghost:hover {
            background: #f8fafc;
            color: var(--cat-ink);
        }

        .cat-alert {
            display: flex;
            align-items: center;
            gap: .6rem;
            margin-bottom: 1rem;
            padding: .8rem 1rem;
            border-radius: 12px;
            font-weight: 600;
        }
        .cat-alert--success { background: var(--cat-success-soft); color: var(--cat-success); }
        .cat-alert--danger  { background: var(--cat-danger-soft);  color: var(--cat-danger); }

        .cat-card {
            background: #fff;
            border: 1px solid var(--cat-line);
            border-radius: 16px;
            box-shadow: 0 1px 2px rgba(15, 23, 42, .05);
            overflow: hidden;
        }

        .cat-toolbar {
            padding: 1rem 1.25rem;
            border-bottom: 1px solid var(--cat-line);
        }
        .cat-search {
            position: relative;
            display: block;
            max-width: 360px;
            margin: 0;
        }
        .cat-search i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--cat-ink-3);
            pointer-events: none;
        }
        .cat-search input {
            width: 100%;
            height: 44px;
            padding: 0 1rem 0 2.6rem;
            border: 1px solid var(--cat-line);
            border-radius: 12px;
            background: #f8fafc;
            font: inherit;
            color: var(--cat-ink);
            transition: border-color .15s ease, box-shadow .15s ease, background .15s ease;
        }
        .cat-search input:focus {
            outline: 0;
            background: #fff;
            border-color: var(--cat-primary);
            box-shadow: 0 0 0 4px rgba(79, 70, 229, .12);
        }

        .cat-table { margin: 0; }
        .cat-table thead th {
            padding: .85rem 1.25rem;
            background: #f8fafc;
            border-bottom: 1px solid var(--cat-line);
            color: var(--cat-ink-2);
            font-size: .72rem;
            font-weight: 800;
            letter-spacing: .06em;
            text-transform: uppercase;
        }
        .cat-table tbody td {
            padding: .9rem 1.25rem;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
        }
        .cat-table tbody tr:last-child td { border-bottom: 0; }
        .cat-table tbody tr:hover td { background: #fafbff; }
        .cat-col-num { width: 70px; color: var(--cat-ink-3); font-weight: 700; }
        .cat-name { font-weight: 700; }
        .cat-muted { color: var(--cat-ink-2); font-size: .9rem; }

        .cat-actions {
            display: inline-flex;
            gap: .4rem;
        }
        .cat-action {
            display: grid;
            place-items: center;
            width: 36px;
            height: 36px;
            border: 0;
            border-radius: 10px;
            background: var(--cat-primary-soft);
            color: var(--cat-primary);
            font-size: .95rem;
            transition: background .15s ease, color .15s ease, transform .15s ease;
        }
        .cat-action:hover { transform: translateY(-1px); }
        .cat-action--danger {
            background: var(--cat-danger-soft);
            color: var(--cat-danger);
        }
        .cat-action:focus-visible {
            outline: 3px solid rgba(79, 70, 229, .35);
            outline-offset: 2px;
        }

        .cat-empty {
            padding: 2.5rem 1rem !important;
            text-align: center;
            color: var(--cat-ink-3);
        }
        .cat-empty i { font-size: 2rem; }
        .cat-empty p { margin: .5rem 0 0; font-weight: 600; }

        .cat-pagination {
            padding: 1rem 1.25rem;
            border-top: 1px solid var(--cat-line);
        }

        .cat-modal {
            border: 0;
            border-radius: 16px;
            box-shadow: 0 25px 60px rgba(15, 23, 42, .25);
            font-family: "Nunito", system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
        }
        .cat-modal .modal-header {
            padding: 1.1rem 1.25rem;
            border-bottom: 1px solid var(--cat-line);
        }
        .cat-modal .modal-title { font-weight: 800; }
        .cat-modal .modal-body { padding: 1.25rem; }
        .cat-modal .modal-footer {
            gap: .5rem;
            padding: 1rem 1.25rem;
            border-top: 1px solid var(--cat-line);
        }

        .cat-label {
            display: block;
            margin-bottom: .4rem;
            color: var(--cat-ink-2);
            font-size: .85rem;
            font-weight: 700;
        }
        .cat-input {
            height: 48px;
            border: 1px solid var(--cat-line);
            border-radius: 12px;
            font-size: 1rem;
        }
        .cat-input:focus {
            border-color: var(--cat-primary);
            box-shadow: 0 0 0 4px rgba(79, 70, 229, .12);
        }
        .cat-input.is-invalid {
            border-color: var(--cat-danger);
            background-image: none;
        }

        @media (max-width: 575.98px) {
            .cat-header { align-items: stretch; }
            .cat-btn-primary { justify-content: center; }
            .cat-col-num { display: none; }
            .cat-table thead th,
            .cat-table tbody td { padding: .75rem .9rem; }
            .cat-search { max-width: none; }
        }
    </style>
</div>