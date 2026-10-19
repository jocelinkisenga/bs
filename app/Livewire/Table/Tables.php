<?php

namespace App\Livewire\Table;

use App\Enums\RoleEnum;
use App\Models\Table as TableModel;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class Tables extends Component
{
    use WithPagination;

    // Formulaire
    public string $name = '';
    public string $places = '';
    public ?int $tableId = null;   // null = création

    // Liste
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
        $unique = Rule::unique(TableModel::class, 'name')
            ->where(fn ($query) => $query->where('company_id', $this->companyId()));

        if ($this->tableId) {
            $unique->ignore($this->tableId);
        }

        return [
            'name'   => ['required', 'string', 'max:100', $unique],
            'places' => ['required', 'integer', 'min:1', 'max:999'],
        ];
    }

    protected function messages(): array
    {
        return [
            'name.required'   => 'Le nom de la table est obligatoire.',
            'name.max'        => 'Le nom ne peut pas dépasser 100 caractères.',
            'name.unique'     => 'Cette table existe déjà.',
            'places.required' => 'Le nombre de places est obligatoire.',
            'places.integer'  => 'Le nombre de places doit être un entier.',
            'places.min'      => 'Il faut au moins une place.',
            'places.max'      => 'Le nombre de places ne peut pas dépasser 999.',
        ];
    }

    /** Ouvre le modal en mode création. */
    public function openCreate(): void
    {
        $this->resetForm();
        $this->dispatch('open-table-modal');
    }

    /** Ouvre le modal en mode modification. */
    public function edit(int $id): void
    {
        $table = $this->findTable($id);

        $this->tableId = $table->id;
        $this->name    = $table->name;
        $this->places  = (string) $table->places;

        $this->resetValidation();
        $this->dispatch('open-table-modal');
    }

    /** Crée ou met à jour la table selon le mode actif. */
    public function save(): void
    {
        $this->name   = trim($this->name);
        $this->places = trim($this->places);

        $this->validate();

        $data = [
            'name'   => $this->name,
            'places' => (int) $this->places,
        ];

        if ($this->tableId) {
            $this->findTable($this->tableId)->update($data);
            $message = 'Table modifiée avec succès.';
        } else {
            TableModel::create($data + ['company_id' => $this->companyId()]);
            $message = 'Table ajoutée avec succès.';
        }

        $this->resetForm();
        session()->flash('message', $message);
        $this->dispatch('table-saved');
    }

    /** Supprime une table, sauf si elle est liée à des commandes. */
    public function delete(int $id): void
    {
        $table = $this->findTable($id);

        try {
            $table->delete();
        } catch (QueryException $e) {
            session()->flash(
                'error',
                'Suppression impossible : « ' . $table->name . ' » est liée à des commandes.'
            );
            return;
        }

        if ($this->tableId === $id) {
            $this->resetForm();
        }

        session()->flash('message', 'Table supprimée avec succès.');
    }

    public function render()
    {
        $tables = TableModel::query()
            ->where('company_id', $this->companyId())
            ->when($this->search !== '', function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%');
            })
            ->latest()
            ->paginate(10);

        $stats = [
            'total'  => TableModel::where('company_id', $this->companyId())->count(),
            'places' => (int) TableModel::where('company_id', $this->companyId())->sum('places'),
        ];

        return view('livewire.table.tables', compact('tables', 'stats'));
    }

    private function findTable(int $id): TableModel
    {
        return TableModel::where('company_id', $this->companyId())->findOrFail($id);
    }

    private function companyId(): int
    {
        return Auth::user()->id;
    }

    private function resetForm(): void
    {
        $this->reset(['name', 'places', 'tableId']);
        $this->resetValidation();
    }
}