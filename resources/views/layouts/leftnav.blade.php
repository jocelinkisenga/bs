@php
    use App\Enums\RoleEnum;

    $role = Auth::user()->role_id;
    $peutGerer = $role == RoleEnum::GERANT || $role == RoleEnum::ADMIN;

    // Structure du menu : chaque lien est soit une route nommée, soit une URL directe
    $menu = [
        [
            'titre' => "Vue d'ensemble",
            'liens' => [
                ['route' => 'dashboard', 'icone' => 'bi-speedometer2',  'libelle' => 'Tableau de bord'],
                ['url' => '/home',       'icone' => 'bi-cash-register', 'libelle' => 'Caisse (POS)', 'actif' => 'home'],
            ],
        ],
        [
            'titre' => 'Catalogue',
            'liens' => [
                ['route' => 'categories', 'icone' => 'bi-tags',       'libelle' => 'Catégories', 'navigate' => true],
                ['route' => 'products',   'icone' => 'bi-box-seam',   'libelle' => 'Produits'],
            ],
        ],
        [
            'titre' => 'Service',
            'liens' => [
                ['route' => 'tables',         'icone' => 'bi-grid-3x3-gap', 'libelle' => 'Tables'],
                ['route' => 'admin-commande', 'icone' => 'bi-receipt',      'libelle' => 'Commandes'],
            ],
        ],
        [
            'titre' => 'Finances',
            'liens' => [
                ['route' => 'depenses', 'icone' => 'bi-wallet2',         'libelle' => 'Dépenses'],
                ['route' => 'rapports', 'icone' => 'bi-bar-chart-line',  'libelle' => 'Rapports'],
               // Réductions : désactivé comme dans l'original
               // ['route' => 'reductions', 'icone' => 'bi-percent', 'libelle' => 'Réductions'],
            ],
        ],
        [
            'titre' => 'Administration',
            'liens' => [
                ['route' => 'users', 'icone' => 'bi-people', 'libelle' => 'Personnel'],
            ],
             'liens' => [
                ['route' => 'profile', 'icone' => 'bi-settings', 'libelle' => 'Profile'],
            ],
        ],
    ];
@endphp

