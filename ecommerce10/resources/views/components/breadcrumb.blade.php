@props(['links' => []])

<nav aria-label="Breadcrumb" class="mb-6 text-sm text-muted-foreground bg-neutral-100 px-4 py-2 rounded-xl">
    <ol class="flex items-center space-x-2">
        @foreach ($links as $link)
            @if ($loop->last)
                {{-- Item Terakhir (Halaman Aktif / Tidak Bisa Diklik) --}}
                <li class="text-foreground font-medium" aria-current="page">
                    {{ $link[0] }}
                </li>
            @else
                {{-- Item Link --}}
                <li>
                    <a href="{{ $link[1] }}" class="hover:text-foreground transition-colors opacity-50">
                        {{ $link[0] }}
                    </a>
                </li>
                {{-- Pemisah / Chevron --}}
                <li>
                    <i data-lucide="chevron-right" class="h-4 w-4 opacity-50"></i>
                </li>
            @endif
        @endforeach
    </ol>
</nav>
