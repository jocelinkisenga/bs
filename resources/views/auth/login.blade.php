@extends('layouts.guest')

@section('content')
<div class="pos-login">
    <div class="pos-login__wrap">

        {{-- ============ Panneau gauche : accueil ============ --}}
        <aside class="pos-login__hero">
            <div class="pos-login__brand">
                <img src="{{ asset('assets/img/logo.png') }}" alt="SELLIT" class="pos-login__logo">
            </div>

            <div class="pos-login__art" aria-hidden="true">
                <svg viewBox="0 0 280 220" xmlns="http://www.w3.org/2000/svg">
                    {{-- Comptoir --}}
                    <rect x="20" y="170" width="240" height="14" rx="6" fill="#c7d2fe"/>
                    <rect x="40" y="184" width="200" height="22" rx="4" fill="#818cf8" opacity=".35"/>

                    {{-- Terminal de caisse --}}
                    <rect x="46" y="70" width="120" height="96" rx="12" fill="#ffffff"/>
                    <rect x="54" y="80" width="104" height="50" rx="6" fill="#eef2ff"/>
                    <rect x="62" y="88" width="88" height="8" rx="3" fill="#4f46e5"/>
                    <rect x="62" y="102" width="40" height="6" rx="3" fill="#a5b4fc"/>
                    <rect x="62" y="112" width="56" height="6" rx="3" fill="#c7d2fe"/>
                    <g fill="#c7d2fe">
                        <rect x="60" y="138" width="16" height="12" rx="3"/>
                        <rect x="82" y="138" width="16" height="12" rx="3"/>
                        <rect x="104" y="138" width="16" height="12" rx="3"/>
                        <rect x="126" y="138" width="16" height="12" rx="3"/>
                    </g>
                    <rect x="150" y="70" width="16" height="96" rx="6" fill="#4f46e5" opacity=".15"/>

                    {{-- Tiroir --}}
                    <rect x="46" y="150" width="120" height="16" rx="4" fill="#4f46e5" opacity=".12"/>

                    {{-- Panier --}}
                    <path d="M184 92h62l-9 62a8 8 0 0 1-8 7h-36a8 8 0 0 1-8-7z" fill="#4f46e5"/>
                    <path d="M184 92h62" stroke="#312e81" stroke-width="4" stroke-linecap="round"/>
                    <path d="M196 92l12-28h22l12 28" fill="none" stroke="#312e81" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"/>
                    <g fill="none" stroke="#c7d2fe" stroke-width="4" stroke-linecap="round">
                        <path d="M200 110v30"/>
                        <path d="M216 110v30"/>
                        <path d="M232 110v30"/>
                    </g>

                    {{-- Produits dans le panier --}}
                    <circle cx="200" cy="82" r="8" fill="#f97316"/>
                    <circle cx="232" cy="80" r="7" fill="#22c55e"/>
                </svg>
            </div>

            <div class="pos-login__welcome">
                <h1>Bienvenue sur votre caisse</h1>
                <p>Encaissez rapidement, suivez vos stocks et imprimez vos factures en un clic.</p>
            </div>
        </aside>

        {{-- ============ Panneau droit : formulaire ============ --}}
        <main class="pos-login__panel">
            <div class="pos-login__card">

                <div class="pos-login__heading">
                    <span class="pos-login__eyebrow">Espace caissier</span>
                    <h2>Connexion</h2>
                    <p>Saisissez vos identifiants pour ouvrir la caisse.</p>
                </div>

                <form method="POST" action="{{ route('login') }}" novalidate>
                    @csrf

                    {{-- Email --}}
                    <div class="pos-field @error('email') has-error @enderror">
                        <label for="email">Adresse email</label>
                        <div class="pos-field__input">
                            <i class="bi bi-envelope"></i>
                            <input type="email" name="email" id="email"
                                value="{{ old('email') }}"
                                placeholder="vous@exemple.com"
                                autocomplete="username"
                                autofocus
                                required>
                        </div>
                        @error('email')
                            <small class="pos-field__error" id="emailError">{{ $message }}</small>
                        @enderror
                    </div>

                    {{-- Mot de passe --}}
                    <div class="pos-field @error('password') has-error @enderror">
                        <label for="password">Mot de passe</label>
                        <div class="pos-field__input">
                            <i class="bi bi-lock"></i>
                            <input type="password" name="password" id="password"
                                placeholder="••••••••"
                                autocomplete="current-password"
                                required>
                            <button type="button" class="pos-field__toggle" aria-label="Afficher le mot de passe"
                                onclick="posTogglePassword(this)">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        @error('password')
                            <small class="pos-field__error" id="passwordError">{{ $message }}</small>
                        @enderror
                    </div>

                    <button type="submit" name="login" class="pos-login__submit">
                        <span>S'authentifier</span>
                        <i class="bi bi-arrow-right"></i>
                    </button>
                </form>

                <p class="pos-login__foot">
                    <i class="bi bi-shield-lock"></i> Accès réservé au personnel autorisé
                </p>
            </div>
        </main>
    </div>
