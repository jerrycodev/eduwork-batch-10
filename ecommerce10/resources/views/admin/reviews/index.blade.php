<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div><h2 class="text-xl font-bold tracking-tight text-gray-900">Moderasi Ulasan</h2><p class="mt-1 text-sm text-gray-500">Tinjau ulasan pelanggan sebelum ditampilkan di halaman produk.</p></div>
            <span class="inline-flex w-fit rounded-full bg-amber-50 px-3 py-1.5 text-sm font-semibold text-amber-700">{{ $pendingCount }} menunggu</span>
        </div>
    </x-slot>
    <div class="py-8"><div class="mx-auto max-w-7xl space-y-5 px-4 sm:px-6 lg:px-8">
        @if (session('success'))<x-alert class="border border-green-200 bg-green-50 text-green-800">{{ session('success') }}</x-alert>@endif
        <div class="flex flex-wrap gap-2">
            @foreach (['pending' => 'Menunggu', 'approved' => 'Ditampilkan', 'all' => 'Semua'] as $key => $label)
                <a href="{{ route('reviews.index', ['status' => $key]) }}" class="rounded-lg px-4 py-2 text-sm font-semibold {{ $status === $key ? 'bg-indigo-600 text-white' : 'border border-gray-200 bg-white text-gray-600 hover:bg-gray-50' }}">{{ $label }}</a>
            @endforeach
        </div>
        <div class="space-y-4">
            @forelse ($reviews as $review)
                <article class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2"><h3 class="font-semibold text-gray-900">{{ $review->title ?: 'Ulasan produk' }}</h3><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $review->is_approved ? 'bg-green-50 text-green-700' : 'bg-amber-50 text-amber-700' }}">{{ $review->is_approved ? 'Ditampilkan' : 'Menunggu' }}</span></div>
                            <p class="mt-1 text-sm text-gray-500">{{ $review->product->name }} · oleh {{ $review->user->name }} · {{ $review->created_at->format('d M Y H:i') }}</p>
                            <p class="mt-2 text-sm font-semibold text-amber-600">★ {{ $review->rating }}/5</p>
                            <p class="mt-3 whitespace-pre-line text-sm leading-6 text-gray-700">{{ $review->comment }}</p>
                            @if ($review->media->isNotEmpty())
                                <div class="mt-4 grid max-w-2xl grid-cols-2 gap-3 sm:grid-cols-3">
                                    @foreach ($review->media as $media)
                                        @if ($media->media_type === 'image')
                                            <a href="{{ Storage::disk('public')->url($media->path) }}" target="_blank" rel="noopener"><img src="{{ Storage::disk('public')->url($media->path) }}" alt="Lampiran ulasan" class="aspect-square w-full rounded-lg border border-gray-200 object-cover"></a>
                                        @else
                                            <video controls preload="metadata" class="aspect-square w-full rounded-lg border border-gray-200 bg-black object-contain"><source src="{{ Storage::disk('public')->url($media->path) }}" type="{{ $media->mime_type }}"></video>
                                        @endif
                                    @endforeach
                                </div>
                            @endif
                        </div>
                        <div class="flex shrink-0 flex-wrap gap-2">
                            <form action="{{ route('reviews.update', $review) }}" method="POST">
                                @csrf @method('PATCH')
                                <input type="hidden" name="is_approved" value="{{ $review->is_approved ? 0 : 1 }}">
                                <button class="rounded-lg px-3 py-2 text-sm font-semibold {{ $review->is_approved ? 'border border-amber-200 text-amber-700 hover:bg-amber-50' : 'bg-indigo-600 text-white hover:bg-indigo-700' }}">{{ $review->is_approved ? 'Sembunyikan' : 'Setujui & Tampilkan' }}</button>
                            </form>
                            <form action="{{ route('reviews.destroy', $review) }}" method="POST" onsubmit="return confirm('Hapus ulasan ini secara permanen?')">
                                @csrf @method('DELETE')<button class="rounded-lg border border-red-200 px-3 py-2 text-sm font-semibold text-red-600 hover:bg-red-50">Hapus</button>
                            </form>
                        </div>
                    </div>
                </article>
            @empty
                <div class="rounded-xl border border-dashed border-gray-300 bg-white px-6 py-16 text-center"><p class="font-semibold text-gray-900">Belum ada ulasan pada kategori ini</p><p class="mt-1 text-sm text-gray-500">Ulasan baru dari pelanggan akan muncul di sini.</p></div>
            @endforelse
        </div>
        @if ($reviews->hasPages()){{ $reviews->links() }}@endif
    </div></div>
</x-app-layout>
