<?php

namespace App\Livewire\Categorie;

use App\Models\Categorie as ModelsCategorie;
use App\Utilities\FormatDate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;

class Categorie extends Component
{
    public  $name;
    public $data;
    public function render()
    {
        $this->data = ModelsCategorie::latest()->whereCompany_id(Auth::user()->company_id)->get();
        return view('livewire.categorie.categorie');
    }
     
    public function reset_fields(){
        $this->name = "";
    }

    public function store(){
            $validate = $this->validate(['name'=>'required']);

            ModelsCategorie::create(
                [
                    "name" => $validate["name"],
                    "company_id" => Auth::user()->company_id
                ]);
            session()->flash('message','categorie created successfully');
            $this->reset_fields();
            $this->dispatch('categorieStore');
            $this->dispatch("close-modal");
    }

    public function modifier ($id){
        $categorie = ModelsCategorie::find($id);
        $categorie->update([
            "name"=>$this->name
        ]);

        session()->flash('message','categorie modifier avec succès');

    }
}
