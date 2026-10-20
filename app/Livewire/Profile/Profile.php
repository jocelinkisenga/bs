<?php

namespace App\Livewire\Profile;

use App\Enums\RoleEnum;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Component;

class Profile extends Component
{
    private const ONGLETS = ['infos', 'email', 'mot_de_passe', 'suppression'];

    public string $onglet = 'infos';
    public ?string $succes = null;

    // Informations personnelles
    public string $name = '';
    public string $phone = '';
    public string $sexe = '';

    // Changement d'e-mail
    public string $email = '';
    public string $email_password = '';

    // Changement de mot de passe
    public string $current_password = '';
    public string $new_password = '';
    public string $new_password_confirmation = '';

    // Suppression du compte
    public string $delete_password = '';
    public bool $delete_confirm = false;

    public function mount(): void
    {
        $user = Auth::user();

        $this->name  = (string) $user->name;
        $this->phone = (string) $user->phone;
        $this->sexe  = (string) $user->sexe;
        $this->email = (string) $user->email;
    }

    public function changerOnglet(string $onglet): void
    {
        if (in_array($onglet, self::ONGLETS, true)) {
            $this->onglet = $onglet;
            $this->succes = null;
            $this->resetValidation();
        }
    }

    /* ==========================================================
     |  Informations personnelles
     ========================================================== */

    public function sauverInfos(): void
    {
        $this->name  = trim($this->name);
        $this->phone = trim($this->phone);

        $this->validate([
            'name'  => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'max:20', 'regex:/^\+?[0-9 ]{6,20}$/'],
            'sexe'  => ['required', Rule::in(['homme', 'femme'])],
        ], [
            'name.required'  => 'Le nom est obligatoire.',
            'name.max'       => 'Le nom ne peut pas dépasser 100 caractères.',
            'phone.required' => 'Le téléphone est obligatoire.',
            'phone.regex'    => 'Le numéro de téléphone est invalide.',
            'sexe.required'  => 'Veuillez indiquer le sexe.',
            'sexe.in'        => 'La valeur choisie est invalide.',
        ]);

        Auth::user()->update([
            'name'  => $this->name,
            'phone' => $this->phone,
            'sexe'  => $this->sexe,
        ]);

        $this->terminer('Vos informations ont été mises à jour.');
    }

    /* ==========================================================
     |  Adresse e-mail
     ========================================================== */

    public function sauverEmail(): void
    {
        $this->email = Str::lower(trim($this->email));
        $user = Auth::user();

        $this->validate([
            'email' => [
                'required', 'email', 'max:150',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'email_password' => ['required', 'current_password'],
        ], [
            'email.required'            => "L'adresse e-mail est obligatoire.",
            'email.email'               => "L'adresse e-mail est invalide.",
            'email.unique'              => 'Cette adresse e-mail est déjà utilisée.',
            'email_password.required'   => 'Saisissez votre mot de passe pour confirmer.',
            'email_password.current_password' => 'Mot de passe incorrect.',
        ]);

        $user->update(['email' => $this->email]);

        $this->terminer('Votre adresse e-mail a été mise à jour.');
    }

    /* ==========================================================
     |  Mot de passe
     ========================================================== */

    public function sauverMotDePasse(): void
    {
        $this->validate([
            'current_password' => ['required', 'current_password'],
            'new_password'     => [
                'required', 'confirmed', 'different:current_password',
                Password::min(8)->letters()->numbers(),
            ],
        ], [
            'current_password.required'         => 'Saisissez votre mot de passe actuel.',
            'current_password.current_password' => 'Mot de passe actuel incorrect.',
            'new_password.required'             => 'Saisissez un nouveau mot de passe.',
            'new_password.confirmed'            => 'Les deux mots de passe ne correspondent pas.',
            'new_password.different'            => 'Le nouveau mot de passe doit être différent de l\'actuel.',
        ]);

        Auth::user()->update([
            'password' => Hash::make($this->new_password),
        ]);

        // Déconnecte les autres appareils et garde la session courante active
        Auth::logoutOtherDevices($this->new_password);

        $this->terminer('Mot de passe modifié. Les autres appareils ont été déconnectés.');
    }

    /* ==========================================================
     |  Suppression du compte
     ========================================================== */

    public function supprimerCompte(): void
    {
        $user = Auth::user();

        $this->validate([
            'delete_password' => ['required', 'current_password'],
            'delete_confirm'  => ['accepted'],
        ], [
            'delete_password.required'         => 'Saisissez votre mot de passe pour confirmer.',
            'delete_password.current_password' => 'Mot de passe incorrect.',
            'delete_confirm.accepted'          => 'Cochez la case pour confirmer la suppression.',
        ]);

        // Une entreprise doit toujours avoir un administrateur
        if ((int) $user->role_id === RoleEnum::ADMIN) {
            $autresAdmins = User::where('company_id', $user->company_id)
                ->where('role_id', RoleEnum::ADMIN)
                ->whereKeyNot($user->id)
                ->count();

            if ($autresAdmins === 0) {
                $this->addError(
                    'delete_password',
                    "Vous êtes le dernier administrateur de l'entreprise. Désignez un autre administrateur avant de supprimer votre compte."
                );
                return;
            }
        }

        try {
            $user->delete();
        } catch (QueryException) {
            // Le compte est lié à des commandes ou dépenses : on conserve l'historique et on anonymise
            $user->update([
                'name'     => 'Compte supprimé',
                'email'    => null,
                'phone'    => '',
                'password' => Hash::make(Str::random(64)),
            ]);
        }

        Auth::logout();
        session()->invalidate();
        session()->regenerateToken();

        $this->redirect(route('login'), navigate: false);
    }

    /* ==========================================================
     |  Utilitaires
     ========================================================== */

    private function terminer(string $message): void
    {
        $this->succes = $message;
        $this->resetValidation();
        $this->reset([
            'email_password',
            'current_password', 'new_password', 'new_password_confirmation',
            'delete_password', 'delete_confirm',
        ]);
    }

    public static function libelleRole(int $role): string
    {
        return match ($role) {
            RoleEnum::ADMIN     => 'Administrateur',
            RoleEnum::GERANT    => 'Gérant',
            RoleEnum::RECEPTEUR => 'Comptoiriste',
            RoleEnum::SERVER    => 'Serveur',
            default             => 'Non défini',
        };
    }

    public function render()
    {
        $user = Auth::user();

        // Après suppression, la requête se termine sans utilisateur connecté
        if (! $user) {
            return view('livewire.profile.profile', [
                'user'         => null,
                'roleLabel'    => '',
                'membreDepuis' => '',
            ]);
        }

        return view('livewire.profile.profile', [
            'user'         => $user,
            'roleLabel'    => self::libelleRole((int) $user->role_id),
            'membreDepuis' => $user->created_at?->format('d/m/Y') ?? '—',
        ]);
    }
}