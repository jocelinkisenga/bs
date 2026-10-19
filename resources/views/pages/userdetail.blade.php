@extends('layouts.app')

@section('content')
@php
    use App\Enums\RoleEnum;
    use Illuminate\Support\Str;

    // Mêmes valeurs que la liste et la création du personnel
    $roleOptions = [
        RoleEnum::GERANT    => 'Gérant',
        RoleEnum::RECEPTEUR => 'Comptoiriste',
        RoleEnum::SERVER    => 'Serveur',
    ];
    $sexOptions = ['homme' => 'Homme', 'femme' => 'Femme'];

    $roleId    = (int) $data->role_id;
    $roleLabel = match ($roleId) {
        RoleEnum::ADMIN     => 'Administrateur',
        RoleEnum::GERANT    => 'Gérant',
        RoleEnum::RECEPTEUR => 'Comptoiriste',
        RoleEnum::SERVER    => 'Serveur',
        default             => 'Non défini',
    };
    $roleClass = match ($roleId) {
        RoleEnum::GERANT => 'purple',
        RoleEnum::SERVER => 'green',
        RoleEnum::RECEPTEUR => 'amber',
        default => 'slate',
    };

    $estServeur   = $roleId === RoleEnum::SERVER;
    $estMoi       = (int) $data->id === (int) auth()->id();
    $initiale     = Str::upper(Str::substr($data->name, 0, 1));
    $precommandes = collect($precommandes ?? []);
    $fmt = fn ($n) => rtrim(rtrim(number_format((float) $n, 2, ',', ' '), '0'), ',');
@endphp

