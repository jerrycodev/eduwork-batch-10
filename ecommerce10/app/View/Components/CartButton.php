<?php

namespace App\View\Components;

use App\Models\Cart;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\Component;

class CartButton extends Component
{
    public int $itemCount;

    /**
     * Create a new component instance.
     */
    public function __construct()
    {
        $customer = Auth::guard('customer');
        $this->itemCount = $customer->check()
            ? Cart::query()->where('user_id', $customer->id())->count()
            : 0;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.storefront.cart-button', ['itemCount' => $this->itemCount]);
    }
}
