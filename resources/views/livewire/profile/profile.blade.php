<div class="page-wrapper pf">
    <div class="content">

        @if ($user)

            {{-- En-tête --}}
            <div class="pf-header">
                <span class="pf-avatar">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</span>
                <div>
                    <h4 class="pf-header__name">{{ $user->name }}</h4>
                    <p class="pf-header__meta">{{ $roleLabel }} · membre depuis le {{ $membreDepuis }}</p>
                </div>
            </div>

            <div class="pf-layout">

                {{-- Navigation des sections --}}
                <nav class="pf-nav" aria-label="Sections du profil">
                    <button type="button" wire:click="changerOnglet('infos')"
                        class="pf-nav__item {{ $onglet === 'infos' ? 'is-active' : '' }}">
                        <i class="bi bi-person"></i><span>Informations</span>
                    </button>
                    <button type="button" wire:click="changerOnglet('email')"
                        class="pf-nav__item {{ $onglet === 'email' ? 'is-active' : '' }}">
                        <i class="bi bi-envelope"></i><span>Adresse e-mail</span>
                    </button>
                    <button type="button" wire:click="changerOnglet('mot_de_passe')"
                        class="pf-nav__item {{ $onglet === 'mot_de_passe' ? 'is-active' : '' }}">
                        <i class="bi bi-shield-lock"></i><span>Mot de passe</span>
                    </button>
                    <button type="button" wire:click="changerOnglet('suppression')"
                        class="pf-nav__item pf-nav__item--danger {{ $onglet === 'suppression' ? 'is-active' : '' }}">
                        <i class="bi bi-trash3"></i><span>Supprimer mon compte</span>
                    </button>
                </nav>

                {{-- Contenu --}}
                <div class="pf-main">

                    @if ($succes)
                        <div class="pf-alert pf-alert--success" role="alert">
                            <i class="bi bi-check-circle-fill"></i>
                            <span>{{ $succes }}</span>
                        </div>
                    @endif

                    {{-- Informations --}}
                    @if ($onglet === 'infos')
                        <section class="pf-card">
                            <div class="pf-card__head">
                                <h5>Informations personnelles</h5>
                                <p>Ces informations apparaissent dans la liste du personnel.</p>
                            </div>

                            <form wire:submit="sauverInfos" novalidate>
                                <div class="pf-grid">
                                    <div class="pf-field">
                                        <label for="pf-name">Nom complet</label>
                                        <input id="pf-name" type="text" wire:model="name" maxlength="100"
                                            class="pf-input @error('name') is-invalid @enderror">
                                        @error('name') <small class="pf-error">{{ $message }}</small> @enderror
                                    </div>

                                    <div class="pf-field">
                                        <label for="pf-phone">Téléphone</label>
                                        <input id="pf-phone" type="tel" inputmode="tel" wire:model="phone"
                                            placeholder="+243 800 000 000"
                                            class="pf-input @error('phone') is-invalid @enderror">
                                        @error('phone') <small class="pf-error">{{ $message }}</small> @enderror
                                    </div>

                                    <div class="pf-field">
                                        <label for="pf-sexe">Sexe</label>
                                        <select id="pf-sexe" wire:model="sexe"
                                            class="pf-input @error('sexe') is-invalid @enderror">
                                            <option value="">Sélectionner</option>
                                            <option value="homme">Homme</option>
                                            <option value="femme">Femme</option>
                                        </select>
                                        @error('sexe') <small class="pf-error">{{ $message }}</small> @enderror
                                    </div>
                                </div>

                                <div class="pf-actions">
                                    <button type="submit" class="pf-btn pf-btn--primary" wire:loading.attr="disabled" wire:target="sauverInfos">
                                        <span wire:loading.remove wire:target="sauverInfos">Enregistrer</span>
                                        <span wire:loading wire:target="sauverInfos" class="spinner-border spinner-border-sm" aria-hidden="true"></span>
                                    </button>
                                </div>
                            </form>
                        </section>
                    @endif

                    {{-- E-mail --}}
                    @if ($onglet === 'email')
                        <section class="pf-card">
                            <div class="pf-card__head">
                                <h5>Adresse e-mail</h5>
                                <p>Votre adresse sert à vous connecter. Pour la modifier, confirmez avec votre mot de passe.</p>
                            </div>

                            <div class="pf-current">
                                <span>Adresse actuelle</span>
                                <strong>{{ $user->email ?: 'Aucune adresse enregistrée' }}</strong>
                            </div>

                            <form wire:submit="sauverEmail" novalidate>
                                <div class="pf-grid">
                                    <div class="pf-field">
                                        <label for="pf-email">Nouvelle adresse</label>
                                        <input id="pf-email" type="email" wire:model="email" autocomplete="off"
                                            placeholder="nom@exemple.com"
                                            class="pf-input @error('email') is-invalid @enderror">
                                        @error('email') <small class="pf-error">{{ $message }}</small> @enderror
                                    </div>

                                    <div class="pf-field">
                                        <label for="pf-email-pwd">Mot de passe actuel</label>
                                        <input id="pf-email-pwd" type="password" wire:model="email_password" autocomplete="current-password"
                                            class="pf-input @error('email_password') is-invalid @enderror">
                                        @error('email_password') <small class="pf-error">{{ $message }}</small> @enderror
                                    </div>
                                </div>

                                <div class="pf-actions">
                                    <button type="submit" class="pf-btn pf-btn--primary" wire:loading.attr="disabled" wire:target="sauverEmail">
                                        <span wire:loading.remove wire:target="sauverEmail">Mettre à jour l'e-mail</span>
                                        <span wire:loading wire:target="sauverEmail" class="spinner-border spinner-border-sm" aria-hidden="true"></span>
                                    </button>
                                </div>
                            </form>
                        </section>
                    @endif

                    {{-- Mot de passe --}}
                    @if ($onglet === 'mot_de_passe')
                        <section class="pf-card">
                            <div class="pf-card__head">
                                <h5>Mot de passe</h5>
                                <p>Au moins 8 caractères, avec des lettres et des chiffres. Les autres appareils seront déconnectés.</p>
                            </div>

                            <form wire:submit="sauverMotDePasse" novalidate>
                                <div class="pf-grid pf-grid--single">
                                    <div class="pf-field">
                                        <label for="pf-current">Mot de passe actuel</label>
                                        <input id="pf-current" type="password" wire:model="current_password" autocomplete="current-password"
                                            class="pf-input @error('current_password') is-invalid @enderror">
                                        @error('current_password') <small class="pf-error">{{ $message }}</small> @enderror
                                    </div>

                                    <div class="pf-field">
                                        <label for="pf-new">Nouveau mot de passe</label>
                                        <input id="pf-new" type="password" wire:model="new_password" autocomplete="new-password"
                                            class="pf-input @error('new_password') is-invalid @enderror">
                                        @error('new_password') <small class="pf-error">{{ $message }}</small> @enderror
                                    </div>

                                    <div class="pf-field">
                                        <label for="pf-confirm">Confirmer le nouveau mot de passe</label>
                                        <input id="pf-confirm" type="password" wire:model="new_password_confirmation" autocomplete="new-password"
                                            class="pf-input">
                                    </div>
                                </div>

                                <div class="pf-actions">
                                    <button type="submit" class="pf-btn pf-btn--primary" wire:loading.attr="disabled" wire:target="sauverMotDePasse">
                                        <span wire:loading.remove wire:target="sauverMotDePasse">Changer le mot de passe</span>
                                        <span wire:loading wire:target="sauverMotDePasse" class="spinner-border spinner-border-sm" aria-hidden="true"></span>
                                    </button>
                                </div>
                            </form>
                        </section>
                    @endif

                    {{-- Suppression --}}
                    @if ($onglet === 'suppression')
                        <section class="pf-card pf-card--danger">
                            <div class="pf-card__head">
                                <h5>Supprimer mon compte</h5>
                                <p>Cette action est définitive. Vous serez déconnecté immédiatement et ne pourrez plus accéder à l'application.</p>
                            </div>

                            <div class="pf-warning">
                                <i class="bi bi-exclamation-triangle-fill"></i>
                                <div>
                                    <strong>Votre historique est conservé</strong>
                                    <p>
                                        Si votre compte a déjà enregistré des commandes ou des dépenses, il est anonymisé
                                        au lieu d'être supprimé, pour garder les chiffres de l'entreprise cohérents.
                                    </p>
                                </div>
                            </div>

                            <form wire:submit="supprimerCompte" novalidate>
                                <div class="pf-grid pf-grid--single">
                                    <div class="pf-field">
                                        <label for="pf-delete-pwd">Mot de passe</label>
                                        <input id="pf-delete-pwd" type="password" wire:model="delete_password" autocomplete="current-password"
                                            class="pf-input @error('delete_password') is-invalid @enderror">
                                        @error('delete_password') <small class="pf-error">{{ $message }}</small> @enderror
                                    </div>
                                </div>

                                <label class="pf-check">
                                    <input type="checkbox" wire:model="delete_confirm">
                                    <span>Je comprends que la suppression de mon compte est définitive.</span>
                                </label>
                                @error('delete_confirm') <small class="pf-error d-block mb-2">{{ $message }}</small> @enderror

                                <div class="pf-actions">
                                    <button type="submit" class="pf-btn pf-btn--danger" wire:loading.attr="disabled" wire:target="supprimerCompte">
                                        <span wire:loading.remove wire:target="supprimerCompte">
                                            <i class="bi bi-trash3"></i> Supprimer définitivement
                                        </span>
                                        <span wire:loading wire:target="supprimerCompte" class="spinner-border spinner-border-sm" aria-hidden="true"></span>
                                    </button>
                                </div>
                            </form>
                        </section>
                    @endif

                </div>
            </div>

        @endif
    </div>

    <style>
        .pf {
            --pf-primary: #4f46e5;
            --pf-primary-hover: #4338ca;
            --pf-primary-soft: #eef2ff;
            --pf-ink: #0f172a;
            --pf-ink-2: #475569;
            --pf-ink-3: #94a3b8;
            --pf-line: #e2e8f0;
            --pf-danger: #dc2626;
            --pf-danger-hover: #b91c1c;
            --pf-danger-soft: #fee2e2;
            --pf-success: #15803d;
            --pf-success-soft: #dcfce7;
            --pf-warn-soft: #fff7ed;
            --pf-warn: #9a3412;

            font-family: "Nunito", system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            color: var(--pf-ink);
        }

        /* En-tête */
        .pf-header { display: flex; align-items: center; gap: 1rem; margin-bottom: 1.5rem; }
        .pf-avatar {
            display: grid; place-items: center; width: 64px; height: 64px; border-radius: 20px;
            background: var(--pf-primary-soft); color: var(--pf-primary); font-size: 1.7rem; font-weight: 800;
        }
        .pf-header__name { margin: 0; font-size: 1.4rem; font-weight: 800; letter-spacing: -.02em; }
        .pf-header__meta { margin: .15rem 0 0; color: var(--pf-ink-2); font-size: .9rem; }

        /* Mise en page */
        .pf-layout { display: grid; grid-template-columns: 240px 1fr; gap: 1.25rem; align-items: start; }

        .pf-nav {
            display: flex; flex-direction: column; gap: .3rem; padding: .6rem;
            background: #fff; border: 1px solid var(--pf-line); border-radius: 16px;
        }
        .pf-nav__item {
            display: flex; align-items: center; gap: .7rem; width: 100%; padding: .65rem .8rem;
            border: 0; border-radius: 12px; background: transparent; color: var(--pf-ink-2);
            font: inherit; font-weight: 700; text-align: left; transition: background .15s ease, color .15s ease;
        }
        .pf-nav__item i { font-size: 1.05rem; }
        .pf-nav__item:hover { background: var(--pf-primary-soft); color: var(--pf-primary); }
        .pf-nav__item.is-active { background: var(--pf-primary); color: #fff; box-shadow: 0 8px 18px rgba(79, 70, 229, .25); }
        .pf-nav__item--danger:hover { background: var(--pf-danger-soft); color: var(--pf-danger); }
        .pf-nav__item--danger.is-active { background: var(--pf-danger); box-shadow: 0 8px 18px rgba(220, 38, 38, .25); }

        .pf-main { min-width: 0; }

        /* Cartes */
        .pf-card {
            background: #fff; border: 1px solid var(--pf-line); border-radius: 16px;
            box-shadow: 0 1px 2px rgba(15, 23, 42, .05); padding: 1.5rem;
        }
        .pf-card--danger { border-color: #fecaca; }
        .pf-card__head { margin-bottom: 1.25rem; }
        .pf-card__head h5 { margin: 0; font-size: 1.05rem; font-weight: 800; }
        .pf-card__head p { margin: .3rem 0 0; color: var(--pf-ink-2); font-size: .88rem; line-height: 1.5; }

        .pf-current {
            display: flex; flex-direction: column; gap: .15rem; margin-bottom: 1.25rem; padding: .8rem 1rem;
            background: #f8fafc; border: 1px solid var(--pf-line); border-radius: 12px;
        }
        .pf-current span { color: var(--pf-ink-3); font-size: .72rem; font-weight: 800; letter-spacing: .05em; text-transform: uppercase; }
        .pf-current strong { font-weight: 700; overflow-wrap: anywhere; }

        /* Formulaires */
        .pf-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1rem 1.25rem; }
        .pf-grid--single { grid-template-columns: 1fr; }
        .pf-field { display: flex; flex-direction: column; gap: .35rem; }
        .pf-field label { color: var(--pf-ink-2); font-size: .85rem; font-weight: 700; }
        .pf-input {
            height: 46px; padding: 0 .9rem; border: 1px solid var(--pf-line); border-radius: 12px;
            background: #fff; font: inherit; font-size: .95rem; color: var(--pf-ink);
            transition: border-color .15s ease, box-shadow .15s ease;
        }
        .pf-input:focus { outline: 0; border-color: var(--pf-primary); box-shadow: 0 0 0 4px rgba(79, 70, 229, .12); }
        .pf-input.is-invalid { border-color: var(--pf-danger); background: #fff7f7; }
        .pf-error { color: var(--pf-danger); font-size: .82rem; font-weight: 600; }

        .pf-check {
            display: flex; align-items: flex-start; gap: .6rem; margin: 1.25rem 0 .5rem;
            color: var(--pf-ink-2); font-size: .9rem; font-weight: 600; cursor: pointer;
        }
        .pf-check input { margin-top: .25rem; width: 16px; height: 16px; accent-color: var(--pf-danger); }

        .pf-actions { display: flex; justify-content: flex-end; margin-top: 1.5rem; }

        .pf-btn {
            display: inline-flex; align-items: center; justify-content: center; gap: .5rem;
            min-width: 180px; height: 46px; padding: 0 1.25rem; border: 0; border-radius: 12px;
            font: inherit; font-weight: 800; color: #fff; cursor: pointer;
            transition: background .15s ease, transform .15s ease, box-shadow .15s ease;
        }
        .pf-btn:hover { transform: translateY(-1px); }
        .pf-btn:disabled { opacity: .7; cursor: wait; }
        .pf-btn--primary { background: var(--pf-primary); box-shadow: 0 10px 22px rgba(79, 70, 229, .25); }
        .pf-btn--primary:hover { background: var(--pf-primary-hover); }
        .pf-btn--danger { background: var(--pf-danger); box-shadow: 0 10px 22px rgba(220, 38, 38, .25); }
        .pf-btn--danger:hover { background: var(--pf-danger-hover); }

        /* Alertes */
        .pf-alert {
            display: flex; align-items: center; gap: .6rem; margin-bottom: 1rem; padding: .8rem 1rem;
            border-radius: 12px; font-weight: 600;
        }
        .pf-alert--success { background: var(--pf-success-soft); color: var(--pf-success); }

        .pf-warning {
            display: flex; gap: .75rem; padding: 1rem; margin-bottom: 1.25rem;
            background: var(--pf-warn-soft); border: 1px solid #fed7aa; border-radius: 12px; color: var(--pf-warn);
        }
        .pf-warning i { font-size: 1.2rem; margin-top: .1rem; }
        .pf-warning strong { display: block; font-weight: 800; }
        .pf-warning p { margin: .2rem 0 0; font-size: .88rem; line-height: 1.5; }

        /* Responsive */
        @media (max-width: 991.98px) {
            .pf-layout { grid-template-columns: 1fr; }
            .pf-nav { flex-direction: row; overflow-x: auto; scrollbar-width: none; }
            .pf-nav::-webkit-scrollbar { display: none; }
            .pf-nav__item { flex: 0 0 auto; white-space: nowrap; }
        }

        @media (max-width: 575.98px) {
            .pf-grid { grid-template-columns: 1fr; }
            .pf-card { padding: 1.1rem; }
            .pf-actions { justify-content: stretch; }
            .pf-btn { width: 100%; }
            .pf-avatar { width: 52px; height: 52px; font-size: 1.4rem; }
        }

        @media (prefers-reduced-motion: reduce) {
            .pf-nav__item, .pf-input, .pf-btn { transition: none; }
        }
    </style>
</div>