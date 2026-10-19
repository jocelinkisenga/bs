<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(){
        return view('Pages.produit');
    }

    public function show(int $id){
        return view('Pages.productDetail',compact('id'));
    }
}
