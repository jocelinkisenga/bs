<?php

namespace App\Livewire\Product;


use App\Models\Categorie;
use App\Models\HystoryProduct;
use App\Models\Produit;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;

class Products extends Component
{
    use WithFileUploads;
    
    public $data;
    public $name;
    public $categorie_id;
    public $categories;
    public  $produit_id;
    public $produit_quantity;
    public $product_price;
    public $price;
    public  $prix_achat;
    public $prix_vente;
    public $photo;
    

    /**
     * Summary of render
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function render()
    {
        $this->categories = Categorie::where("company_id", "=", Auth::user()->company_id)->get();
        $this->data = Produit::where("company_id", "=", Auth::user()->company_id)->get();
        
        return view('livewire.product.products');
    }

    

    /**
     * Summary of reset_fields
     * @return void
     */
    public function reset_fields()
    {
        $this->name = "";
        $this->categorie_id = "";
        $this->price = "";
        $this->produit_quantity = "";
        $this->product_price = "";
        $this->prix_achat = "";
        $this->produit_id = "";
        $this->prix_vente = "";
    }


    /**
     * Summary of store
     * @return void
     */
    public function store()
    {
        $fileName= time().'.'.$this->photo->getClientOriginalName();
        
        $path=$this->photo->storeAs('uploads', $fileName, 'public');

        
        // $valide = $this->validate([
        //     'categorie_id' => "required",
        //     'name' => "required",
        //     'price' => "required",
        //     'path'=>'require'
        // ]);

        Produit::create([
            'categorie_id'=>$this->categorie_id,
            'company_id' => Auth::user()->company_id,
            'name'=>$this->name,
            'price'=>$this->price,
            'path'=>$fileName
        ]);
        session()->flash('message', 'produit ajouté avec succès');
        $this->reset_fields();
        $this->dispatch('close-modal');
    }


    /**
     * Summary of modifier_prix
     * @param int $produitId
     * @return void
     */
    public function modifier_prix(int $produitId)
    {

        $produit = Produit::find($produitId);
        $produit->update([
            'price' => $this->product_price
        ]);
        $this->reset_fields();
        session()->flash('message', 'prix modifier');
    }
}