<div class="page-wrapper usd-page">
    <div class="content">

        {{-- En-tête --}}
        <div class="usd-header">
            <div>
                <a href="{{ route('users') }}" class="usd-back">
                    <i class="bi bi-arrow-left"></i>
                    <span>Retour au personnel</span>
                </a>
                <h4 class="usd-header__title">{{ $data->name }}</h4>
            </div>

            <button type="button" class="btn usd-btn-primary" data-bs-toggle="modal" data-bs-target="#detailModal">
                <i class="bi bi-pencil-square"></i>
                <span>Modifier</span>
            </button>
        </div>

        {{-- Messages --}}
        @if (session('message'))
            <div class="usd-alert usd-alert--success" role="alert">
                <i class="bi bi-check-circle-fill"></i>
                <span>{{ session('message') }}</span>
            </div>
        @endif

        @if ($errors->any())
            <div class="usd-alert usd-alert--danger" role="alert">
                <i class="bi bi-exclamation-octagon-fill"></i>
                <span>Certains champs sont invalides. Corrigez-les dans la fenêtre de modification.</span>
            </div>
        @endif

        <div class="row g-4">

            {{-- Carte profil --}}
            <div class="col-12 col-lg-4">
                <div class="usd-card usd-profile">
                    <span class="usd-avatar">{{ $initiale }}</span>
                    <h5 class="usd-profile__name">{{ $data->name }}</h5>
                    <span class="usd-badge usd-badge--{{ $roleClass }}">{{ $roleLabel }}</span>
                    @if ($estMoi)
                        <span class="usd-me">C'est vous</span>
                    @endif
                </div>
            </div>

            {{-- Informations --}}
            <div class="col-12 col-lg-8">
                <div class="usd-card usd-info">
                    <dl class="usd-dl">
                        <div>
                            <dt>Nom</dt>
                            <dd>{{ $data->name }}</dd>
                        </div>
                        <div>
                            <dt>Téléphone</dt>
                            <dd>{{ $data->phone ?: '—' }}</dd>
                        </div>
                        <div>
                            <dt>E-mail</dt>
                            <dd>{{ $data->email ?: '—' }}</dd>
                        </div>
                        <div>
                            <dt>Sexe</dt>
                            <dd>{{ $sexOptions[$data->sexe] ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt>Rôle</dt>
                            <dd>{{ $roleLabel }}</dd>
                        </div>
                        <div>
                            <dt>Membre depuis</dt>
                            <dd>{{ $data->created_at?->format('d/m/Y') ?? '—' }}</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>

        {{-- Commandes (serveurs uniquement) --}}
        @if ($estServeur)
            <div class="usd-card mt-4">
                <div class="usd-card__head">
                    <h5>Commandes du serveur</h5>
                    <span class="usd-muted">{{ $precommandes->count() }} ligne(s)</span>
                </div>

                <div class="table-responsive">
                    <table class="table usd-table">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th class="text-center">Qté</th>
                                <th class="text-end">Sous-total</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($precommandes as $item)
                                @php
                                    $qte   = (int) ($item->quantity_commande ?? 0);
                                    $prix  = (float) ($item->produit?->price ?? 0);
                                    $date  = $item->precommande?->created_at ?? $item->created_at;
                                @endphp
                                <tr>
                                    <td class="usd-code">{{ $item->code ?? $item->precommande?->code ?? '—' }}</td>
                                    <td class="text-center">{{ $qte }}</td>
                                    <td class="text-end">{{ $fmt($qte * $prix) }} Fc</td>
                                    <td class="usd-muted">{{ $date?->format('d/m/Y H:i') ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="usd-empty">
                                        <i class="bi bi-receipt"></i>
                                        <p>Aucune commande pour ce serveur.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>

    {{-- Modal de modification (formulaire classique, pas Livewire) --}}
    <div class="modal fade" id="detailModal" tabindex="-1"
        aria-labelledby="detailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content usd-modal">
                <form action="{{ route('update.user') }}" method="POST" novalidate>
                    @csrf
                    <input type="hidden" name="user_id" value="{{ $data->id }}">

                    <div class="modal-header">
                        <h5 class="modal-title" id="detailModalLabel">Modifier le membre</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>

                    <div class="modal-body">
                        <div class="row g-3">

                            <div class="col-12 col-md-6">
                                <label for="usd-name" class="usd-label">Nom complet</label>
                                <input id="usd-name" type="text" name="name" maxlength="100"
                                    value="{{ old('name', $data->name) }}"
                                    class="form-control usd-input @error('name') is-invalid @enderror">
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="usd-role" class="usd-label">Rôle</label>
                                @if ($estMoi)
                                    {{-- Un utilisateur ne modifie pas son propre rôle --}}
                                    <input type="hidden" name="role_id" value="{{ $data->role_id }}">
                                    <select id="usd-role" class="form-select usd-input" disabled>
                                        <option>{{ $roleLabel }}</option>
                                    </select>
                                    <div class="usd-hint">Vous ne pouvez pas changer votre propre rôle.</div>
                                @else
                                    <select id="usd-role" name="role_id"
                                        class="form-select usd-input @error('role_id') is-invalid @enderror">
                                        <option value="">Sélectionner un rôle</option>
                                        @foreach ($roleOptions as $valeur => $libelle)
                                            <option value="{{ $valeur }}" @selected((string) old('role_id', $data->role_id) === (string) $valeur)>
                                                {{ $libelle }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('role_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                @endif
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="usd-phone" class="usd-label">Téléphone</label>
                                <input id="usd-phone" type="tel" name="phone" inputmode="tel"
                                    value="{{ old('phone', $data->phone) }}"
                                    placeholder="+243 800 000 000"
                                    class="form-control usd-input @error('phone') is-invalid @enderror">
                                @error('phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="usd-sexe" class="usd-label">Sexe</label>
                                <select id="usd-sexe" name="sexe"
                                    class="form-select usd-input @error('sexe') is-invalid @enderror">
                                    <option value="">Sélectionner</option>
                                    @foreach ($sexOptions as $valeur => $libelle)
                                        <option value="{{ $valeur }}" @selected(old('sexe', $data->sexe) === $valeur)>
                                            {{ $libelle }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('sexe')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="usd-email" class="usd-label">
                                    E-mail <span class="usd-optional">(facultatif)</span>
                                </label>
                                <input id="usd-email" type="email" name="email" autocomplete="off"
                                    value="{{ old('email', $data->email) }}"
                                    placeholder="nom@exemple.com"
                                    class="form-control usd-input @error('email') is-invalid @enderror">
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="usd-password" class="usd-label">
                                    Nouveau mot de passe <span class="usd-optional">(facultatif)</span>
                                </label>
                                <input id="usd-password" type="password" name="password" autocomplete="new-password"
                                    placeholder="Laisser vide pour ne pas changer"
                                    class="form-control usd-input @error('password') is-invalid @enderror">
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn usd-btn-ghost" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn usd-btn-primary">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Réouvre la fenêtre si la validation échoue --}}
    @if ($errors->any())
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var el = document.getElementById('detailModal');
                if (el) bootstrap.Modal.getOrCreateInstance(el).show();
            });
        </script>
    @endif

    <style>
        .usd-page {
            --usd-primary: #4f46e5;
            --usd-primary-hover: #4338ca;
            --usd-primary-soft: #eef2ff;
            --usd-ink: #0f172a;
            --usd-ink-2: #475569;
            --usd-ink-3: #94a3b8;
            --usd-line: #e2e8f0;
            --usd-danger: #dc2626;
            --usd-danger-soft: #fee2e2;
            --usd-success: #15803d;
            --usd-success-soft: #dcfce7;
            --usd-warning: #b45309;
            --usd-warning-soft: #fef3c7;
            --usd-purple: #7c3aed;
            --usd-purple-soft: #ede9fe;

            font-family: "Nunito", system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            color: var(--usd-ink);
        }

        /* En-tête */
        .usd-header {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1.25rem;
        }
        .usd-back {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            margin-bottom: .4rem;
            color: var(--usd-ink-2);
            font-size: .88rem;
            font-weight: 700;
            text-decoration: none;
        }
        .usd-back:hover { color: var(--usd-primary); }
        .usd-header__title { margin: 0; font-size: 1.5rem; font-weight: 800; letter-spacing: -.02em; }

        /* Boutons */
        .usd-btn-primary {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            height: 44px;
            padding: 0 1.1rem;
            border: 0;
            border-radius: 12px;
            background: var(--usd-primary);
            color: #fff;
            font-weight: 700;
            box-shadow: 0 8px 18px rgba(79, 70, 229, .25);
            transition: background .15s ease, transform .15s ease;
        }
        .usd-btn-primary:hover { background: var(--usd-primary-hover); color: #fff; transform: translateY(-1px); }
        .usd-btn-ghost {
            height: 44px;
            padding: 0 1.1rem;
            border: 1px solid var(--usd-line);
            border-radius: 12px;
            background: #fff;
            color: var(--usd-ink-2);
            font-weight: 700;
        }
        .usd-btn-ghost:hover { background: #f8fafc; color: var(--usd-ink); }

        /* Alertes */
        .usd-alert {
            display: flex;
            align-items: center;
            gap: .6rem;
            margin-bottom: 1.25rem;
            padding: .8rem 1rem;
            border-radius: 12px;
            font-weight: 600;
        }
        .usd-alert--success { background: var(--usd-success-soft); color: var(--usd-success); }
        .usd-alert--danger  { background: var(--usd-danger-soft);  color: var(--usd-danger); }

        /* Cartes */
        .usd-card {
            height: 100%;
            background: #fff;
            border: 1px solid var(--usd-line);
            border-radius: 16px;
            box-shadow: 0 1px 2px rgba(15, 23, 42, .05);
            overflow: hidden;
        }
        .usd-card__head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: .5rem;
            padding: 1rem 1.25rem;
            border-bottom: 1px solid var(--usd-line);
        }
        .usd-card__head h5 { margin: 0; font-size: 1rem; font-weight: 800; }
        .usd-muted { color: var(--usd-ink-3); font-size: .85rem; font-weight: 600; }

        /* Profil */
        .usd-profile {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: .6rem;
            padding: 2rem 1.5rem;
            text-align: center;
        }
        .usd-avatar {
            display: grid;
            place-items: center;
            width: 84px;
            height: 84px;
            margin-bottom: .4rem;
            border-radius: 24px;
            background: var(--usd-primary-soft);
            color: var(--usd-primary);
            font-size: 2rem;
            font-weight: 800;
        }
        .usd-profile__name { margin: 0; font-size: 1.15rem; font-weight: 800; }
        .usd-me {
            padding: .2rem .65rem;
            border-radius: 999px;
            background: var(--usd-primary-soft);
            color: var(--usd-primary);
            font-size: .75rem;
            font-weight: 800;
        }

        .usd-badge {
            display: inline-block;
            padding: .25rem .75rem;
            border-radius: 999px;
            font-size: .8rem;
            font-weight: 800;
        }
        .usd-badge--purple { background: var(--usd-purple-soft);  color: var(--usd-purple); }
        .usd-badge--green  { background: var(--usd-success-soft); color: var(--usd-success); }
        .usd-badge--amber  { background: var(--usd-warning-soft); color: var(--usd-warning); }
        .usd-badge--slate  { background: #f1f5f9; color: var(--usd-ink-2); }

        /* Informations */
        .usd-info { padding: 1.5rem; }
        .usd-dl {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
            gap: 1.1rem 1.5rem;
            margin: 0;
        }
        .usd-dl dt {
            color: var(--usd-ink-3);
            font-size: .72rem;
            font-weight: 800;
            letter-spacing: .05em;
            text-transform: uppercase;
        }
        .usd-dl dd {
            margin: .25rem 0 0;
            font-weight: 700;
            overflow-wrap: anywhere;
        }

        /* Tableau commandes */
        .usd-table { margin: 0; }
        .usd-table thead th {
            padding: .85rem 1.25rem;
            background: #f8fafc;
            border-bottom: 1px solid var(--usd-line);
            color: var(--usd-ink-2);
            font-size: .72rem;
            font-weight: 800;
            letter-spacing: .06em;
            text-transform: uppercase;
            white-space: nowrap;
        }
        .usd-table tbody td {
            padding: .85rem 1.25rem;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
            font-variant-numeric: tabular-nums;
        }
        .usd-table tbody tr:last-child td { border-bottom: 0; }
        .usd-table tbody tr:hover td { background: #fafbff; }
        .usd-code { font-weight: 800; }

        .usd-empty { padding: 2.5rem 1rem !important; text-align: center; color: var(--usd-ink-3); }
        .usd-empty i { font-size: 2rem; }
        .usd-empty p { margin: .5rem 0 0; font-weight: 600; }

        /* Modal */
        .usd-modal {
            border: 0;
            border-radius: 16px;
            box-shadow: 0 25px 60px rgba(15, 23, 42, .25);
            font-family: "Nunito", system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
        }
        .usd-modal .modal-header { padding: 1.1rem 1.25rem; border-bottom: 1px solid var(--usd-line); }
        .usd-modal .modal-title { font-weight: 800; }
        .usd-modal .modal-body { padding: 1.25rem; }
        .usd-modal .modal-footer { gap: .5rem; padding: 1rem 1.25rem; border-top: 1px solid var(--usd-line); }

        .usd-label {
            display: block;
            margin-bottom: .4rem;
            color: var(--usd-ink-2);
            font-size: .85rem;
            font-weight: 700;
        }
        .usd-optional { color: var(--usd-ink-3); font-weight: 600; }
        .usd-hint { margin-top: .4rem; color: var(--usd-ink-3); font-size: .82rem; }
        .usd-input {
            min-height: 46px;
            border: 1px solid var(--usd-line);
            border-radius: 12px;
            font-size: 1rem;
        }
        .usd-input:focus {
            border-color: var(--usd-primary);
            box-shadow: 0 0 0 4px rgba(79, 70, 229, .12);
        }

        /* Responsive */
        @media (max-width: 575.98px) {
            .usd-header { align-items: stretch; }
            .usd-btn-primary { justify-content: center; }
            .usd-info { padding: 1.1rem; }
            .usd-table thead th,
            .usd-table tbody td { padding: .75rem .8rem; }
        }
    </style>
</div>
@endsection