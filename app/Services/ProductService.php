<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Produit;
use Illuminate\Support\Facades\Auth;

class ProductService {

    public function product_by_company() {
      return  Produit::latest()->get();
    }
    
}