<aside class="pos-sidebar" id="sidebar" aria-label="Menu principal">
    <nav class="pos-sidebar__nav">

        @if ($peutGerer)

            @foreach ($menu as $groupe)
                <div class="pos-sidebar__group">
                    <p class="pos-sidebar__label">{{ $groupe['titre'] }}</p>

                    <ul class="pos-sidebar__list">
                        @foreach ($groupe['liens'] as $lien)
                            @php
                                $href  = isset($lien['route']) ? route($lien['route']) : url($lien['url']);
                                $actif = isset($lien['route'])
                                    ? request()->routeIs($lien['route'])
                                    : request()->is($lien['actif'] ?? $lien['url']);
                            @endphp

                            <li>
                                <a href="{{ $href }}"
                                   class="pos-sidebar__link {{ $actif ? 'is-active' : '' }}"
                                   @if (!empty($lien['navigate'])) wire:navigate @endif
                                   @if ($actif) aria-current="page" @endif>
                                    <i class="bi {{ $lien['icone'] }}"></i>
                                    <span>{{ $lien['libelle'] }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach

        @else

            <div class="pos-sidebar__group">
                <ul class="pos-sidebar__list">
                    <li>
                        <a href="{{ route('commandes') }}" class="pos-sidebar__link pos-sidebar__link--alert">
                            <i class="bi bi-house-door"></i>
                            <span>Retour à l'accueil</span>
                        </a>
                    </li>
                </ul>
            </div>

        @endif

    </nav>
</aside>

<style>
    :root {
        --pos-sidebar-w: 250px;
        --pos-header-h: 60px;
    }

    .pos-sidebar {
        --pos-primary: #4f46e5;
        --pos-primary-soft: #eef2ff;
        --pos-ink-2: #475569;
        --pos-ink-3: #94a3b8;
        --pos-line: #e2e8f0;
        --pos-warn: #b45309;
        --pos-warn-soft: #fef3c7;

        position: fixed;
        top: var(--pos-header-h);
        left: 0;
        bottom: 0;
        z-index: 1040;
        width: var(--pos-sidebar-w);
        padding: 1.25rem .75rem 1.5rem;
        overflow-y: auto;
        background: #fff;
        border-right: 1px solid var(--pos-line);
        font-family: "Nunito", system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
        transition: transform .25s ease;
    }

    .pos-sidebar__group + .pos-sidebar__group { margin-top: 1.4rem; }

    .pos-sidebar__label {
        margin: 0 .75rem .5rem;
        font-size: .68rem;
        font-weight: 800;
        letter-spacing: .08em;
        text-transform: uppercase;
        color: var(--pos-ink-3);
    }

    .pos-sidebar__list {
        display: flex;
        flex-direction: column;
        gap: .2rem;
        margin: 0;
        padding: 0;
        list-style: none;
    }

    .pos-sidebar__link {
        display: flex;
        align-items: center;
        gap: .75rem;
        padding: .55rem .75rem;
        border-radius: 12px;
        color: var(--pos-ink-2);
        font-size: .92rem;
        font-weight: 700;
        text-decoration: none;
        transition: background .15s ease, color .15s ease, box-shadow .15s ease;
    }

    .pos-sidebar__link i {
        display: grid;
        place-items: center;
        width: 34px;
        height: 34px;
        border-radius: 10px;
        background: #f1f5f9;
        color: var(--pos-ink-3);
        font-size: 1.05rem;
        transition: background .15s ease, color .15s ease;
    }

    .pos-sidebar__link:hover {
        background: var(--pos-primary-soft);
        color: var(--pos-primary);
    }
    .pos-sidebar__link:hover i {
        background: #fff;
        color: var(--pos-primary);
    }

    .pos-sidebar__link:focus-visible {
        outline: 3px solid rgba(79, 70, 229, .35);
        outline-offset: 2px;
    }

    .pos-sidebar__link.is-active {
        background: var(--pos-primary);
        color: #fff;
        box-shadow: 0 8px 18px rgba(79, 70, 229, .28);
    }
    .pos-sidebar__link.is-active i {
        background: rgba(255, 255, 255, .18);
        color: #fff;
    }

    .pos-sidebar__link--alert {
        background: var(--pos-warn-soft);
        color: var(--pos-warn);
    }
    .pos-sidebar__link--alert i {
        background: #fff;
        color: var(--pos-warn);
    }
    .pos-sidebar__link--alert:hover {
        background: #fde68a;
        color: #92400e;
    }

    /* Décalage du contenu par rapport à la sidebar (desktop) */
    .main-wrapper > .page-wrapper {
        margin-left: var(--pos-sidebar-w);
    }

    /* ---------- Mobile : menu coulissant ---------- */
    @media (max-width: 991.98px) {
        .pos-sidebar {
            top: 0;
            width: min(280px, 85vw);
            transform: translateX(-100%);
        }
        .pos-sidebar.is-open {
            transform: none;
            box-shadow: 0 20px 50px rgba(15, 23, 42, .25);
        }
        .main-wrapper > .page-wrapper {
            margin-left: 0;
        }
        body.pos-nav-open::after {
            content: "";
            position: fixed;
            inset: 0;
            z-index: 1035;
            background: rgba(15, 23, 42, .35);
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .pos-sidebar,
        .pos-sidebar__link,
        .pos-sidebar__link i { transition: none; }
    }
</style>

<script>
    (function () {
        var sidebar = document.getElementById('sidebar');
        var burger  = document.getElementById('mobile_btn');
        if (!sidebar || !burger) return;

        function fermer() {
            sidebar.classList.remove('is-open');
            document.body.classList.remove('pos-nav-open');
        }

        burger.addEventListener('click', function (e) {
            e.preventDefault();
            var ouvert = sidebar.classList.toggle('is-open');
            document.body.classList.toggle('pos-nav-open', ouvert);
        });

        // Fermer en cliquant hors du menu (mobile)
        document.addEventListener('click', function (e) {
            if (!document.body.classList.contains('pos-nav-open')) return;
            if (sidebar.contains(e.target) || burger.contains(e.target)) return;
            fermer();
        });
    })();
</script>