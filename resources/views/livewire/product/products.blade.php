<div class="page-wrapper prd-page">
    <div class="content">

        {{-- En-tête --}}
        <div class="prd-header">
            <div>
                <h4 class="prd-header__title">Produits</h4>
                <p class="prd-header__sub">{{ $produits->total() }} produit(s) enregistré(s)</p>
            </div>

            <button type="button" class="btn prd-btn-primary" wire:click="openCreate">
                <i class="bi bi-plus-lg"></i>
                <span>Ajouter un produit</span>
            </button>
        </div>

        {{-- Messages --}}
        @if (session('message'))
            <div class="prd-alert prd-alert--success" role="alert">
                <i class="bi bi-check-circle-fill"></i>
                <span>{{ session('message') }}</span>
            </div>
        @endif

        @if (session('error'))
            <div class="prd-alert prd-alert--danger" role="alert">
                <i class="bi bi-exclamation-octagon-fill"></i>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        <div class="prd-card">

            {{-- Barre d'outils --}}
            <div class="prd-toolbar">
                <label class="prd-search">
                    <i class="bi bi-search"></i>
                    <input type="search"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Rechercher un produit…"
                        autocomplete="off">
                </label>

                <select class="prd-select" wire:model.live="filterCategorie" aria-label="Filtrer par catégorie">
                    <option value="">Toutes les catégories</option>
                    @foreach ($categories as $categorie)
                        <option value="{{ $categorie->id }}">{{ $categorie->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Tableau --}}
            <div class="table-responsive">
                <table class="table prd-table align-middle">
                    <thead>
                        <tr>
                            <th>Produit</th>
                            <th>Catégorie</th>
                            <th class="text-center">Stock</th>
                            <th class="text-end">Prix</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($produits as $item)
                            @php
                                $qte = (int) ($item->quantity ?? 0);
                                $niveau = $qte <= 0 ? 'danger' : ($qte < 5 ? 'warning' : 'success');
                                $prix = rtrim(rtrim(number_format((float) $item->price, 2, ',', ' '), '0'), ',');
                            @endphp

                            <tr wire:key="produit-{{ $item->id }}">
                                <td>
                                    <div class="prd-product">
                                        @if ($item->path)
                                            <img src="{{ asset('storage/uploads/' . $item->path) }}"
                                                alt="{{ $item->name }}" class="prd-thumb" loading="lazy">
                                        @else
                                            <span class="prd-thumb prd-thumb--empty">
                                                <i class="bi bi-image"></i>
                                            </span>
                                        @endif
                                        <span class="prd-name">{{ $item->name }}</span>
                                    </div>
                                </td>
                                <td>
                                    <span class="prd-chip">{{ $item->categorie?->name ?? '—' }}</span>
                                </td>
                                <td class="text-center">
                                    <span class="prd-stock prd-stock--{{ $niveau }}">{{ $qte }}</span>
                                </td>
                                <td class="text-end prd-price">{{ $prix }} Fc</td>
                                <td class="text-end">
                                    <div class="prd-actions">
                                        <a class="prd-action" title="Détails"
                                            href="{{ route('product-detail', ['id' => $item->id]) }}">
                                            <i class="bi bi-eye"></i>
                                        </a>

                                        <button type="button" class="prd-action" title="Modifier"
                                            wire:click="edit({{ $item->id }})"
                                            wire:loading.attr="disabled">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>

                                        <button type="button" class="prd-action prd-action--danger" title="Supprimer"
                                            data-id="{{ $item->id }}"
                                            data-name="{{ $item->name }}"
                                            onclick="confirmerSuppressionProduit(this)">
                                            <i class="bi bi-trash3"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="prd-empty">
                                    <i class="bi bi-box-seam"></i>
                                    <p>Aucun produit trouvé.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($produits->hasPages())
                <div class="prd-pagination">
                    {{ $produits->links('livewire::bootstrap') }}
                </div>
            @endif
        </div>
    </div>

    {{-- Modal création / modification --}}
    <div wire:ignore.self class="modal fade" id="produitModal" tabindex="-1"
        aria-labelledby="produitModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content prd-modal">
                <form wire:submit="save" novalidate>

                    <div class="modal-header">
                        <h5 class="modal-title" id="produitModalLabel">
                            {{ $produitId ? 'Modifier le produit' : 'Nouveau produit' }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>

                    <div class="modal-body">
                        <div class="row g-3">

                            {{-- Image --}}
                            <div class="col-12 col-md-4">
                                <label class="prd-label">Image</label>
                                <label class="prd-dropzone" for="produit-photo">
                                    @if ($photo)
                                        <img src="{{ $photo->temporaryUrl() }}" alt="Aperçu" class="prd-dropzone__img">
                                    @elseif ($currentPhoto)
                                        <img src="{{ asset('storage/uploads/' . $currentPhoto) }}" alt="Image actuelle" class="prd-dropzone__img">
                                    @else
                                        <span class="prd-dropzone__placeholder">
                                            <i class="bi bi-cloud-arrow-up"></i>
                                            <small>Choisir une image</small>
                                        </span>
                                    @endif
                                    <input id="produit-photo" type="file" accept="image/*" class="d-none" wire:model="photo">
                                </label>
                                <div class="prd-loading" wire:loading wire:target="photo">
                                    <span class="spinner-border spinner-border-sm" aria-hidden="true"></span>
                                    Envoi de l'image…
                                </div>
                                @error('photo')
                                    <div class="prd-error">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Champs --}}
                            <div class="col-12 col-md-8">
                                <div class="mb-3">
                                    <label for="produit-name" class="prd-label">Nom du produit</label>
                                    <input id="produit-name" type="text" wire:model="name" maxlength="150"
                                        placeholder="Ex. Coca-Cola 33 cl"
                                        class="form-control prd-input @error('name') is-invalid @enderror">
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="produit-categorie" class="prd-label">Catégorie</label>
                                    <select id="produit-categorie" wire:model="categorie_id"
                                        class="form-select prd-input @error('categorie_id') is-invalid @enderror">
                                        <option value="">Sélectionner une catégorie</option>
                                        @foreach ($categories as $categorie)
                                            <option value="{{ $categorie->id }}">{{ $categorie->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('categorie_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div>
                                    <label for="produit-price" class="prd-label">Prix de vente (Fc)</label>
                                    <input id="produit-price" type="text" inputmode="decimal" wire:model="price"
                                        placeholder="0"
                                        class="form-control prd-input @error('price') is-invalid @enderror">
                                    @error('price')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn prd-btn-ghost" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn prd-btn-primary"
                            wire:loading.attr="disabled" wire:target="save,photo">
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
        .prd-page {
            --prd-primary: #4f46e5;
            --prd-primary-hover: #4338ca;
            --prd-primary-soft: #eef2ff;
            --prd-ink: #0f172a;
            --prd-ink-2: #475569;
            --prd-ink-3: #94a3b8;
            --prd-line: #e2e8f0;
            --prd-danger: #dc2626;
            --prd-danger-soft: #fee2e2;
            --prd-warning: #b45309;
            --prd-warning-soft: #fef3c7;
            --prd-success: #15803d;
            --prd-success-soft: #dcfce7;

            font-family: "Nunito", system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            color: var(--prd-ink);
        }

        /* En-tête */
        .prd-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1.25rem;
        }
        .prd-header__title { margin: 0; font-size: 1.5rem; font-weight: 800; letter-spacing: -.02em; }
        .prd-header__sub { margin: .15rem 0 0; color: var(--prd-ink-2); font-size: .9rem; }

        /* Boutons */
        .prd-btn-primary {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            height: 44px;
            padding: 0 1.1rem;
            border: 0;
            border-radius: 12px;
            background: var(--prd-primary);
            color: #fff;
            font-weight: 700;
            box-shadow: 0 8px 18px rgba(79, 70, 229, .25);
            transition: background .15s ease, transform .15s ease;
        }
        .prd-btn-primary:hover { background: var(--prd-primary-hover); color: #fff; transform: translateY(-1px); }
        .prd-btn-primary:disabled { opacity: .7; }
        .prd-btn-ghost {
            height: 44px;
            padding: 0 1.1rem;
            border: 1px solid var(--prd-line);
            border-radius: 12px;
            background: #fff;
            color: var(--prd-ink-2);
            font-weight: 700;
        }
        .prd-btn-ghost:hover { background: #f8fafc; color: var(--prd-ink); }

        /* Alertes */
        .prd-alert {
            display: flex;
            align-items: center;
            gap: .6rem;
            margin-bottom: 1rem;
            padding: .8rem 1rem;
            border-radius: 12px;
            font-weight: 600;
        }
        .prd-alert--success { background: var(--prd-success-soft); color: var(--prd-success); }
        .prd-alert--danger  { background: var(--prd-danger-soft);  color: var(--prd-danger); }

        /* Carte et barre d'outils */
        .prd-card {
            background: #fff;
            border: 1px solid var(--prd-line);
            border-radius: 16px;
            box-shadow: 0 1px 2px rgba(15, 23, 42, .05);
            overflow: hidden;
        }
        .prd-toolbar {
            display: flex;
            flex-wrap: wrap;
            gap: .75rem;
            padding: 1rem 1.25rem;
            border-bottom: 1px solid var(--prd-line);
        }
        .prd-search { position: relative; flex: 1 1 260px; max-width: 360px; margin: 0; }
        .prd-search i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--prd-ink-3);
            pointer-events: none;
        }
        .prd-search input,
        .prd-select {
            width: 100%;
            height: 44px;
            border: 1px solid var(--prd-line);
            border-radius: 12px;
            background: #f8fafc;
            font: inherit;
            color: var(--prd-ink);
            transition: border-color .15s ease, box-shadow .15s ease, background .15s ease;
        }
        .prd-search input { padding: 0 1rem 0 2.6rem; }
        .prd-select { flex: 0 1 240px; padding: 0 .9rem; }
        .prd-search input:focus,
        .prd-select:focus {
            outline: 0;
            background: #fff;
            border-color: var(--prd-primary);
            box-shadow: 0 0 0 4px rgba(79, 70, 229, .12);
        }

        /* Tableau */
        .prd-table { margin: 0; }
        .prd-table thead th {
            padding: .85rem 1.25rem;
            background: #f8fafc;
            border-bottom: 1px solid var(--prd-line);
            color: var(--prd-ink-2);
            font-size: .72rem;
            font-weight: 800;
            letter-spacing: .06em;
            text-transform: uppercase;
        }
        .prd-table tbody td {
            padding: .85rem 1.25rem;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
        }
        .prd-table tbody tr:last-child td { border-bottom: 0; }
        .prd-table tbody tr:hover td { background: #fafbff; }

        .prd-product { display: flex; align-items: center; gap: .85rem; }
        .prd-thumb {
            flex: 0 0 44px;
            width: 44px;
            height: 44px;
            border-radius: 12px;
            object-fit: cover;
            background: #f1f5f9;
        }
        .prd-thumb--empty {
            display: grid;
            place-items: center;
            color: var(--prd-ink-3);
            font-size: 1.1rem;
        }
        .prd-name { font-weight: 700; }

        .prd-chip {
            display: inline-block;
            padding: .25rem .65rem;
            border-radius: 999px;
            background: var(--prd-primary-soft);
            color: var(--prd-primary);
            font-size: .8rem;
            font-weight: 700;
        }

        .prd-stock {
            display: inline-block;
            min-width: 36px;
            padding: .25rem .6rem;
            border-radius: 999px;
            font-size: .85rem;
            font-weight: 800;
        }
        .prd-stock--success { background: var(--prd-success-soft); color: var(--prd-success); }
        .prd-stock--warning { background: var(--prd-warning-soft); color: var(--prd-warning); }
        .prd-stock--danger  { background: var(--prd-danger-soft);  color: var(--prd-danger); }

        .prd-price { font-weight: 800; white-space: nowrap; }

        .prd-actions { display: inline-flex; gap: .4rem; }
        .prd-action {
            display: grid;
            place-items: center;
            width: 36px;
            height: 36px;
            border: 0;
            border-radius: 10px;
            background: var(--prd-primary-soft);
            color: var(--prd-primary);
            font-size: .95rem;
            text-decoration: none;
            transition: transform .15s ease;
        }
        .prd-action:hover { transform: translateY(-1px); color: var(--prd-primary); }
        .prd-action--danger { background: var(--prd-danger-soft); color: var(--prd-danger); }
        .prd-action--danger:hover { color: var(--prd-danger); }
        .prd-action:focus-visible { outline: 3px solid rgba(79, 70, 229, .35); outline-offset: 2px; }

        .prd-empty { padding: 2.5rem 1rem !important; text-align: center; color: var(--prd-ink-3); }
        .prd-empty i { font-size: 2rem; }
        .prd-empty p { margin: .5rem 0 0; font-weight: 600; }

        .prd-pagination { padding: 1rem 1.25rem; border-top: 1px solid var(--prd-line); }

        /* Modal */
        .prd-modal {
            border: 0;
            border-radius: 16px;
            box-shadow: 0 25px 60px rgba(15, 23, 42, .25);
            font-family: "Nunito", system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
        }
        .prd-modal .modal-header { padding: 1.1rem 1.25rem; border-bottom: 1px solid var(--prd-line); }
        .prd-modal .modal-title { font-weight: 800; }
        .prd-modal .modal-body { padding: 1.25rem; }
        .prd-modal .modal-footer { gap: .5rem; padding: 1rem 1.25rem; border-top: 1px solid var(--prd-line); }

        .prd-label {
            display: block;
            margin-bottom: .4rem;
            color: var(--prd-ink-2);
            font-size: .85rem;
            font-weight: 700;
        }
        .prd-input {
            min-height: 46px;
            border: 1px solid var(--prd-line);
            border-radius: 12px;
            font-size: 1rem;
        }
        .prd-input:focus {
            border-color: var(--prd-primary);
            box-shadow: 0 0 0 4px rgba(79, 70, 229, .12);
        }

        .prd-dropzone {
            display: flex;
            align-items: center;
            justify-content: center;
            aspect-ratio: 1 / 1;
            margin: 0;
            border: 2px dashed #c7d2fe;
            border-radius: 16px;
            background: #f8faff;
            overflow: hidden;
            cursor: pointer;
            transition: border-color .15s ease, background .15s ease;
        }
        .prd-dropzone:hover { border-color: var(--prd-primary); background: var(--prd-primary-soft); }
        .prd-dropzone__img { width: 100%; height: 100%; object-fit: cover; }
        .prd-dropzone__placeholder {
            display: grid;
            place-items: center;
            gap: .3rem;
            color: var(--prd-primary);
            text-align: center;
        }
        .prd-dropzone__placeholder i { font-size: 2rem; }
        .prd-dropzone__placeholder small { font-weight: 700; }

        .prd-loading {
            display: flex;
            align-items: center;
            gap: .5rem;
            margin-top: .5rem;
            color: var(--prd-ink-2);
            font-size: .85rem;
        }
        .prd-error { margin-top: .4rem; color: var(--prd-danger); font-size: .85rem; font-weight: 600; }

        /* Responsive */
        @media (max-width: 767.98px) {
            .prd-table thead th:nth-child(2),
            .prd-table tbody td:nth-child(2) { display: none; }
        }
        @media (max-width: 575.98px) {
            .prd-header { align-items: stretch; }
            .prd-btn-primary { justify-content: center; }
            .prd-select { flex: 1 1 100%; }
            .prd-search { max-width: none; }
            .prd-table thead th,
            .prd-table tbody td { padding: .75rem .8rem; }
            .prd-table thead th:nth-child(3),
            .prd-table tbody td:nth-child(3) { display: none; }
            .prd-dropzone { aspect-ratio: 16 / 9; }
        }
    </style>
</div>