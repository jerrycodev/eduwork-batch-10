<div class="bg-white">
    <header
        class="mx-auto flex min-h-16 max-w-7xl items-center justify-between gap-3 px-4 py-3 sm:min-h-20 sm:gap-6 sm:px-6 lg:px-8">
        <a href="/">
            <h1 class="flex items-center gap-2 text-base font-bold sm:text-xl">
                {{-- <i data-lucide="shopping-bag"></i> --}}
                <span>
                    {{ config('app.name') }}
                </span>
            </h1>
        </a>
        <nav class="nav-menu min-w-0" aria-label="Main">
            <ul class="nav-menu-list flex-wrap gap-2 sm:gap-4">
                <li class="nav-menu-item"><a class="nav-menu-link" href="{{ route('home') }}">Home</a></li>
                <li class="nav-menu-item"><a class="nav-menu-link" href="{{ route('shop') }}">Shop</a></li>
                @if(Auth::check())
                    <li class="nav-menu-item"><a class="nav-menu-link" href="{{ route('orders.customer.index') }}">Orders</a></li>
                @endif
            </ul>
        </nav>
        <nav class="nav-menu min-w-0" aria-label="Main">
            <ul class="nav-menu-list flex-wrap gap-2 sm:gap-4">
                <li class="nav-menu-item"><a class="nav-menu-link" href="/cart" data-variant="outline"
                        data-tooltip-trigger="tooltip-profile">
                        <div class="tooltip" id="tooltip-profile" popover="hint" role="tooltip">Shopping Bag</div>
                        <i class="font-bold" data-lucide="shopping-cart"></i>
                    </a></li>
                @auth
                    <li class="nav-menu-item"><a class="nav-menu-link" href="/profile" data-variant="outline"
                            data-tooltip-trigger="tooltip-profile">
                            <div class="tooltip" id="tooltip-profile" popover="hint" role="tooltip">Profile</div>
                            <i class="font-bold" data-lucide="user-round"></i>
                        </a></li>
                @else
                    <li class="nav-menu-item"><a class="nav-menu-link" href="/login" data-variant="outline"
                            data-tooltip-trigger="tooltip-login">
                            <div class="tooltip" id="tooltip-login" popover="hint" role="tooltip">Login</div>
                            <i class="font-bold" data-lucide="log-in"></i>
                        </a></li>
                @endauth
            </ul>
        </nav>
    </header>

</div>
