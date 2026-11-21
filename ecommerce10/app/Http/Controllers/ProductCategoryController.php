<?php

namespace App\Http\Controllers;

use App\Models\ProductCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/** Mengelola kategori katalog, termasuk penyimpanan dan pembersihan file gambar. */
class ProductCategoryController extends Controller
{
    /**
     * Menampilkan daftar data dengan filter dan relasi yang diperlukan untuk kebutuhan operasional.
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
     * Menyiapkan halaman pembuatan data baru.
     */
    public function create()
    {
        return redirect()->route('product-categories.index');
    }

    /**
     * Memvalidasi dan memproses permintaan pembuatan data baru sesuai aturan bisnis.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->merge([
            'slug' => Str::slug($request->input('slug') ?: $request->input('name')),
        ]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:product_categories,slug'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [
            'name.required' => 'Nama kategori wajib diisi.',
            'name.string' => 'Nama kategori harus berupa teks.',
            'name.max' => 'Nama kategori maksimal 255 karakter.',
            'slug.required' => 'Slug kategori wajib diisi.',
            'slug.string' => 'Slug kategori tidak valid.',
            'slug.max' => 'Slug kategori maksimal 255 karakter.',
            'slug.unique' => 'Slug tersebut sudah digunakan. Silakan gunakan nama kategori lain.',
            'image.image' => 'File kategori harus berupa gambar.',
            'image.mimes' => 'Format gambar harus JPG, PNG, atau WebP.',
            'image.max' => 'Ukuran gambar maksimal 5 MB.',
        ]);

        unset($validated['image']);

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('categories', 'public');
        }

        ProductCategory::create($validated);

        return redirect()->route('product-categories.index')->with('success', 'Category created successfully.');
    }

    /**
     * Menampilkan detail data beserta relasi yang dibutuhkan oleh halaman.
     */
    public function show(ProductCategory $productCategory)
    {
        return redirect()->route('product-categories.index');
    }

    /**
     * Menyiapkan halaman perubahan data yang sudah ada.
     */
    public function edit(ProductCategory $productCategory)
    {
        return redirect()->route('product-categories.index');
    }

    /**
     * Memvalidasi dan menyimpan perubahan data dengan tetap menjaga aturan bisnis.
     */
    public function update(Request $request, ProductCategory $productCategory): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:product_categories,slug,' . $productCategory->id],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [
            'name.required' => 'Nama kategori wajib diisi.',
            'name.string' => 'Nama kategori harus berupa teks.',
            'name.max' => 'Nama kategori maksimal 255 karakter.',
            'slug.required' => 'Slug kategori wajib diisi.',
            'slug.string' => 'Slug kategori tidak valid.',
            'slug.max' => 'Slug kategori maksimal 255 karakter.',
            'slug.unique' => 'Slug tersebut sudah digunakan. Silakan gunakan nama kategori lain.',
            'image.image' => 'File kategori harus berupa gambar.',
            'image.mimes' => 'Format gambar harus JPG, PNG, atau WebP.',
            'image.max' => 'Ukuran gambar maksimal 5 MB.',
        ]);

        unset($validated['image']);

        $oldImage = $productCategory->image;
        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('categories', 'public');
        }

        $productCategory->update($validated);

        if (isset($validated['image']) && $oldImage) {
            Storage::disk('public')->delete($oldImage);
        }

        return redirect()->route('product-categories.index')->with('success', 'Category updated successfully.');
    }

    /** Kategori yang masih dipakai produk tidak boleh dihapus agar relasi katalog tetap valid. */
    public function destroy(ProductCategory $productCategory): RedirectResponse
    {
        if ($productCategory->products()->exists()) {
            return back()->withErrors(['category' => 'Kategori tidak dapat dihapus karena masih digunakan oleh produk.']);
        }

        if ($productCategory->image) {
            Storage::disk('public')->delete($productCategory->image);
        }

        $productCategory->delete();

        return redirect()->route('product-categories.index')->with('success', 'Category deleted successfully.');
    }
}
