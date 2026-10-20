                    <div class="p-3 ml-4  bg-white border border-primary shadow-sm col-lg-4 col-sm-12 rounded-2xl">
                        <div class="col-12">
                            <a href="javascript:void(0);" class="btn btn-adds text-primary border-primary hover-btn-primary" data-bs-toggle="modal"
                                data-bs-target="#create"><i class="fa fa-plus me-2"></i>créer une commande</a>
                        </div>
                        <div class="setvaluecash">
                            <div class="row ">
                                 <div class="col-3">
                            </div>
                                <div class="col-3 m-2">
                                    <button class="btn btn-sm btn-outline-success text-bold " data-bs-toggle="modal"
                                        data-bs-target="#recents">
                                        COMMANDES
                                    </button>
                                </div>
                                 <div class="col-3">
                            </div>
                            </div>
                                @if (!empty($last_commande) and $last_commande->status == false)
                                <div class="row">
                                    <div class="col-3 m-2">
                                        <button class="btn btn-sm btn-outline-success text-bold "
                                            wire:click="confirmer({{ $last_commande->id }})">CASH
                                        </button>
                                    </div>
                             <div class="col-3">
                            </div>
                                    <div class="col-3 m-2">
                                        <button class="btn btn-sm btn-outline-success text-bold "
                                            data-bs-toggle="modal" data-bs-target="#dette">
                                            CREDIT
                                        </button>
                                    </div>
                                </div>
                                @endif

                            


                            <div class="btn-totallabel">
                                <h5></h5>
                                <h6></h6>
                            </div>

                            <div class="row">
                                @if (!empty($last_commande) and $last_commande->status == false)
                                    <div class="col-3 m-2">
                                        <button class="btn btn-sm btn-outline-success text-bold "
                                            data-bs-toggle="modal" data-bs-target="#facture">
                                            {{-- <img src="assets/img/icons/purchase1.svg" alt="img" class="me-2"> --}}
                                            FACTURE
                                        </button>
                                    </div>
                                    <div class="col-3 m-2">
                                        <button class="btn btn-sm btn-outline-success text-bold "
                                            data-bs-toggle="modal" data-bs-target="#coupon"class="">
                                            {{-- <img src="assets/img/icons/purchase.svg" alt="img" class="me-2"> --}}
                                            COUPON
                                        </button>

                                    </div>
                                    <div class="col-3 m-2">
                                        <button class="btn btn-sm btn-outline-success text-bold "
                                            data-bs-toggle="offcanvas" data-bs-target="#panierOffcanvas"
                                            aria-controls="panierOffcanvas">
                                            {{-- <img src="assets/img/icons/purchase.svg" alt="img" class="me-2"> --}}
                                            PANIER
                                            {{-- <span class="badge rounded bg-danger text-white m-0">{{ $commandes->count() }}</span> --}}
                                        </button>
                                    </div>
                                @endif
                            </div>
                        </div>
                        <div class="row">
                            @if (!empty($last_commande) and $last_commande->status == false)
                                <div class="order-list">
                                    <div class="orderid">
                                        @if (!empty($last_commande) and $last_commande->status == false)
                                            <h4>votre commande</h4>
                                            <h5>Code:

                                                <span class="text-strong"> {{ $last_commande->code }} </span>

                                            </h5>
                                        @endif
                                    </div>
                                    @if (!empty($commandes))
                                        <div class="totalitem">
                                            <h4>Total items : {{ $commandes->count() }}</h4>

                                        </div>
                                        <div class="actionproducts">

                                        </div>
                                </div>
                                <div class="table-responsive">
                                    <table class="table ">
                                        <thead>
                                            <tr>
                                                <th scope="col">Nom</th>
                                                <th scope="col">Quantite</th>
                                                <th>T.U</th>
                                                <th scope="col">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($commandes as $commande)
                                                <tr class="">
                                                    <td scope="row">{{ $commande->produit->name }}</td>
                                                    <td>
                                                        <div class="increment-decrement">
                                                            <div class="input-groups">
                                                                <input type="button" value="-"
                                                                    wire:click="reduire({{ $last_commande->id }},{{ $commande->produit->id }})"
                                                                    class="button-minus dec button">
                                                                <input type="text" name="child"
                                                                    value="{{ $commande->quantity_commande }}"
                                                                    class="quantity-field">
                                                                <input type="button" value="+"
                                                                    class="button-plus inc button "
                                                                    wire:click.prevent="ajouter( {{ $commande->produit->id }})"
                                                                    @if ($commande->produit->quantity <= 0) onClick="Swal.fire(
                                                                            'oups !!',
                                                                            'la quantité ne suffit pas pour ajouter à la commande!',
                                                                            'danger'
                                                                          )" @endif>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td> <?php $total_item += $commande->quantity_commande * $commande->produit->price; ?>
                                                        {{ $commande->quantity_commande * $commande->produit->price }}
                                                    </td>
                                                    <td>
                                                        @if (!empty($last_commande))
                                                            <li><a class="confirm"
                                                                    wire:click="annuler({{ $last_commande->id }},{{ $commande->produit->id }}, {{ $commande->quantity_commande }})"><img
                                                                        src="assets/img/icons/delete-2.svg"
                                                                        alt="img"></a>
                                                            </li>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif

                            <div class="split-card">
                            </div>
                            <div class="pt-0 pb-2 card-body">
                                <div class="setvalue">
                                    <ul>
                                        @if (!empty($last_commande))
                                            <li class="total-value">
                                                <h5>sous total </h5>
                                                <h6><?= $total_item ?> $</h6>
                                            </li>
                                            <li class="total-value">
                                                <h5>TVA </h5>
                                                <h6>{{ ($total_item / 100) * 16 }} $</h6>
                                            </li>
                                            <li class="total-value">
                                                <h5>Total </h5>
                                                <h6><?= $total_item + ($total_item / 100) * 16 ?> $</h6>
                                            </li>
                                        @endif
                                    </ul>
                                </div>
                                <div class="col-12">
                                </div>
                            </div>
                            @endif
                        </div>
                        {{-- off canvas --}}
                    </div>