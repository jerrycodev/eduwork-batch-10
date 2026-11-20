<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Menampilkan daftar data dengan filter dan relasi yang diperlukan untuk kebutuhan operasional.
     */
    public function index()
    {
        $categorySlug = request('category');

        $products = Product::query()
            ->where('is_active', true)
            ->when($categorySlug, fn (Builder $query, string $slug) => $query->whereHas(
                'category',
                fn (Builder $categoryQuery) => $categoryQuery->where('slug', $slug),
            ))
            ->with(['category', 'options.values', 'variants'])
            ->latest()
            ->paginate(8)
            ->withQueryString();

        $activeProducts = fn (Builder $query) => $query->where('is_active', true);
        $categories = ProductCategory::query()
            ->whereHas('products', $activeProducts)
            ->withCount(['products' => $activeProducts])
            ->orderBy('name')
            ->get();

        return view('home.index', compact('products', 'categories'));
    }

    public function productDetail(string $product)
    {
        $product = Product::query()->where('is_active', true)->where('slug', $product)->firstOrFail();
        $session = session();
        if (! $session->has("product_{$product->id}_viewed")) {
            $product->increment('click');
            $session->put("product_{$product->id}_viewed", true);
        }

        $product->load(['category', 'images', 'options.values', 'variants.optionValues.option', 'variants.images']);
        $isWishlisted = auth()->check() && auth()->user()->role !== 'admin'
            ? auth()->user()->wishlistItems()->where('product_id', $product->id)->exists()
            : false;
        $product->loadAvg(['approvedReviews as average_rating' => fn ($query) => $query->where('is_approved', true)], 'rating');
        $product->loadCount(['approvedReviews as reviews_count' => fn ($query) => $query->where('is_approved', true)]);
        $reviews = $product->approvedReviews()->with(['user', 'media'])->latest()->paginate(10, ['*'], 'reviews_page');
        $relatedProducts = Product::query()
            ->where('category_id', $product->category_id)
            ->whereKeyNot($product->id)
            ->where('is_active', true)
            ->where('stock', '>', 0)
            ->with(['images', 'options.values', 'variants'])
            ->withAvg(['approvedReviews as average_rating' => fn ($query) => $query->where('is_approved', true)], 'rating')
            ->withCount(['approvedReviews as reviews_count' => fn ($query) => $query->where('is_approved', true)])
            ->latest()
            ->limit(4)
            ->get();

        $links = [
            ['Home', route('home')],
            ['Shop', route('shop')],
            [$product->category->name, route('shop', ['category' => $product->category->slug])],
            [$product->name, route('product-detail', $product->slug)],
        ];

        return view('product.description', compact('product', 'links', 'reviews', 'relatedProducts', 'isWishlisted'));
    }

    public function shop(Request $request)
    {
        $validated = $request->validate([
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'max_price' => ['nullable', 'numeric', 'min:0', 'gte:min_price'],
            'sort' => ['nullable', 'in:latest,price_asc,price_desc,rating_desc,rating_asc,name_asc,name_desc'],
        ]);

        $search = trim((string) $request->input('search', ''));
        $category = $request->input('category');
        $sort = $validated['sort'] ?? 'latest';

        $query = Product::query()
            ->where('is_active', true)
            ->where('stock', '>', 0)
            ->with(['category', 'images', 'options.values', 'variants'])
            ->withAvg(['approvedReviews as reviews_avg_rating' => fn ($q) => $q->where('is_approved', true)], 'rating')
            ->withCount(['approvedReviews as reviews_count' => fn ($q) => $q->where('is_approved', true)]);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%'.$search.'%')
                    ->orWhere('description', 'like', '%'.$search.'%');
            });
        }

        if ($category) {
            $query->whereHas('category', fn ($q) => $q->where('slug', trim($category)));
        }

        if (isset($validated['min_price'])) {
            $query->where('price', '>=', $validated['min_price']);
        }
        if (isset($validated['max_price'])) {
            $query->where('price', '<=', $validated['max_price']);
        }

        match ($sort) {
            'price_asc' => $query->orderBy('price'),
            'price_desc' => $query->orderByDesc('price'),
            'rating_desc' => $query->orderByDesc('reviews_avg_rating')->orderByDesc('reviews_count'),
            'rating_asc' => $query->orderBy('reviews_avg_rating')->orderByDesc('reviews_count'),
            'name_asc' => $query->orderBy('name'),
            'name_desc' => $query->orderByDesc('name'),
            default => $query->latest(),
        };

        $products = $query->paginate(12)->withQueryString();
        $categories = ProductCategory::withCount(['products' => fn ($q) => $q->where('is_active', true)->where('stock', '>', 0)])->get();
        $priceRange = Product::query()->where('is_active', true)->where('stock', '>', 0)->selectRaw('MIN(price) as min_price, MAX(price) as max_price')->first();

        return view('home.shop', compact('products', 'search', 'category', 'categories', 'sort', 'priceRange'));
    }
}
