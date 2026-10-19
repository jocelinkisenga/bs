<div class="page-wrapper usr-page">
    <div class="content">

        {{-- En-tête --}}
        <div class="usr-header">
            <div>
                <h4 class="usr-header__title">Personnel</h4>
                <p class="usr-header__sub">Gérez les comptes de votre équipe</p>
            </div>

            <button type="button" class="btn usr-btn-primary" wire:click="openCreate">
                <i class="bi bi-person-plus"></i>
                <span>Ajouter un membre</span>
            </button>
        </div>

        {{-- Indicateurs --}}
        <div class="usr-stats">
            <div class="usr-stat">
                <span class="usr-stat__icon"><i class="bi bi-people"></i></span>
                <div>
                    <span class="usr-stat__label">Total</span>
                    <span class="usr-stat__value">{{ $stats['total'] }}</span>
                </div>
            </div>
            <div class="usr-stat">
                <span class="usr-stat__icon usr-stat__icon--purple"><i class="bi bi-person-badge"></i></span>
                <div>
                    <span class="usr-stat__label">Gérants</span>
                    <span class="usr-stat__value">{{ $stats['gerants'] }}</span>
                </div>
            </div>
            <div class="usr-stat">
                <span class="usr-stat__icon usr-stat__icon--green"><i class="bi bi-cup-hot"></i></span>
                <div>
                    <span class="usr-stat__label">Serveurs</span>
                    <span class="usr-stat__value">{{ $stats['serveurs'] }}</span>
                </div>
            </div>
        </div>

        {{-- Messages --}}
        @if (session('message'))
            <div class="usr-alert usr-alert--success" role="alert">
                <i class="bi bi-check-circle-fill"></i>
                <span>{{ session('message') }}</span>
            </div>
        @endif

        @if (session('error'))
            <div class="usr-alert usr-alert--danger" role="alert">
                <i class="bi bi-exclamation-octagon-fill"></i>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        <div class="usr-card">

            {{-- Barre d'outils --}}
            <div class="usr-toolbar">
                <label class="usr-search">
                    <i class="bi bi-search"></i>
                    <input type="search"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Rechercher par nom, e-mail ou téléphone…"
                        autocomplete="off">
                </label>
            </div>

            {{-- Tableau --}}
            <div class="table-responsive">
                <table class="table usr-table align-middle">
                    <thead>
                        <tr>
                            <th>Membre</th>
                            <th>Contact</th>
                            <th>Rôle</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($personnel as $item)
                            @php
                                $estMoi = $item->id === auth()->id();
                                $niveau = match ((int) $item->role_id) {
                                    \App\Enums\RoleEnum::GERANT => 'purple',
                                    \App\Enums\RoleEnum::SERVER => 'green',
                                    \App\Enums\RoleEnum::RECEPTEUR => 'amber',
                                    default => 'slate',
                                };
                            @endphp

                            <tr wire:key="user-{{ $item->id }}">
                                <td>
                                    <div class="usr-person">
                                        <span class="usr-avatar">
                                            {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($item->name, 0, 1)) }}
                                        </span>
                                        <div>
                                            <span class="usr-name">
                                                {{ $item->name }}
                                                @if ($estMoi)
                                                    <span class="usr-me">Vous</span>
                                                @endif
                                            </span>
                                            <span class="usr-sub">
                                                {{ $sexes[$item->sexe] ?? '' }}
                                            </span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="usr-contact">
                                        <span><i class="bi bi-telephone"></i> {{ $item->phone ?: '—' }}</span>
                                        @if ($item->email)
                                            <span><i class="bi bi-envelope"></i> {{ $item->email }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <span class="usr-badge usr-badge--{{ $niveau }}">
                                        {{ $roles[$item->role_id] ?? 'Administrateur' }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <div class="usr-actions">
                                        @if (\Illuminate\Support\Facades\Route::has('user-detail'))
                                            <a class="usr-action" title="Détails"
                                                href="{{ route('user-detail', ['id' => $item->id]) }}">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        @endif

                                        <button type="button" class="usr-action" title="Modifier"
                                            wire:click="edit({{ $item->id }})"
                                            wire:loading.attr="disabled">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>

                                        @unless ($estMoi)
                                            <button type="button" class="usr-action usr-action--danger" title="Supprimer"
                                                data-id="{{ $item->id }}"
                                                data-name="{{ $item->name }}"
                                                onclick="confirmerSuppressionUser(this)">
                                                <i class="bi bi-trash3"></i>
                                            </button>
                                        @endunless
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="usr-empty">
                                    <i class="bi bi-people"></i>
                                    <p>Aucun membre trouvé.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($personnel->hasPages())
                <div class="usr-pagination">
                    {{ $personnel->links('livewire::bootstrap') }}
                </div>
            @endif
        </div>
    </div>

    {{-- Modal création / modification --}}
    <div wire:ignore.self class="modal fade" id="userModal" tabindex="-1"
        aria-labelledby="userModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content usr-modal">
                <form wire:submit="save" novalidate>

                    <div class="modal-header">
                        <h5 class="modal-title" id="userModalLabel">
                            {{ $userId ? 'Modifier le membre' : 'Nouveau membre' }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>

                    <div class="modal-body">
                        <div class="row g-3">

                            <div class="col-12 col-md-6">
                                <label for="usr-name" class="usr-label">Nom complet</label>
                                <input id="usr-name" type="text" wire:model="name" maxlength="100"
                                    placeholder="Ex. Jean Mbuyi"
                                    class="form-control usr-input @error('name') is-invalid @enderror">
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="usr-role" class="usr-label">Rôle</label>
                                <select id="usr-role" wire:model="role_id"
                                    class="form-select usr-input @error('role_id') is-invalid @enderror">
                                    <option value="">Sélectionner un rôle</option>
                                    @foreach ($roles as $valeur => $libelle)
                                        <option value="{{ $valeur }}">{{ $libelle }}</option>
                                    @endforeach
                                </select>
                                @error('role_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="usr-phone" class="usr-label">Téléphone</label>
                                <input id="usr-phone" type="tel" inputmode="tel" wire:model="phone"
                                    placeholder="+243 800 000 000"
                                    class="form-control usr-input @error('phone') is-invalid @enderror">
                                @error('phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="usr-sexe" class="usr-label">Sexe</label>
                                <select id="usr-sexe" wire:model="sexe"
                                    class="form-select usr-input @error('sexe') is-invalid @enderror">
                                    <option value="">Sélectionner</option>
                                    @foreach ($sexes as $valeur => $libelle)
                                        <option value="{{ $valeur }}">{{ $libelle }}</option>
                                    @endforeach
                                </select>
                                @error('sexe')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="usr-email" class="usr-label">
                                    E-mail <span class="usr-optional">(facultatif)</span>
                                </label>
                                <input id="usr-email" type="email" wire:model="email" autocomplete="off"
                                    placeholder="nom@exemple.com"
                                    class="form-control usr-input @error('email') is-invalid @enderror">
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="usr-password" class="usr-label">
                                    Mot de passe
                                    @if ($userId)
                                        <span class="usr-optional">(laisser vide pour ne pas changer)</span>
                                    @endif
                                </label>
                                <input id="usr-password" type="password" wire:model="password"
                                    autocomplete="new-password"
                                    placeholder="6 caractères minimum"
                                    class="form-control usr-input @error('password') is-invalid @enderror">
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn usr-btn-ghost" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn usr-btn-primary" wire:loading.attr="disabled" wire:target="save">
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
        .usr-page {
            --usr-primary: #4f46e5;
            --usr-primary-hover: #4338ca;
            --usr-primary-soft: #eef2ff;
            --usr-ink: #0f172a;
            --usr-ink-2: #475569;
            --usr-ink-3: #94a3b8;
            --usr-line: #e2e8f0;
            --usr-danger: #dc2626;
            --usr-danger-soft: #fee2e2;
            --usr-success: #15803d;
            --usr-success-soft: #dcfce7;
            --usr-warning: #b45309;
            --usr-warning-soft: #fef3c7;
            --usr-purple: #7c3aed;
            --usr-purple-soft: #ede9fe;

            font-family: "Nunito", system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            color: var(--usr-ink);
        }

        /* En-tête */
        .usr-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1.25rem;
        }
        .usr-header__title { margin: 0; font-size: 1.5rem; font-weight: 800; letter-spacing: -.02em; }
        .usr-header__sub { margin: .15rem 0 0; color: var(--usr-ink-2); font-size: .9rem; }

        /* Boutons */
        .usr-btn-primary {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            height: 44px;
            padding: 0 1.1rem;
            border: 0;
            border-radius: 12px;
            background: var(--usr-primary);
            color: #fff;
            font-weight: 700;
            box-shadow: 0 8px 18px rgba(79, 70, 229, .25);
            transition: background .15s ease, transform .15s ease;
        }
        .usr-btn-primary:hover { background: var(--usr-primary-hover); color: #fff; transform: translateY(-1px); }
        .usr-btn-primary:disabled { opacity: .7; }
        .usr-btn-ghost {
            height: 44px;
            padding: 0 1.1rem;
            border: 1px solid var(--usr-line);
            border-radius: 12px;
            background: #fff;
            color: var(--usr-ink-2);
            font-weight: 700;
        }
        .usr-btn-ghost:hover { background: #f8fafc; color: var(--usr-ink); }

        /* Indicateurs */
        .usr-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: .75rem;
            margin-bottom: 1.25rem;
        }
        .usr-stat {
            display: flex;
            align-items: center;
            gap: .85rem;
            padding: 1rem 1.1rem;
            background: #fff;
            border: 1px solid var(--usr-line);
            border-radius: 16px;
            box-shadow: 0 1px 2px rgba(15, 23, 42, .05);
        }
        .usr-stat__icon {
            display: grid;
            place-items: center;
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: var(--usr-primary-soft);
            color: var(--usr-primary);
            font-size: 1.2rem;
        }
        .usr-stat__icon--purple { background: var(--usr-purple-soft); color: var(--usr-purple); }
        .usr-stat__icon--green  { background: var(--usr-success-soft); color: var(--usr-success); }
        .usr-stat__label {
            display: block;
            color: var(--usr-ink-3);
            font-size: .72rem;
            font-weight: 800;
            letter-spacing: .05em;
            text-transform: uppercase;
        }
        .usr-stat__value {
            display: block;
            font-size: 1.35rem;
            font-weight: 800;
            font-variant-numeric: tabular-nums;
        }

        /* Alertes */
        .usr-alert {
            display: flex;
            align-items: center;
            gap: .6rem;
            margin-bottom: 1rem;
            padding: .8rem 1rem;
            border-radius: 12px;
            font-weight: 600;
        }
        .usr-alert--success { background: var(--usr-success-soft); color: var(--usr-success); }
        .usr-alert--danger  { background: var(--usr-danger-soft);  color: var(--usr-danger); }

        /* Carte */
        .usr-card {
            background: #fff;
            border: 1px solid var(--usr-line);
            border-radius: 16px;
            box-shadow: 0 1px 2px rgba(15, 23, 42, .05);
            overflow: hidden;
        }
        .usr-toolbar { padding: 1rem 1.25rem; border-bottom: 1px solid var(--usr-line); }
        .usr-search { position: relative; display: block; max-width: 420px; margin: 0; }
        .usr-search i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--usr-ink-3);
            pointer-events: none;
        }
        .usr-search input {
            width: 100%;
            height: 44px;
            padding: 0 1rem 0 2.6rem;
            border: 1px solid var(--usr-line);
            border-radius: 12px;
            background: #f8fafc;
            font: inherit;
            color: var(--usr-ink);
            transition: border-color .15s ease, box-shadow .15s ease, background .15s ease;
        }
        .usr-search input:focus {
            outline: 0;
            background: #fff;
            border-color: var(--usr-primary);
            box-shadow: 0 0 0 4px rgba(79, 70, 229, .12);
        }

        /* Tableau */
        .usr-table { margin: 0; }
        .usr-table thead th {
            padding: .85rem 1.25rem;
            background: #f8fafc;
            border-bottom: 1px solid var(--usr-line);
            color: var(--usr-ink-2);
            font-size: .72rem;
            font-weight: 800;
            letter-spacing: .06em;
            text-transform: uppercase;
            white-space: nowrap;
        }
        .usr-table tbody td {
            padding: .9rem 1.25rem;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
        }
        .usr-table tbody tr:last-child td { border-bottom: 0; }
        .usr-table tbody tr:hover td { background: #fafbff; }

        .usr-person { display: flex; align-items: center; gap: .75rem; }
        .usr-avatar {
            display: grid;
            place-items: center;
            width: 40px;
            height: 40px;
            border-radius: 12px;
            background: var(--usr-primary-soft);
            color: var(--usr-primary);
            font-weight: 800;
        }
        .usr-name { display: block; font-weight: 700; }
        .usr-sub { display: block; color: var(--usr-ink-3); font-size: .8rem; font-weight: 600; }
        .usr-me {
            margin-left: .35rem;
            padding: .1rem .5rem;
            border-radius: 999px;
            background: var(--usr-primary-soft);
            color: var(--usr-primary);
            font-size: .7rem;
            font-weight: 800;
            vertical-align: middle;
        }

        .usr-contact { display: flex; flex-direction: column; gap: .2rem; color: var(--usr-ink-2); font-size: .9rem; }
        .usr-contact i { color: var(--usr-ink-3); }

        .usr-badge {
            display: inline-block;
            padding: .25rem .7rem;
            border-radius: 999px;
            font-size: .78rem;
            font-weight: 800;
            white-space: nowrap;
        }
        .usr-badge--purple { background: var(--usr-purple-soft);  color: var(--usr-purple); }
        .usr-badge--green  { background: var(--usr-success-soft); color: var(--usr-success); }
        .usr-badge--amber  { background: var(--usr-warning-soft); color: var(--usr-warning); }
        .usr-badge--slate  { background: #f1f5f9; color: var(--usr-ink-2); }

        .usr-actions { display: inline-flex; gap: .4rem; }
        .usr-action {
            display: grid;
            place-items: center;
            width: 36px;
            height: 36px;
            border: 0;
            border-radius: 10px;
            background: var(--usr-primary-soft);
            color: var(--usr-primary);
            font-size: .95rem;
            text-decoration: none;
            transition: transform .15s ease;
        }
        .usr-action:hover { transform: translateY(-1px); color: var(--usr-primary); }
        .usr-action--danger { background: var(--usr-danger-soft); color: var(--usr-danger); }
        .usr-action--danger:hover { color: var(--usr-danger); }
        .usr-action:focus-visible { outline: 3px solid rgba(79, 70, 229, .35); outline-offset: 2px; }

        .usr-empty { padding: 2.5rem 1rem !important; text-align: center; color: var(--usr-ink-3); }
        .usr-empty i { font-size: 2rem; }
        .usr-empty p { margin: .5rem 0 0; font-weight: 600; }

        .usr-pagination { padding: 1rem 1.25rem; border-top: 1px solid var(--usr-line); }

        /* Modal */
        .usr-modal {
            border: 0;
            border-radius: 16px;
            box-shadow: 0 25px 60px rgba(15, 23, 42, .25);
            font-family: "Nunito", system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
        }
        .usr-modal .modal-header { padding: 1.1rem 1.25rem; border-bottom: 1px solid var(--usr-line); }
        .usr-modal .modal-title { font-weight: 800; }
        .usr-modal .modal-body { padding: 1.25rem; }
        .usr-modal .modal-footer { gap: .5rem; padding: 1rem 1.25rem; border-top: 1px solid var(--usr-line); }

        .usr-label {
            display: block;
            margin-bottom: .4rem;
            color: var(--usr-ink-2);
            font-size: .85rem;
            font-weight: 700;
        }
        .usr-optional { color: var(--usr-ink-3); font-weight: 600; }
        .usr-input {
            min-height: 46px;
            border: 1px solid var(--usr-line);
            border-radius: 12px;
            font-size: 1rem;
        }
        .usr-input:focus {
            border-color: var(--usr-primary);
            box-shadow: 0 0 0 4px rgba(79, 70, 229, .12);
        }

        /* Responsive */
        @media (max-width: 767.98px) {
            .usr-table thead th:nth-child(2),
            .usr-table tbody td:nth-child(2) { display: none; }
        }
        @media (max-width: 575.98px) {
            .usr-header { align-items: stretch; }
            .usr-btn-primary { justify-content: center; }
            .usr-search { max-width: none; }
            .usr-table thead th,
            .usr-table tbody td { padding: .75rem .8rem; }
        }
    </style>
</div>