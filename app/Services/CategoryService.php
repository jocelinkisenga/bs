<?php 

declare(strict_types=1);

namespace App\Services;

use App\Models\Categorie;
use Illuminate\Support\Facades\Auth;

class CategoryService {

    public function category_with_products () {
       return Categorie::with('produits')->get();
    }
}