<?php

namespace App\Livewire\Categorie;

use App\Enums\RoleEnum;
use App\Models\Categorie as ModelsCategorie;
use App\Models\Produit;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class Categorie extends Component
{
    use WithPagination;

    public string $name = '';
    public ?int $categorieId = null;
    public string $search = '';

    protected $queryString = [
        'search' => ['except' => ''],
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

    protected function rules(): array
    {
        $unique = Rule::unique(ModelsCategorie::class, 'name')
            ->where(fn ($query) => $query->where('company_id', $this->companyId()));

        if ($this->categorieId) {
            $unique->ignore($this->categorieId);
        }

        return [
            'name' => ['required', 'string', 'max:100', $unique],
        ];
    }

    protected function messages(): array
    {
        return [
            'name.required' => 'Le nom de la catégorie est obligatoire.',
            'name.max'      => 'Le nom ne peut pas dépasser 100 caractères.',
            'name.unique'   => 'Cette catégorie existe déjà.',
        ];
    }

    /** Ouvre le modal en mode création. */
    public function openCreate(): void
    {
        $this->resetForm();
        $this->dispatch('open-categorie-modal');
    }

    /** Ouvre le modal en mode modification. */
    public function edit(int $id): void
    {
        $categorie = $this->findCategorie($id);

        $this->categorieId = $categorie->id;
        $this->name = $categorie->name;
        $this->resetValidation();

        $this->dispatch('open-categorie-modal');
    }

    /** Crée ou met à jour la catégorie selon le mode actif. */
    public function save(): void
    {
        $this->name = trim($this->name);
        $this->validate();

        if ($this->categorieId) {
            $this->findCategorie($this->categorieId)->update([
                'name' => $this->name,
            ]);
            $message = 'Catégorie modifiée avec succès.';
        } else {
            ModelsCategorie::create([
                'name'       => $this->name,
                'company_id' => $this->companyId(),
            ]);
            $message = 'Catégorie ajoutée avec succès.';
        }

        $this->resetForm();
        session()->flash('message', $message);
        $this->dispatch('categorie-saved');
    }

    /** Supprime une catégorie, sauf si des produits y sont rattachés. */
    public function delete(int $id): void
    {
        $categorie = $this->findCategorie($id);

        if (Produit::where('categorie_id', $categorie->id)->exists()) {
            session()->flash(
                'error',
                'Suppression impossible : des produits sont rattachés à « ' . $categorie->name . ' ».'
            );
            return;
        }

        $categorie->delete();

        if ($this->categorieId === $id) {
            $this->resetForm();
        }

        session()->flash('message', 'Catégorie supprimée avec succès.');
    }

    public function render()
    {
        $categories = ModelsCategorie::query()
            ->where('company_id', $this->companyId())
            ->when($this->search !== '', function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%');
            })
            ->latest()
            ->paginate(10);

        return view('livewire.categorie.categorie', compact('categories'));
    }

    private function findCategorie(int $id): ModelsCategorie
    {
        return ModelsCategorie::where('company_id', $this->companyId())
            ->findOrFail($id);
    }

    private function companyId(): int
    {
        return Auth::user()->id;
    }

    private function resetForm(): void
    {
        $this->reset(['name', 'categorieId']);
        $this->resetValidation();
    }
}