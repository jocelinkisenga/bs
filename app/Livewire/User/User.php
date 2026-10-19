<?php

namespace App\Livewire\User;

use App\Enums\RoleEnum;
use App\Models\User as UserModel;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class User extends Component
{
    use WithPagination;

    // Formulaire
    public string $name = '';
    public string $phone = '';
    public string $email = '';
    public string $sexe = '';
    public string $role_id = '';
    public string $password = '';
    public ?int $userId = null;   // null = création

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

    /** Rôles attribuables depuis l'interface. */
    public static function roles(): array
    {
        return [
            RoleEnum::GERANT    => 'Gérant',
            RoleEnum::RECEPTEUR => 'Comptoiriste',
            RoleEnum::SERVER    => 'Serveur',
        ];
    }

    /** Valeurs possibles pour le sexe. */
    public static function sexes(): array
    {
        return [
            'homme' => 'Homme',
            'femme' => 'Femme',
        ];
    }

    protected function rules(): array
    {
        $emailRules = [];
        if ($this->email !== '') {
            $unique = Rule::unique(UserModel::class, 'email');
            if ($this->userId) {
                $unique->ignore($this->userId);
            }
            $emailRules = ['email', 'max:150', $unique];
        }

        return [
            'name'    => ['required', 'string', 'max:100'],
            'phone'   => ['required', 'string', 'max:20', 'regex:/^\+?[0-9 ]{6,20}$/'],
            'email'   => $emailRules,
            'sexe'    => ['required', Rule::in(array_keys(self::sexes()))],
            'role_id' => ['required', Rule::in(array_keys(self::roles()))],
            'password' => $this->userId
                ? ['nullable', 'string', 'min:6', 'max:100']
                : ['required', 'string', 'min:6', 'max:100'],
        ];
    }

    protected function messages(): array
    {
        return [
            'name.required'     => 'Le nom est obligatoire.',
            'name.max'          => 'Le nom ne peut pas dépasser 100 caractères.',
            'phone.required'    => 'Le téléphone est obligatoire.',
            'phone.regex'       => 'Le numéro de téléphone est invalide.',
            'email.email'       => 'L\'adresse e-mail est invalide.',
            'email.unique'      => 'Cette adresse e-mail est déjà utilisée.',
            'sexe.required'     => 'Veuillez indiquer le sexe.',
            'sexe.in'           => 'Le sexe sélectionné est invalide.',
            'role_id.required'  => 'Veuillez choisir un rôle.',
            'role_id.in'        => 'Le rôle sélectionné est invalide.',
            'password.required' => 'Le mot de passe est obligatoire.',
            'password.min'      => 'Le mot de passe doit contenir au moins 6 caractères.',
        ];
    }

    /** Ouvre le modal en mode création. */
    public function openCreate(): void
    {
        $this->resetForm();
        $this->dispatch('open-user-modal');
    }

    /** Ouvre le modal en mode modification. */
    public function edit(int $id): void
    {
        $user = $this->findUser($id);

        $this->userId  = $user->id;
        $this->name    = $user->name;
        $this->phone   = (string) $user->phone;
        $this->email   = (string) $user->email;
        $this->sexe    = (string) $user->sexe;
        $this->role_id = (string) $user->role_id;
        $this->password = '';

        $this->resetValidation();
        $this->dispatch('open-user-modal');
    }

    /** Crée ou met à jour le membre du personnel selon le mode actif. */
    public function save(): void
    {
        $this->name  = trim($this->name);
        $this->phone = trim($this->phone);
        $this->email = trim($this->email);

        $this->validate();

        if ($this->userId) {
            $user = $this->findUser($this->userId);

            $data = [
                'name'    => $this->name,
                'phone'   => $this->phone,
                'email'   => $this->email !== '' ? $this->email : null,
                'sexe'    => $this->sexe,
                // Un utilisateur ne peut pas changer son propre rôle
                'role_id' => $user->id === Auth::id() ? $user->role_id : (int) $this->role_id,
            ];

            if ($this->password !== '') {
                $data['password'] = Hash::make($this->password);
            }

            $user->update($data);
            $message = 'Membre du personnel modifié avec succès.';
        } else {
            UserModel::create([
                'name'       => $this->name,
                'phone'      => $this->phone,
                'email'      => $this->email !== '' ? $this->email : null,
                'sexe'       => $this->sexe,
                'role_id'    => (int) $this->role_id,
                'password'   => Hash::make($this->password),
                'company_id' => $this->companyId(),
            ]);
            $message = 'Membre du personnel ajouté avec succès.';
        }

        $this->resetForm();
        session()->flash('message', $message);
        $this->dispatch('user-saved');
    }

    /** Supprime un membre du personnel. */
    public function delete(int $id): void
    {
        if ($id === Auth::id()) {
            session()->flash('error', 'Vous ne pouvez pas supprimer votre propre compte.');
            return;
        }

        $user = $this->findUser($id);

        try {
            $user->delete();
        } catch (QueryException $e) {
            // Le membre a déjà enregistré des dépenses ou des commandes
            session()->flash(
                'error',
                'Suppression impossible : « ' . $user->name . ' » est lié à des enregistrements existants.'
            );
            return;
        }

        if ($this->userId === $id) {
            $this->resetForm();
        }

        session()->flash('message', 'Membre du personnel supprimé avec succès.');
    }

    public function render()
    {
        $companyId = $this->companyId();

        $personnel = UserModel::query()
            ->where('company_id', $companyId)
            ->when($this->search !== '', function ($query) {
                $term = '%' . $this->search . '%';
                $query->where(function ($q) use ($term) {
                    $q->where('name', 'like', $term)
                      ->orWhere('email', 'like', $term)
                      ->orWhere('phone', 'like', $term);
                });
            })
            ->latest()
            ->paginate(10);

        $parRole = UserModel::where('company_id', $companyId)
            ->selectRaw('role_id, COUNT(*) as total')
            ->groupBy('role_id')
            ->pluck('total', 'role_id');

        $stats = [
            'total'    => (int) $parRole->sum(),
            'gerants'  => (int) ($parRole[RoleEnum::GERANT] ?? 0),
            'serveurs' => (int) ($parRole[RoleEnum::SERVER] ?? 0),
        ];

        return view('livewire.user.user', [
            'personnel' => $personnel,
            'stats'     => $stats,
            'roles'     => self::roles(),
            'sexes'     => self::sexes(),
        ]);
    }

    /** Recherche un membre de l'entreprise. Un gérant ne peut pas toucher à un administrateur. */
    private function findUser(int $id): UserModel
    {
        $user = UserModel::where('company_id', $this->companyId())->findOrFail($id);

        abort_if(
            $user->role_id == RoleEnum::ADMIN && Auth::user()->role_id != RoleEnum::ADMIN,
            403
        );

        return $user;
    }

    private function companyId(): int
    {
        return Auth::user()->id;
    }

    private function resetForm(): void
    {
        $this->reset(['name', 'phone', 'email', 'sexe', 'role_id', 'password', 'userId']);
        $this->resetValidation();
    }
}