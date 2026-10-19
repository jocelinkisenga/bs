<?php

namespace App\Livewire\Reduction;

use App\Repositorie\CommandeRepositorie;
use App\Repositorie\ReductionRepositorie;
use Livewire\Component;

class ReductionComponent extends Component
{
    public $reduction;
    public $reduction_id;
    public  $percent;
    protected $repo_commande;
    public $reduction_repo;

    public function __construct()
    {
        $this->repo_commande = new CommandeRepositorie;
        $this->reduction_repo = new ReductionRepositorie;
    }

    public function reset_field(){
        $this->percent =  "";
    }

    public function render()
    {
        $this->reduction = $this->repo_commande->facture($this->reduction_id);
        return view('livewire.reduction.reduction-component');
    }

    public function update($id){
       
       $reduction_modifier = $this->reduction_repo->modifier($id, $this->percent);
        $this->reset_field();
        if($reduction_modifier){
            $this->dispach("reduced");
        }
       
    }
}
