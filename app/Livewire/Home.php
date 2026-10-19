<?php

namespace App\Livewire;

use App\Enums\RoleEnum;
use App\Repositorie\CommandeRepositorie;
use App\Repositorie\ProduitRepository;
use App\Repositorie\ReductionRepositorie;
use App\Services\CategoryService;
use App\Services\DetteService;
use App\Services\PrecommandeService;
use App\Services\ProductService;
use App\Services\TableService;
use App\Services\UserService;
use App\Models\Categorie;
use App\Models\Commande;
use App\Models\Precommande;
use App\Models\Produit;
use App\Models\Serveur;
use App\Models\Table;
use App\Models\User;
use Flasher\Laravel\Facade\Flasher;
use Flasher\Noty\Prime\NotyInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Component;
use SebastianBergmann\Type\NullType;

class Home extends Component
{
    public $categories;
    public $serveurs;
    public $produits;
    public $servers;
    public $server_id;
    public $last_commande = null;
    public $quantity_commande = 1;
    public $produit_id;
    public $commandes;
    public $precommandes;
    public $facture;
    public $invoce, $reductions;
    public $todays;
    public $clientName, $clientPhone, $advance;
    public $tables;
    public $companyName;
    public $table_id;
    protected $commande_repo, $reduction_repo, $produit_repo;
    protected $tableService, $productService, $categoryService, $userService, $precommandeService, $detteService;
    public $precommande;

    protected $listeners = ["reduced" => 'render', 'reduction-confirmed' => 'render'];

    public function __construct()
    {
        $this->commande_repo = new CommandeRepositorie;
        $this->reduction_repo = new ReductionRepositorie;
        $this->produit_repo = new ProduitRepository;
        $this->tableService = new TableService;
        $this->productService = new ProductService;
        $this->categoryService = new CategoryService;
        $this->userService = new UserService;
        $this->precommandeService = new PrecommandeService;
        $this->detteService = new DetteService;
    }

    /**
     * Facture de la précommande sous forme de tableau.
     * Le dépôt renvoie une Collection : on la convertit pour que empty()
     * et l'accès par index ($invoce[0]) se comportent comme avant.
     */
    private function factureLignes(int $precommandeId): array
    {
        return $this->commande_repo->facture($precommandeId)->all();
    }

    public function render()
    {
        if ($this->last_commande) {
            $this->invoce = $this->factureLignes($this->last_commande->id);
            $this->commandes = $this->commande_repo->all_commandes($this->last_commande->id);
            $this->companyName = User::where('id', '=', Auth::user()->company_id)->get('company_name');
        }

        $this->categories = $this->categoryService->category_with_products();
        $this->produits = $this->productService->product_by_company();
        $this->precommandes = $this->commande_repo->all_precommandes();
        $this->reductions = $this->reduction_repo->reductions();
        $this->todays = $this->commande_repo->todays();
        $this->tables = $this->tableService->table_with_precommandes();
        $this->serveurs = $this->userService->servers();

        return view('livewire.home');
    }

    //cree une commande
    public function store()
    {
        $code = '#' . date('Y-m-d') . rand(1, 1000);
        $table = $this->tableService->table_by_id($this->table_id);

        //creation de la precommande
        $this->precommande = $this->precommandeService->store_precommande($this->server_id, $code, $this->table_id);
        $this->tableService->update_table($this->table_id);

        $this->facture = $this->factureLignes($this->precommande->id);
        $this->last_commande = $this->commande_repo->last_commande($this->precommande->id);

        flash()->success('Commande créée.', [], 'Succès');
        Session::put($table->name, $table->name);
        Session::put($this->precommande->code, $this->precommande->code);
        $this->vider_commande_form();

        $this->dispatch('closeModal');
    }

    // reduction des donnees
    public function reduction($commandeId)
    {
        $this->reduction_repo->store($commandeId);
    }

    //Ajout des produits a la commande
    public function ajouter(int $produitId)
    {
        $this->produit_id = $produitId;

        if (empty($this->last_commande)) {
            flash()->warning('Sélectionnez d\'abord une commande.');
            return;
        }

        $produit = $this->commande_repo->produit_by_id($produitId);

        if (! $produit) {
            flash()->error('Produit introuvable.');
            return;
        }

        $precommandeId = $this->last_commande->id;

        $existe = $this->commande_repo->commande_by_id($precommandeId, $produitId);

        $ok = empty($existe)
            ? $this->commande_repo->store_command($precommandeId, $produitId, $this->quantity_commande)
            : $this->commande_repo->update_quantity($precommandeId, $produitId, $this->quantity_commande);

        if (! $ok) {
            flash()->error('Stock insuffisant pour « ' . $produit->name . ' ».');
        }

        $this->facture = $this->factureLignes($precommandeId);
    }

    //retranche la quantite des produits de la commande
    public function reduire(int $orderId, int $productId)
    {
        if (! $this->commande_repo->reduire_quantity($orderId, $productId)) {
            flash()->warning('Ce produit n\'est pas dans la commande.');
        }

        $this->facture = $this->factureLignes($orderId);
    }

    //annule efface le produit de la commande
    public function annuler(int $commandId, int $produitId)
    {
        if (! $this->commande_repo->delete_commande($commandId, $produitId)) {
            flash()->warning('Ligne introuvable dans la commande.');
        }

        $this->facture = $this->factureLignes($commandId);
    }

    //confirme la reduction de la facture
    public function reduction_facture($commandeId)
    {
        return $this->facture = $this->factureLignes($commandeId);
    }

    //selectionne la commande pour y ajouter des produits
    public function edit(int $id)
    {
        if ($id !== 0) {
            $this->facture = $this->factureLignes($id);
            return $this->last_commande = $this->commande_repo->last_commande($id);
        }

        flash()->error('Cette commande n\'existe pas.');
    }

    //confirme la commande et genere une facture
    public function invoice($precommandeId)
    {
        $this->precommandeService->invoice_precommande($precommandeId);
    }

    //confirme la commande
    public function confirmer(int $id)
    {
        $this->commande_repo->confirm($id);
        $this->facture = $this->factureLignes($id);
        $this->last_commande = 0;
        $this->dispatch('close-modal');
    }

    //confirme la reduction
    public function confirm_reduction(int $id)
    {
        $precommande_id = $this->reduction_repo->confirm($id);
        return $this->facture = $this->factureLignes($precommande_id);
    }

    public function storeDette($precommandeId)
    {
        $detteOkay = $this->detteService->store_dette($this->clientName, $this->clientPhone, $this->advance, $precommandeId);

        if ($detteOkay === true) {
            $this->facture = $this->factureLignes($precommandeId);
        }

        $this->dispatch('closeModal');
    }

    //mise a jour du status de la table
    private function vider_commande_form()
    {
        $this->server_id = "";
        $this->table_id = "";
    }
}