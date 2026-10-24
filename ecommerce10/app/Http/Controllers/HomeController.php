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
            ->when($categorySlug, fn (Builder $query, string $slug) => $query->whereHas(
                'category',
                fn (Builder $query) => $query->where('slug', $slug),
            ))
            ->paginate(8)
            ->withQueryString();

        $categories = ProductCategory::withCount('products')->get();

        return view('home.index', compact('products', 'categories'));
    }
}
