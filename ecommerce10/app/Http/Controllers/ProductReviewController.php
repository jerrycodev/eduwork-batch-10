<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductReview;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ProductReviewController extends Controller
{
    public function store(Request $request, Product $product): RedirectResponse
    {
        abort_unless(Auth::user()?->role !== 'admin', 403);

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'title' => ['nullable', 'string', 'max:120'],
            'comment' => ['required', 'string', 'min:5', 'max:3000'],
            'media' => ['nullable', 'array', 'max:5'],
            'media.*' => ['file', 'mimes:jpg,jpeg,png,webp,mp4,mov,webm', 'max:30720'],
        ], [
            'media.max' => 'Maksimal 5 file untuk setiap ulasan.',
            'media.*.mimes' => 'File harus berupa gambar JPG, PNG, WEBP atau video MP4, MOV, WEBM.',
            'media.*.max' => 'Ukuran setiap file maksimal 30 MB.',
        ]);

        $user = $request->user();
        $hasPaidPurchase = $user->orders()
            ->where('payment_status', 'paid')
            ->where('status', 'completed')
            ->whereHas('orderItems', fn ($query) => $query->where('product_id', $product->id))
            ->exists();

        if (! $hasPaidPurchase) {
            throw ValidationException::withMessages([
                'review' => 'Anda hanya dapat mengulas produk dari pesanan yang sudah selesai dan dibayar.',
            ]);
        }

        if ($user->reviews()->where('product_id', $product->id)->exists()) {
            throw ValidationException::withMessages([
                'review' => 'Anda sudah pernah mengirim ulasan untuk produk ini.',
            ]);
        }

        $storedPaths = [];

        try {
            $review = DB::transaction(function () use ($validated, $request, $product, &$storedPaths) {
                $review = $request->user()->reviews()->create([
                    'product_id' => $product->id,
                    'rating' => $validated['rating'],
                    'title' => $validated['title'] ?? null,
                    'comment' => $validated['comment'],
                    'is_approved' => false,
                ]);

                foreach ($request->file('media', []) as $index => $file) {
                    $path = $file->store('product-reviews/'.$review->id, 'public');
                    $storedPaths[] = $path;
                    $review->media()->create([
                        'path' => $path,
                        'media_type' => str_starts_with($file->getMimeType(), 'video/') ? 'video' : 'image',
                        'mime_type' => $file->getMimeType(),
                        'size' => $file->getSize(),
                        'sort_order' => $index,
                    ]);
                }

                return $review;
            });
        } catch (\Throwable $exception) {
            Storage::disk('public')->delete($storedPaths);
            throw $exception;
        }

        return back()->with('review_success', 'Ulasan berhasil dikirim dan menunggu persetujuan admin.');
    }

    public function adminIndex(Request $request)
    {
        $status = $request->input('status', 'pending');
        abort_unless(in_array($status, ['pending', 'approved', 'all'], true), 404);

        $reviews = ProductReview::query()
            ->with(['product', 'user', 'media'])
            ->when($status === 'pending', fn ($query) => $query->where('is_approved', false))
            ->when($status === 'approved', fn ($query) => $query->where('is_approved', true))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $pendingCount = ProductReview::query()->where('is_approved', false)->count();

        return view('admin.reviews.index', compact('reviews', 'status', 'pendingCount'));
    }

    public function update(Request $request, ProductReview $review): RedirectResponse
    {
        $validated = $request->validate(['is_approved' => ['required', 'boolean']]);
        $approved = (bool) $validated['is_approved'];
        $review->update([
            'is_approved' => $approved,
            'approved_at' => $approved ? ($review->approved_at ?? now()) : null,
        ]);

        return back()->with('success', $approved ? 'Ulasan berhasil ditampilkan.' : 'Ulasan berhasil disembunyikan.');
    }

    public function destroy(ProductReview $review): RedirectResponse
    {
        Storage::disk('public')->delete($review->media()->pluck('path')->all());
        $review->delete();

        return back()->with('success', 'Ulasan berhasil dihapus.');
    }
}
