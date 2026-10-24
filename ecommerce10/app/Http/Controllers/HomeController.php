<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index() {
        $productModel = new Product();
        $products = $productModel->findAll();
        return view('home.index', compact('products'));
    }

    public function productDetail(int $id) {
        $productModel = new Product();
        $product = $productModel->findById($id);
        $links = [
            ['Home', '/'],
            ['Shop', '/shop'],
            [$product->title, $product->link],
        ];
        return view('product.description', compact('product', 'links'));
    }

    public function cart() {
        return view('cart.index');
    }
}
