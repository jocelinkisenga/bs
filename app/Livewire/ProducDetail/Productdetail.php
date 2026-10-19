<?php

namespace App\Livewire\ProducDetail;

use App\Enums\RoleEnum;
use App\Models\HystoryProduct;
use App\Models\Produit;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Productdetail extends Component
{
    /** Renseigné automatiquement par la route (paramètre "id" de product-detail). */
    public int $product_id;

    // Formulaire « Ajouter du stock »
    public string $quantiteAjout = '';
    public string $prixAchat = '';

    // Formulaire « Modifier le prix »
    public string $nouveauPrix = '';

    public function mount(): void
    {
        abort_unless(
            in_array(Auth::user()->role_id, [RoleEnum::ADMIN, RoleEnum::GERANT]),
            403
        );
    }

    /** Ajoute une quantité au stock et enregistre le mouvement dans l'historique. */
    public function ajouterStock(): void
    {
        $this->quantiteAjout = trim($this->quantiteAjout);
        $this->prixAchat     = str_replace(',', '.', trim($this->prixAchat));

        $this->validate([
            'quantiteAjout' => ['required', 'integer', 'min:1', 'max:1000000'],
            'prixAchat'     => ['required', 'numeric', 'min:0', 'max:999999999'],
        ], [
            'quantiteAjout.required' => 'La quantité est obligatoire.',
            'quantiteAjout.integer'  => 'La quantité doit être un nombre entier.',
            'quantiteAjout.min'      => 'La quantité doit être au moins 1.',
            'quantiteAjout.max'      => 'La quantité est trop élevée.',
            'prixAchat.required'     => 'Le prix d\'achat est obligatoire.',
            'prixAchat.numeric'      => 'Le prix d\'achat doit être un nombre.',
            'prixAchat.min'          => 'Le prix d\'achat ne peut pas être négatif.',
        ]);

        $ajout = (int) $this->quantiteAjout;

        DB::transaction(function () use ($ajout) {
            // Verrou : évite deux ajouts simultanés sur le même produit
            $produit = Produit::where('company_id', $this->companyId())
                ->lockForUpdate()
                ->findOrFail($this->product_id);

            HystoryProduct::create([
                'product_id'   => $produit->id,
                'new_quantity' => $ajout,
                'old_quantity' => $produit->quantity,
                'prix_achat'   => $this->prixAchat,
                'company_id'   => $this->companyId(),
            ]);

            $produit->update([
                'quantity' => $produit->quantity + $ajout,
            ]);
        });

        $this->reset(['quantiteAjout', 'prixAchat']);
        $this->resetValidation();

        session()->flash('message', 'Stock ajouté avec succès.');
        $this->dispatch('modal-hide', id: 'stockModal');
    }

    /** Met à jour le prix de vente du produit. */
    public function modifierPrix(): void
    {
        $this->nouveauPrix = str_replace(',', '.', trim($this->nouveauPrix));

        $this->validate([
            'nouveauPrix' => ['required', 'numeric', 'min:0', 'max:999999999'],
        ], [
            'nouveauPrix.required' => 'Le prix est obligatoire.',
            'nouveauPrix.numeric'  => 'Le prix doit être un nombre.',
            'nouveauPrix.min'      => 'Le prix ne peut pas être négatif.',
        ]);

        $this->findProduit()->update([
            'price' => $this->nouveauPrix,
        ]);

        $this->reset('nouveauPrix');
        $this->resetValidation();

        session()->flash('message', 'Prix modifié avec succès.');
        $this->dispatch('modal-hide', id: 'priceModal');
    }

    public function render()
    {
        $produit = $this->findProduit();

        $historique = HystoryProduct::where('product_id', $produit->id)
            ->where('company_id', $this->companyId())
            ->latest()
            ->take(20)
            ->get();

        return view('livewire.produc-detail.productdetail', compact('produit', 'historique'));
    }

    private function findProduit(): Produit
    {
        return Produit::with('categorie')
            ->where('company_id', $this->companyId())
            ->findOrFail($this->product_id);
    }

    private function companyId(): int
    {
        return Auth::user()->id;
    }
}