<?php

namespace App\Livewire\Depense;

use App\Enums\RoleEnum;
use App\Models\Depense as DepenseModel;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class Depenses extends Component
{
    use WithPagination;

    // Formulaire
    public string $user_id = '';      // bénéficiaire (string pour accepter le placeholder vide)
    public string $motif = '';
    public string $montant = '';
    public ?int $depenseId = null;    // null = création

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
        return [
            'user_id' => [
                'required',
                Rule::exists(User::class, 'id')->where('company_id', $this->companyId()),
            ],
            'motif'   => ['required', 'string', 'max:255'],
            'montant' => ['required', 'numeric', 'min:0.01', 'max:999999999'],
        ];
    }

    protected function messages(): array
    {
        return [
            'user_id.required' => 'Veuillez choisir un bénéficiaire.',
            'user_id.exists'   => 'Ce bénéficiaire est invalide.',
            'motif.required'   => 'Le motif est obligatoire.',
            'motif.max'        => 'Le motif ne peut pas dépasser 255 caractères.',
            'montant.required' => 'Le montant est obligatoire.',
            'montant.numeric'  => 'Le montant doit être un nombre.',
            'montant.min'      => 'Le montant doit être supérieur à 0.',
            'montant.max'      => 'Le montant est trop élevé.',
        ];
    }

    /** Ouvre le modal en mode création. */
    public function openCreate(): void
    {
        $this->resetForm();
        $this->dispatch('open-depense-modal');
    }

    /** Ouvre le modal en mode modification. */
    public function edit(int $id): void
    {
        $depense = $this->findDepense($id);

        $this->depenseId = $depense->id;
        $this->user_id   = (string) $depense->user_id;
        $this->motif     = $depense->motif;
        $this->montant   = (string) $depense->montant;

        $this->resetValidation();
        $this->dispatch('open-depense-modal');
    }

    /** Crée ou met à jour la dépense selon le mode actif. */
    public function save(): void
    {
        $this->motif   = trim($this->motif);
        $this->montant = str_replace(',', '.', trim($this->montant));

        $this->validate();

        $data = [
            'user_id' => (int) $this->user_id,
            'motif'   => $this->motif,
            'montant' => $this->montant,
        ];

        if ($this->depenseId) {
            $this->findDepense($this->depenseId)->update($data);
            $message = 'Dépense modifiée avec succès.';
        } else {
            DepenseModel::create($data + ['company_id' => $this->companyId()]);
            $message = 'Dépense ajoutée avec succès.';
        }

        $this->resetForm();
        session()->flash('message', $message);
        $this->dispatch('depense-saved');
    }

    /** Supprime une dépense. */
    public function delete(int $id): void
    {
        $this->findDepense($id)->delete();

        if ($this->depenseId === $id) {
            $this->resetForm();
        }

        session()->flash('message', 'Dépense supprimée avec succès.');
    }

    public function render()
    {
        $companyId = $this->companyId();

        $depenses = DepenseModel::query()
            ->with('user')
            ->where('company_id', $companyId)
            ->when($this->search !== '', function ($query) {
                $term = '%' . $this->search . '%';

                // Groupé entre parenthèses pour ne pas casser le filtre d'entreprise
                $query->where(function ($q) use ($term) {
                    $q->where('motif', 'like', $term)
                      ->orWhereHas('user', fn ($u) => $u->where('name', 'like', $term));
                });
            })
            ->latest()
            ->paginate(10);

        $base = DepenseModel::where('company_id', $companyId);

        $stats = [
            'total' => (float) (clone $base)->sum('montant'),
            'mois'  => (float) (clone $base)
                ->where('created_at', '>=', now()->startOfMonth())
                ->sum('montant'),
        ];

        $beneficiaires = User::where('company_id', $companyId)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('livewire.depense.depenses', compact('depenses', 'stats', 'beneficiaires'));
    }

    private function findDepense(int $id): DepenseModel
    {
        return DepenseModel::where('company_id', $this->companyId())->findOrFail($id);
    }

    private function companyId(): int
    {
        return Auth::user()->id;
    }

    private function resetForm(): void
    {
        $this->reset(['user_id', 'motif', 'montant', 'depenseId']);
        $this->resetValidation();
    }
}