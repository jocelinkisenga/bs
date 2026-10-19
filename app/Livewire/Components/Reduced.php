<?php

namespace App\Livewire\Components;

use App\Repositorie\ReductionRepositorie;
use Livewire\Component;

class Reduced extends Component
{
    public $reductions;
    protected $reduction_repo;
    protected $listeners = ["reduced" => 'render'];

    public function __construct()
    {
        $this->reduction_repo = new ReductionRepositorie;
    }
    public function render()
    {
        $this->reductions = $this->reduction_repo->reductions();
        return view('livewire.components.reduced');
    }
}
