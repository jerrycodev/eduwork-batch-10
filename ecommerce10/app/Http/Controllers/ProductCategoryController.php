<?php

namespace App\Http\Controllers;

use App\Models\ProductCategory;
use Illuminate\Http\Request;

class ProductCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $categories = ProductCategory::withCount('products')
            ->withSum('products', 'price')
            ->withSum('products', 'stock')
            ->get();

        return view('admin.categories.index', compact('categories'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $this->validate($request);
        if ($validated && $this->uniqueSlug($request)) {
            $productCategory = ProductCategory::create($request->all());

            return redirect()->route('product-categories.index')->with('success', 'Category created successfully.');
        }

        return redirect()->route('product-categories.index')->withErrors($validated)->withInput();
    }

    /**
     * Display the specified resource.
     */
    public function show(ProductCategory $productCategory)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ProductCategory $productCategory)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ProductCategory $productCategory)
    {
        $validated = $this->validate($request);
        if ($validated) {
            $productCategory->update($request->all());

            return redirect()->route('product-categories.index')->with('success', 'Category updated successfully.');
        }

        return redirect()->route('product-categories.index')->withErrors($validated)->withInput();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProductCategory $productCategory)
    {
        $productCategory->delete();

        return redirect()->route('product-categories.index')->with('success', 'Category deleted successfully.');
    }

    // Validattion
    public function validate(Request $request)
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:product_categories,slug',
        ]);
    }

    public function uniqueSlug(Request $request)
    {
        $slug = $request->slug;
        $category = ProductCategory::where('slug', $slug)->first();

        if ($category) {
            return false;
        }

        return true;
    }
}
