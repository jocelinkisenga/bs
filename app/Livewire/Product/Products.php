<?php

namespace App\Livewire\Product;

use App\Enums\RoleEnum;
use App\Models\Categorie;
use App\Models\Produit;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class Products extends Component
{
    use WithFileUploads, WithPagination;

    private const DISK   = 'public';
    private const FOLDER = 'uploads';

    // Formulaire
    public string $name = '';
    public ?int $categorie_id = null;
    public string $price = '';
    public $photo = null;               // upload temporaire
    public ?string $currentPhoto = null; // nom du fichier déjà enregistré
    public ?int $produitId = null;      // null = création

    // Liste
    public string $search = '';
    public string $filterCategorie = '';

    protected $queryString = [
        'search'          => ['except' => ''],
        'filterCategorie' => ['except' => ''],
    ];

    public function mount(): void
    {
        abort_unless(
            in_array(Auth::user()->role_id, [RoleEnum::ADMIN, RoleEnum::GERANT]),
            403
        );
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterCategorie(): void
    {
        $this->resetPage();
    }

    protected function rules(): array
    {
        return [
            'name'         => ['required', 'string', 'max:150'],
            'categorie_id' => [
                'required',
                Rule::exists(Categorie::class, 'id')->where('company_id', $this->companyId()),
            ],
            'price'        => ['required', 'numeric', 'min:0', 'max:999999999'],
            'photo'        => ['nullable', 'image', 'max:2048'],
        ];
    }

    protected function messages(): array
    {
        return [
            'name.required'         => 'Le nom du produit est obligatoire.',
            'name.max'              => 'Le nom ne peut pas dépasser 150 caractères.',
            'categorie_id.required' => 'Veuillez choisir une catégorie.',
            'categorie_id.exists'   => 'Cette catégorie est invalide.',
            'price.required'        => 'Le prix est obligatoire.',
            'price.numeric'         => 'Le prix doit être un nombre.',
            'price.min'             => 'Le prix ne peut pas être négatif.',
            'photo.image'           => 'Le fichier doit être une image.',
            'photo.max'             => 'L\'image ne doit pas dépasser 2 Mo.',
        ];
    }

    /** Ouvre le modal en mode création. */
    public function openCreate(): void
    {
        $this->resetForm();
        $this->dispatch('open-produit-modal');
    }

    /** Ouvre le modal en mode modification. */
    public function edit(int $id): void
    {
        $produit = $this->findProduit($id);

        $this->produitId     = $produit->id;
        $this->name          = $produit->name;
        $this->categorie_id  = $produit->categorie_id;
        $this->price         = (string) $produit->price;
        $this->currentPhoto  = $produit->path;
        $this->photo         = null;

        $this->resetValidation();
        $this->dispatch('open-produit-modal');
    }

    /** Crée ou met à jour le produit selon le mode actif. */
    public function save(): void
    {
        $this->name  = trim($this->name);
        $this->price = str_replace(',', '.', trim($this->price));

        $this->validate();

        $data = [
            'name'         => $this->name,
            'categorie_id' => $this->categorie_id,
            'price'        => $this->price,
        ];

        if ($this->photo) {
            $data['path'] = $this->storePhoto();
        }

        if ($this->produitId) {
            $produit = $this->findProduit($this->produitId);
            $ancienneImage = $produit->path;

            $produit->update($data);

            // Supprime l'ancienne image uniquement si une nouvelle a été envoyée
            if (isset($data['path'])) {
                $this->deletePhoto($ancienneImage);
            }

            $message = 'Produit modifié avec succès.';
        } else {
            $data['company_id'] = $this->companyId();
            Produit::create($data);

            $message = 'Produit ajouté avec succès.';
        }

        $this->resetForm();
        session()->flash('message', $message);
        $this->dispatch('produit-saved');
    }

    /** Supprime un produit et son image. */
    public function delete(int $id): void
    {
        $produit = $this->findProduit($id);
        $image   = $produit->path;

        try {
            $produit->delete();
        } catch (QueryException $e) {
            // Le produit est lié à d'autres données (ex. commandes) : on ne le supprime pas
            session()->flash(
                'error',
                'Suppression impossible : « ' . $produit->name . ' » est lié à des commandes existantes.'
            );
            return;
        }

        $this->deletePhoto($image);

        if ($this->produitId === $id) {
            $this->resetForm();
        }

        session()->flash('message', 'Produit supprimé avec succès.');
    }

    public function render()
    {
        $produits = Produit::query()
            ->with('categorie')
            ->where('company_id', $this->companyId())
            ->when($this->search !== '', function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%');
            })
            ->when($this->filterCategorie !== '', function ($query) {
                $query->where('categorie_id', $this->filterCategorie);
            })
            ->latest()
            ->paginate(10);

        $categories = Categorie::where('company_id', $this->companyId())
            ->orderBy('name')
            ->get();

        return view('livewire.product.products', compact('produits', 'categories'));
    }

    private function findProduit(int $id): Produit
    {
        return Produit::where('company_id', $this->companyId())->findOrFail($id);
    }

    private function storePhoto(): string
    {
        $fileName = Str::uuid() . '.' . $this->photo->extension();
        $this->photo->storeAs(self::FOLDER, $fileName, self::DISK);

        return $fileName;
    }

    private function deletePhoto(?string $fileName): void
    {
        if ($fileName) {
            Storage::disk(self::DISK)->delete(self::FOLDER . '/' . $fileName);
        }
    }

    private function companyId(): int
    {
        return Auth::user()->id;
    }

    private function resetForm(): void
    {
        $this->reset(['name', 'categorie_id', 'price', 'photo', 'currentPhoto', 'produitId']);
        $this->resetValidation();
    }
}