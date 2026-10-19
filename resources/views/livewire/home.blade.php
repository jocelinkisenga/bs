@php
    use App\Models\produit;
    global $total_item;
    global $facture_total;
    global $pourcentage;
@endphp

<div class="pos-app">

    {{-- ================= HEADER ================= --}}
    <header class="pos-header">
        <a href="" class="pos-logo" aria-label="Accueil">
            <img src="assets/img/logo.png" alt="SELLIT">
        </a>

        <div class="pos-actions">

            {{-- Notifications --}}
            <div class="dropdown">
                <button class="pos-icon-btn dropdown-toggle" type="button"
                    data-bs-toggle="dropdown" aria-expanded="false" aria-label="Notifications">
                    <i class="bi bi-bell"></i>
                    <span class="pos-badge"><livewire:components.reduced></span>
                </button>

                <div class="dropdown-menu dropdown-menu-end pos-notifications">
                    <div class="pos-dd-head">
                        <span>Notifications</span>
                        <a href="javascript:void(0)" class="clear-noti">Tout effacer</a>
                    </div>

                    <ul class="notification-list">
                        @forelse ($reductions as $item)
                            <li class="notification-message">
                                <div class="pos-notif">
                                    <div>
                                        <p class="pos-notif__code">{{ $item->precommande->code }}</p>
                                        <p class="pos-notif__text">
                                            <strong>{{ $item->pourcentage }} %</strong> de réduction
                                        </p>
                                    </div>
                                    <button class="btn btn-success btn-sm"
                                        data-bs-toggle="modal"
                                        data-bs-target="#commandeFacture"
                                        wire:click="reduction_facture({{ $item->precommande->id }})">
                                        Voir
                                    </button>
                                </div>
                            </li>
                        @empty
                            <li class="pos-empty">Aucune notification</li>
                        @endforelse
                    </ul>

                    <div class="pos-dd-foot">
                        <a href="">Voir toutes les notifications</a>
                    </div>
                </div>
            </div>

            {{-- Profil --}}
            <div class="dropdown">
                <button class="pos-avatar-btn dropdown-toggle" type="button"
                    data-bs-toggle="dropdown" aria-expanded="false" aria-label="Profil">
                    <img src="assets/img/profiles/avator1.jpg" alt="" class="pos-avatar">
                </button>

                <div class="dropdown-menu dropdown-menu-end pos-profile">
                    <div class="pos-profile__head">
                        <img src="assets/img/profiles/avator1.jpg" alt="" class="pos-avatar">
                        <div>
                            <strong>{{ Auth::user()->name }}</strong>
                            <small>Administrateur</small>
                        </div>
                    </div>

                    <a class="dropdown-item" href="{{ route('dashboard') }}">
                        <i class="bi bi-speedometer2"></i> Tableau de bord
                    </a>
                    <a class="dropdown-item" href="">
                        <i class="bi bi-gear"></i> Paramètres
                    </a>

                    <div class="dropdown-divider"></div>

                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="dropdown-item text-danger">
                            <i class="bi bi-box-arrow-right"></i> Se déconnecter
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </header>

    {{-- ================= CONTENU ================= --}}
    <main class="pos-main">
        <div class="row g-3 g-lg-4">

            <section class="col-12 col-lg-8 tabs_wrapper">

                {{-- Catégories --}}
                <div class="pos-panel mb-3 mb-lg-4">
                    <div class="pos-panel__head">
                        <h2 class="pos-panel__title">Catégories</h2>
                    </div>

                    <ul class="tabs pos-categories" wire:ignore>
                        @foreach ($categories as $item)
                            <li id="{{ $item->id }}">
                                <div class="product-details">
                                    <i class="bi bi-tag"></i>
                                    <h6>{{ $item->name }}</h6>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>

                {{-- Produits --}}
                <div class="pos-panel">
                    <div class="pos-panel__head">
                        <h2 class="pos-panel__title">Produits</h2>

                        <label class="pos-search">
                            <i class="bi bi-search"></i>
                            <input type="search" id="pos-search"
                                placeholder="Rechercher un produit" autocomplete="off">
                        </label>
                    </div>

                    <div class="tabs_container">
                        @foreach ($categories as $item)
                            <div class="tab_content active" data-tab="{{ $item->id }}">
                                <div class="row row-cols-2 row-cols-sm-3 row-cols-xl-4 g-2 g-md-3">
                                    @foreach (Produit::whereCategorie_id($item->id)->get() as $produit)
                                        <div class="col pos-product" data-name="{{ Str::lower($produit->name) }}">
                                            <div class="pos-card {{ $produit->quantity <= 0 ? 'is-out' : '' }}"
                                                role="button"
                                                tabindex="0"
                                                @if ($produit->quantity > 0)
                                                    wire:click.prevent="ajouter({{ $produit->id }})"
                                                @else
                                                    onclick="Swal.fire('Oups !', 'La quantité ne suffit pas pour ajouter à la commande.', 'error')"
                                                @endif>

                                                <span class="pos-card__stock">Stock {{ $produit->quantity }}</span>

                                                <div class="pos-card__icon">
                                                    <i class="bi bi-box-seam"></i>
                                                </div>

                                                <p class="pos-card__cat">{{ $produit->categorie->name }}</p>
                                                <h4 class="pos-card__name">{{ Str::upper($produit->name) }}</h4>

                                                <div class="pos-card__foot">
                                                    <span class="pos-card__price">{{ $produit->price }} F</span>
                                                    <span class="pos-card__add">
                                                        <i class="bi bi-plus-lg"></i>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

            {{-- Panier (panneau droit) --}}
            @include('livewire.modals.right-panier')
        </div>
    </main>

    {{-- ================= MODALS ================= --}}
    @include('livewire.modals.edit')
    @include('livewire.modals.create')
    @include('livewire.modals.debt')
    @include('livewire.modals.recents')
    @include('livewire.modals.facture')
    @include('livewire.modals.coupon')
    @include('livewire.modals.factureReduction')
    @include('livewire.modals.ticket')
</div>