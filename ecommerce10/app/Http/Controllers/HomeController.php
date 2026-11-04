<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Database\Eloquent\Builder;

class HomeController extends Controller
{
    public function index()
    {
        $categorySlug = request('category');

        $products = Product::query()
            ->when($categorySlug, fn(Builder $query, string $slug) => $query->whereHas(
                'category',
                fn(Builder $query) => $query->where('slug', $slug),
            ))
            ->paginate(8)
            ->withQueryString();

        $categories = ProductCategory::withCount('products')->get();

        return view('home.index', compact('products', 'categories'));
    }

    public function productDetail(Product $product)
    {
        $session = session();
        if (!$session->has("product_{$product->id}_viewed")) {
            $product->increment('click');
            $session->put("product_{$product->id}_viewed", true);
        }
        $product->load('category');

        $links = [
            ['Home', route('home')],
            ['Shop', route('shop')],
            [$product->category->name, route('shop', ["category" => $product->category->slug])],
            [$product->name, route('product-detail', $product)],
        ];

        return view('product.description', compact('product', 'links'));
    }
}
