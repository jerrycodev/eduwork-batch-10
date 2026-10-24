<div class="bg-white">
    <header class="max-w-7xl mx-auto flex items-center justify-between h-20 px-6">
        <nav class="nav-menu" aria-label="Main">
            <ul class="nav-menu-list">
                <li class="nav-menu-item"><a class="nav-menu-link" href="/">Home</a></li>
                <li class="nav-menu-item"><a class="nav-menu-link" href="/">Shop</a></li>
            </ul>
        </nav>
        <a href="/">
            <h1 class="flex items-center gap-2 text-xl font-bold">
            {{-- <i data-lucide="shopping-bag"></i> --}}
            <span>
                {{config('app.name')}}
            </span>
        </h1>
        </a>
        <nav class="nav-menu" aria-label="Main">
            <ul class="nav-menu-list">
                <li class="nav-menu-item"><a class="nav-menu-link" href="/cart" data-variant="outline"
                        data-tooltip-trigger="tooltip-profile">
                        <div class="tooltip" id="tooltip-profile" popover="hint" role="tooltip">Shopping Bag</div>
                        <i class="font-bold" data-lucide="shopping-cart"></i>
                    </a></li>
                @auth
                    <li class="nav-menu-item"><a class="nav-menu-link" href="/login" data-variant="outline"
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
