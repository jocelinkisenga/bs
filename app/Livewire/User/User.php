<?php

namespace App\Livewire\User;

use App\Models\Role;
use App\Models\User as ModelsUser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;

class User extends Component
{
    public $role_name;
    public $roles;
    public $name;
    public $sexe;
    public $role_id;
    public $phone;
    public $password;
    public $email;
    public $users;
    public function render()
    {
    $this->roles = Role::all();
    $this->users = ModelsUser::latest()->get();
       
        return view('livewire.user.user');
    }


    public function store_role()
    {
        $valide = $this->validate([
            'role_name' => ['required', 'unique:roles,name']
        ]);

        Role::create([
            'name' => $this->role_name
        ]);
        $this->reset_fieds();
    }

    public function ajouter()
    {
        if (!empty($this->password)) {
            $password = Hash::make($this->password);

            ModelsUser::create([
                "name" => $this->name,
                "phone" => $this->phone,
                "sexe" => $this->sexe,
                "role_id" => $this->role_id,
                "password" => $password,
                "email" => $this->email,
                "company_id" => Auth::user()->id
            ]);
            $this->reset_fieds();
            $this->dispatch("close-modal");
        } else {
            ModelsUser::create([
                "name" => $this->name,
                "phone" => $this->phone,
                "sexe" => $this->sexe,
                "role_id" => $this->role_id,
            ]);
            $this->reset_fieds();
            $this->dispatch('close-modal');
        }
    }

    public function delete (int $userId) {
        $user = \App\Models\User::findOrFail($userId);
        $user->delete();
    }

    private function reset_fieds()
    {
        $this->role_name = "";
        $this->name = "";
        $this->phone = "";
        $this->sexe = "";
        $this->role_id = "";
        $this->password = "";
        $this->email = "";
    }

    
}
