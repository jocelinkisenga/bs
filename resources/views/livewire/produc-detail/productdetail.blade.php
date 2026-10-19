@php
    $qte       = (int) $produit->quantity;
    $niveau    = $qte <= 0 ? 'danger' : ($qte < 5 ? 'warning' : 'success');
    $fmt       = fn ($n) => rtrim(rtrim(number_format((float) $n, 2, ',', ' '), '0'), ',');
    $valeur    = (float) $produit->quantity * (float) $produit->price;
    $image     = $produit->path ? asset('storage/uploads/' . $produit->path) : null;
    $reference = 'REF-' . str_pad($produit->id, 6, '0', STR_PAD_LEFT);
@endphp

<div class="page-wrapper pd-page">
    <div class="content">

        {{-- En-tête --}}
        <div class="pd-header">
            <div>
                <a href="{{ route('products') }}" class="pd-back">
                    <i class="bi bi-arrow-left"></i>
                    <span>Retour aux produits</span>
                </a>
                <h4 class="pd-header__title">{{ $produit->name }}</h4>
            </div>

            <div class="pd-header__actions">
                <button type="button" class="btn pd-btn-ghost" data-bs-toggle="modal" data-bs-target="#priceModal">
                    <i class="bi bi-tag"></i>
                    <span>Modifier le prix</span>
                </button>
                <button type="button" class="btn pd-btn-primary" data-bs-toggle="modal" data-bs-target="#stockModal">
                    <i class="bi bi-plus-lg"></i>
                    <span>Ajouter du stock</span>
                </button>
            </div>
        </div>

        {{-- Message --}}
        @if (session('message'))
            <div class="pd-alert" role="alert">
                <i class="bi bi-check-circle-fill"></i>
                <span>{{ session('message') }}</span>
            </div>
        @endif

        <div class="row g-4">

            {{-- Image --}}
            <div class="col-12 col-lg-5">
                <div class="pd-card pd-media">
                    @if ($image)
                        <img src="{{ $image }}" alt="{{ $produit->name }}" class="pd-media__img">
                    @else
                        <div class="pd-media__empty">
                            <i class="bi bi-image"></i>
                            <span>Aucune image</span>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Informations --}}
            <div class="col-12 col-lg-7">
                <div class="pd-card pd-info">
                    <div class="pd-info__top">
                        <span class="pd-chip">{{ $produit->categorie?->name ?? '—' }}</span>
                        <span class="pd-ref">{{ $reference }}</span>
                    </div>

                    <div class="pd-stats">
                        <div class="pd-stat">
                            <span class="pd-stat__label">Prix de vente</span>
                            <span class="pd-stat__value">{{ $fmt($produit->price) }} <small>Fc</small></span>
                        </div>
                        <div class="pd-stat">
                            <span class="pd-stat__label">Stock</span>
                            <span class="pd-stat__value">
                                <span class="pd-stock pd-stock--{{ $niveau }}">{{ $qte }} pcs</span>
                            </span>
                        </div>
                        <div class="pd-stat">
                            <span class="pd-stat__label">Valeur du stock</span>
                            <span class="pd-stat__value">{{ $fmt($valeur) }} <small>Fc</small></span>
                        </div>
                    </div>

                    <dl class="pd-details">
                        <div>
                            <dt>Catégorie</dt>
                            <dd>{{ $produit->categorie?->name ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt>Dernière modification</dt>
                            <dd>{{ $produit->updated_at?->format('d/m/Y H:i') ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt>Mouvements récents</dt>
                            <dd>{{ $historique->count() }}</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>

        {{-- Historique --}}
        <div class="pd-card mt-4">
            <div class="pd-card__head">
                <h5>Historique des approvisionnements</h5>
                <span class="pd-muted">20 derniers mouvements</span>
            </div>

            <div class="table-responsive">
                <table class="table pd-table align-middle">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th class="text-end">Ajout</th>
                            <th class="text-end">Avant</th>
                            <th class="text-end">Après</th>
                            <th class="text-end">Prix d'achat</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($historique as $mouvement)
                            @php
                                $apres = (int) $mouvement->old_quantity + (int) $mouvement->new_quantity;
                            @endphp
                            <tr wire:key="mouvement-{{ $mouvement->id }}">
                                <td class="pd-muted">{{ $mouvement->created_at?->format('d/m/Y H:i') }}</td>
                                <td class="text-end">
                                    <span class="pd-plus">+{{ $mouvement->new_quantity }}</span>
                                </td>
                                <td class="text-end">{{ $mouvement->old_quantity }}</td>
                                <td class="text-end fw-bold">{{ $apres }}</td>
                                <td class="text-end">{{ $fmt($mouvement->prix_achat) }} Fc</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="pd-empty">
                                    <i class="bi bi-clock-history"></i>
                                    <p>Aucun mouvement de stock enregistré.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Modal : ajouter du stock --}}
    <div wire:ignore.self class="modal fade" id="stockModal" tabindex="-1"
        aria-labelledby="stockModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content pd-modal">
                <form wire:submit="ajouterStock" novalidate>

                    <div class="modal-header">
                        <h5 class="modal-title" id="stockModalLabel">Ajouter du stock</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>

                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="pd-quantite" class="pd-label">Quantité reçue</label>
                            <input id="pd-quantite" type="number" min="1" step="1" inputmode="numeric"
                                wire:model="quantiteAjout" placeholder="0"
                                class="form-control pd-input @error('quantiteAjout') is-invalid @enderror">
                            @error('quantiteAjout')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div>
                            <label for="pd-achat" class="pd-label">Prix d'achat unitaire (Fc)</label>
                            <input id="pd-achat" type="text" inputmode="decimal"
                                wire:model="prixAchat" placeholder="0"
                                class="form-control pd-input @error('prixAchat') is-invalid @enderror">
                            @error('prixAchat')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn pd-btn-ghost" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn pd-btn-primary" wire:loading.attr="disabled" wire:target="ajouterStock">
                            <span wire:loading.remove wire:target="ajouterStock">Ajouter</span>
                            <span wire:loading wire:target="ajouterStock">
                                <span class="spinner-border spinner-border-sm" aria-hidden="true"></span>
                            </span>
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>

    {{-- Modal : modifier le prix --}}
    <div wire:ignore.self class="modal fade" id="priceModal" tabindex="-1"
        aria-labelledby="priceModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content pd-modal">
                <form wire:submit="modifierPrix" novalidate>

                    <div class="modal-header">
                        <h5 class="modal-title" id="priceModalLabel">Modifier le prix</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>

                    <div class="modal-body">
                        <label for="pd-prix" class="pd-label">
                            Nouveau prix de vente (Fc)
                        </label>
                        <input id="pd-prix" type="text" inputmode="decimal"
                            wire:model="nouveauPrix"
                            placeholder="Prix actuel : {{ $fmt($produit->price) }}"
                            class="form-control pd-input @error('nouveauPrix') is-invalid @enderror">
                        @error('nouveauPrix')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn pd-btn-ghost" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn pd-btn-primary" wire:loading.attr="disabled" wire:target="modifierPrix">
                            <span wire:loading.remove wire:target="modifierPrix">Enregistrer</span>
                            <span wire:loading wire:target="modifierPrix">
                                <span class="spinner-border spinner-border-sm" aria-hidden="true"></span>
                            </span>
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>

    <style>
        .pd-page {
            --pd-primary: #4f46e5;
            --pd-primary-hover: #4338ca;
            --pd-primary-soft: #eef2ff;
            --pd-ink: #0f172a;
            --pd-ink-2: #475569;
            --pd-ink-3: #94a3b8;
            --pd-line: #e2e8f0;
            --pd-danger: #dc2626;
            --pd-danger-soft: #fee2e2;
            --pd-warning: #b45309;
            --pd-warning-soft: #fef3c7;
            --pd-success: #15803d;
            --pd-success-soft: #dcfce7;

            font-family: "Nunito", system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            color: var(--pd-ink);
        }

        /* En-tête */
        .pd-header {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1.25rem;
        }
        .pd-back {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            margin-bottom: .4rem;
            color: var(--pd-ink-2);
            font-size: .88rem;
            font-weight: 700;
            text-decoration: none;
        }
        .pd-back:hover { color: var(--pd-primary); }
        .pd-header__title {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 800;
            letter-spacing: -.02em;
        }
        .pd-header__actions { display: flex; flex-wrap: wrap; gap: .6rem; }

        /* Boutons */
        .pd-btn-primary {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            height: 44px;
            padding: 0 1.1rem;
            border: 0;
            border-radius: 12px;
            background: var(--pd-primary);
            color: #fff;
            font-weight: 700;
            box-shadow: 0 8px 18px rgba(79, 70, 229, .25);
            transition: background .15s ease, transform .15s ease;
        }
        .pd-btn-primary:hover { background: var(--pd-primary-hover); color: #fff; transform: translateY(-1px); }
        .pd-btn-primary:disabled { opacity: .7; }
        .pd-btn-ghost {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            height: 44px;
            padding: 0 1.1rem;
            border: 1px solid var(--pd-line);
            border-radius: 12px;
            background: #fff;
            color: var(--pd-ink-2);
            font-weight: 700;
        }
        .pd-btn-ghost:hover { background: #f8fafc; color: var(--pd-ink); }

        /* Alerte */
        .pd-alert {
            display: flex;
            align-items: center;
            gap: .6rem;
            margin-bottom: 1.25rem;
            padding: .8rem 1rem;
            border-radius: 12px;
            background: var(--pd-success-soft);
            color: var(--pd-success);
            font-weight: 600;
        }

        /* Cartes */
        .pd-card {
            height: 100%;
            background: #fff;
            border: 1px solid var(--pd-line);
            border-radius: 16px;
            box-shadow: 0 1px 2px rgba(15, 23, 42, .05);
            overflow: hidden;
        }
        .pd-card__head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: .5rem;
            padding: 1rem 1.25rem;
            border-bottom: 1px solid var(--pd-line);
        }
        .pd-card__head h5 { margin: 0; font-size: 1rem; font-weight: 800; }
        .pd-muted { color: var(--pd-ink-3); font-size: .85rem; font-weight: 600; }

        /* Image */
        .pd-media { display: flex; min-height: 320px; }
        .pd-media__img { width: 100%; height: 100%; object-fit: cover; }
        .pd-media__empty {
            flex: 1;
            display: grid;
            place-items: center;
            align-content: center;
            gap: .4rem;
            background: #f8fafc;
            color: var(--pd-ink-3);
            font-weight: 700;
        }
        .pd-media__empty i { font-size: 2.5rem; }

        /* Infos */
        .pd-info { padding: 1.5rem; }
        .pd-info__top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: .5rem;
            margin-bottom: 1.25rem;
        }
        .pd-chip {
            display: inline-block;
            padding: .3rem .75rem;
            border-radius: 999px;
            background: var(--pd-primary-soft);
            color: var(--pd-primary);
            font-size: .8rem;
            font-weight: 800;
        }
        .pd-ref {
            color: var(--pd-ink-3);
            font-size: .8rem;
            font-weight: 800;
            letter-spacing: .06em;
        }

        .pd-stats {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: .75rem;
            margin-bottom: 1.5rem;
        }
        .pd-stat {
            padding: .9rem 1rem;
            background: #f8fafc;
            border: 1px solid var(--pd-line);
            border-radius: 14px;
        }
        .pd-stat__label {
            display: block;
            margin-bottom: .3rem;
            color: var(--pd-ink-3);
            font-size: .72rem;
            font-weight: 800;
            letter-spacing: .05em;
            text-transform: uppercase;
        }
        .pd-stat__value {
            display: block;
            font-size: 1.25rem;
            font-weight: 800;
            font-variant-numeric: tabular-nums;
        }
        .pd-stat__value small { font-size: .75rem; color: var(--pd-ink-3); }

        .pd-stock {
            display: inline-block;
            padding: .2rem .6rem;
            border-radius: 999px;
            font-size: .95rem;
            font-weight: 800;
        }
        .pd-stock--success { background: var(--pd-success-soft); color: var(--pd-success); }
        .pd-stock--warning { background: var(--pd-warning-soft); color: var(--pd-warning); }
        .pd-stock--danger  { background: var(--pd-danger-soft);  color: var(--pd-danger); }

        .pd-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 1rem;
            margin: 0;
        }
        .pd-details dt {
            color: var(--pd-ink-3);
            font-size: .75rem;
            font-weight: 800;
            letter-spacing: .05em;
            text-transform: uppercase;
        }
        .pd-details dd {
            margin: .2rem 0 0;
            font-weight: 700;
        }

        /* Tableau historique */
        .pd-table { margin: 0; }
        .pd-table thead th {
            padding: .85rem 1.25rem;
            background: #f8fafc;
            border-bottom: 1px solid var(--pd-line);
            color: var(--pd-ink-2);
            font-size: .72rem;
            font-weight: 800;
            letter-spacing: .06em;
            text-transform: uppercase;
        }
        .pd-table tbody td {
            padding: .85rem 1.25rem;
            border-bottom: 1px solid #f1f5f9;
            font-variant-numeric: tabular-nums;
        }
        .pd-table tbody tr:last-child td { border-bottom: 0; }
        .pd-table tbody tr:hover td { background: #fafbff; }
        .pd-plus {
            display: inline-block;
            padding: .2rem .6rem;
            border-radius: 999px;
            background: var(--pd-success-soft);
            color: var(--pd-success);
            font-weight: 800;
        }
        .pd-empty {
            padding: 2.5rem 1rem !important;
            text-align: center;
            color: var(--pd-ink-3);
        }
        .pd-empty i { font-size: 2rem; }
        .pd-empty p { margin: .5rem 0 0; font-weight: 600; }

        /* Modals */
        .pd-modal {
            border: 0;
            border-radius: 16px;
            box-shadow: 0 25px 60px rgba(15, 23, 42, .25);
            font-family: "Nunito", system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
        }
        .pd-modal .modal-header { padding: 1.1rem 1.25rem; border-bottom: 1px solid var(--pd-line); }
        .pd-modal .modal-title { font-weight: 800; }
        .pd-modal .modal-body { padding: 1.25rem; }
        .pd-modal .modal-footer { gap: .5rem; padding: 1rem 1.25rem; border-top: 1px solid var(--pd-line); }

        .pd-label {
            display: block;
            margin-bottom: .4rem;
            color: var(--pd-ink-2);
            font-size: .85rem;
            font-weight: 700;
        }
        .pd-input {
            min-height: 48px;
            border: 1px solid var(--pd-line);
            border-radius: 12px;
            font-size: 1rem;
        }
        .pd-input:focus {
            border-color: var(--pd-primary);
            box-shadow: 0 0 0 4px rgba(79, 70, 229, .12);
        }

        /* Responsive */
        @media (max-width: 575.98px) {
            .pd-header { align-items: stretch; }
            .pd-header__actions { width: 100%; }
            .pd-header__actions .btn { flex: 1 1 auto; justify-content: center; }
            .pd-stats { grid-template-columns: 1fr; }
            .pd-media { min-height: 220px; }
            .pd-info { padding: 1.1rem; }
        }
    </style>
</div>