</div>

<style>
    .pos-login {
        --pos-primary: #4f46e5;
        --pos-primary-hover: #4338ca;
        --pos-primary-soft: #eef2ff;
        --pos-ink: #0f172a;
        --pos-ink-2: #475569;
        --pos-ink-3: #94a3b8;
        --pos-line: #e2e8f0;
        --pos-danger: #dc2626;

        min-height: 100vh;
        display: flex;
        align-items: stretch;
        background: #f1f5f9;
        font-family: "Nunito", system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
        color: var(--pos-ink);
        -webkit-font-smoothing: antialiased;
    }

    .pos-login__wrap {
        display: grid;
        grid-template-columns: 1fr;
        width: 100%;
        min-height: 100vh;
    }

    /* ---------- Panneau d'accueil ---------- */
    .pos-login__hero {
        display: none;
        flex-direction: column;
        justify-content: space-between;
        gap: 2rem;
        padding: 2.5rem;
        background: linear-gradient(145deg, #4f46e5 0%, #3730a3 100%);
        color: #fff;
        position: relative;
        overflow: hidden;
    }
    .pos-login__hero::before,
    .pos-login__hero::after {
        content: "";
        position: absolute;
        border-radius: 50%;
        background: rgba(255, 255, 255, .06);
        pointer-events: none;
    }
    .pos-login__hero::before { width: 380px; height: 380px; top: -120px; right: -120px; }
    .pos-login__hero::after  { width: 260px; height: 260px; bottom: -90px; left: -80px; }

    .pos-login__brand { position: relative; z-index: 1; }
    .pos-login__logo {
        height: 40px;
        width: auto;
        padding: 6px 12px;
        background: #fff;
        border-radius: 12px;
    }

    .pos-login__art {
        position: relative;
        z-index: 1;
        display: flex;
        justify-content: center;
    }
    .pos-login__art svg {
        width: min(100%, 320px);
        height: auto;
        filter: drop-shadow(0 20px 30px rgba(15, 23, 42, .25));
    }

    .pos-login__welcome { position: relative; z-index: 1; }
    .pos-login__welcome h1 {
        margin: 0 0 .6rem;
        font-size: 1.9rem;
        font-weight: 800;
        line-height: 1.2;
        letter-spacing: -.02em;
    }
    .pos-login__welcome p {
        margin: 0;
        max-width: 420px;
        color: rgba(255, 255, 255, .82);
        font-size: 1rem;
        line-height: 1.6;
    }

    /* ---------- Panneau formulaire ---------- */
    .pos-login__panel {
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 2rem 1.25rem;
    }
    .pos-login__card {
        width: 100%;
        max-width: 420px;
    }

    .pos-login__heading { margin-bottom: 1.75rem; }
    .pos-login__eyebrow {
        display: inline-block;
        margin-bottom: .6rem;
        padding: .25rem .7rem;
        border-radius: 999px;
        background: var(--pos-primary-soft);
        color: var(--pos-primary);
        font-size: .72rem;
        font-weight: 800;
        letter-spacing: .06em;
        text-transform: uppercase;
    }
    .pos-login__heading h2 {
        margin: 0 0 .35rem;
        font-size: 1.75rem;
        font-weight: 800;
        letter-spacing: -.02em;
    }
    .pos-login__heading p {
        margin: 0;
        color: var(--pos-ink-2);
    }

    /* ---------- Champs ---------- */
    .pos-field { margin-bottom: 1.25rem; }
    .pos-field label {
        display: block;
        margin-bottom: .4rem;
        font-size: .85rem;
        font-weight: 700;
        color: var(--pos-ink-2);
    }
    .pos-field__input {
        position: relative;
    }
    .pos-field__input > i {
        position: absolute;
        left: 14px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--pos-ink-3);
        pointer-events: none;
        font-size: 1rem;
    }
    .pos-field__input input {
        width: 100%;
        height: 50px;
        padding: 0 3rem 0 2.75rem;
        border: 1px solid var(--pos-line);
        border-radius: 12px;
        background: #fff;
        font: inherit;
        font-size: 1rem;
        color: var(--pos-ink);
        transition: border-color .15s, box-shadow .15s;
    }
    .pos-field__input input::placeholder { color: var(--pos-ink-3); }
    .pos-field__input input:focus {
        outline: 0;
        border-color: var(--pos-primary);
        box-shadow: 0 0 0 4px rgba(79, 70, 229, .14);
    }

    .pos-field__toggle {
        position: absolute;
        right: 6px;
        top: 50%;
        transform: translateY(-50%);
        display: grid;
        place-items: center;
        width: 38px;
        height: 38px;
        border: 0;
        border-radius: 10px;
        background: transparent;
        color: var(--pos-ink-3);
        cursor: pointer;
    }
    .pos-field__toggle:hover { color: var(--pos-primary); background: var(--pos-primary-soft); }

    .pos-field.has-error .pos-field__input input {
        border-color: var(--pos-danger);
        background: #fff7f7;
    }
    .pos-field__error {
        display: block;
        margin-top: .4rem;
        color: var(--pos-danger);
        font-size: .82rem;
        font-weight: 600;
    }

    /* ---------- Bouton ---------- */
    .pos-login__submit {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: .5rem;
        width: 100%;
        height: 52px;
        margin-top: .5rem;
        border: 0;
        border-radius: 12px;
        background: var(--pos-primary);
        color: #fff;
        font: inherit;
        font-size: 1rem;
        font-weight: 800;
        cursor: pointer;
        box-shadow: 0 10px 24px rgba(79, 70, 229, .28);
        transition: background .15s, transform .15s, box-shadow .15s;
    }
    .pos-login__submit:hover {
        background: var(--pos-primary-hover);
        transform: translateY(-1px);
        box-shadow: 0 14px 28px rgba(79, 70, 229, .32);
    }
    .pos-login__submit:active { transform: translateY(0); }
    .pos-login__submit:focus-visible {
        outline: 3px solid rgba(79, 70, 229, .4);
        outline-offset: 3px;
    }

    .pos-login__foot {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: .4rem;
        margin: 1.75rem 0 0;
        color: var(--pos-ink-3);
        font-size: .82rem;
    }

    /* ---------- Desktop ---------- */
    @media (min-width: 992px) {
        .pos-login__wrap {
            grid-template-columns: 1.05fr 1fr;
        }
        .pos-login__hero {
            display: flex;
        }
        .pos-login__panel {
            padding: 3rem;
        }
    }

    @media (max-width: 575.98px) {
        .pos-login__card { max-width: none; }
        .pos-login__heading h2 { font-size: 1.5rem; }
    }

    @media (prefers-reduced-motion: reduce) {
        .pos-login__submit,
        .pos-field__input input { transition: none; }
    }
</style>

<script>
    function posTogglePassword(btn) {
        var input = document.getElementById('password');
        var icon = btn.querySelector('i');
        var visible = input.type === 'text';

        input.type = visible ? 'password' : 'text';
        icon.className = visible ? 'bi bi-eye' : 'bi bi-eye-slash';
        btn.setAttribute('aria-label', visible ? 'Afficher le mot de passe' : 'Masquer le mot de passe');
    }
</script>
@endsection