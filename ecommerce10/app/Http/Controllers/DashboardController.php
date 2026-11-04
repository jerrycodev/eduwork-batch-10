<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $totalProducts = Product::count();
        $totalCategories = ProductCategory::count();
        $totalOrders = Order::count();
        $totalProductClicks = (int) Product::sum('click');

        $metrics = [
            ['label' => 'Total Products', 'value' => $totalProducts, 'icon' => 'package'],
            ['label' => 'Total Product Clicks', 'value' => $totalProductClicks, 'icon' => 'mouse-pointer-click'],
            ['label' => 'Total Categories', 'value' => $totalCategories, 'icon' => 'tag'],
            ['label' => 'Total Orders', 'value' => $totalOrders, 'icon' => 'shopping-bag'],
        ];

        $monthExpression = DB::connection()->getDriverName() === 'sqlite'
            ? "CAST(strftime('%m', created_at) AS INTEGER)"
            : 'MONTH(created_at)';

        $monthlyOrders = Order::query()
            ->whereYear('created_at', now()->year)
            ->selectRaw($monthExpression . ' as month, COUNT(*) as total')
            ->groupBy('month')
            ->pluck('total', 'month');

        $ordersChart = [
            'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            'orders' => collect(range(1, 12))
                ->map(fn (int $month) => (int) ($monthlyOrders[$month] ?? 0))
                ->all(),
        ];

        $knownStatuses = ['pending', 'processing', 'success', 'failed', 'refund', 'cancelled'];
        $statusCounts = Order::query()
            ->selectRaw('LOWER(TRIM(status)) as normalized_status, COUNT(*) as total')
            ->groupBy('normalized_status')
            ->pluck('total', 'normalized_status');

        $order_statuses = collect($knownStatuses)
            ->merge($statusCounts->keys())
            ->unique()
            ->map(function (string $status) use ($statusCounts) {
                $colors = [
                    'pending' => '#f59e0b',
                    'processing' => '#3b82f6',
                    'success' => '#22c55e',
                    'completed' => '#22c55e',
                    'failed' => '#ef4444',
                    'refund' => '#8b5cf6',
                    'refunded' => '#8b5cf6',
                    'cancelled' => '#64748b',
                ];

                return [
                    'name' => ucwords(str_replace(['_', '-'], ' ', $status)),
                    'value' => (int) ($statusCounts[$status] ?? 0),
                    'color' => $colors[$status] ?? '#0ea5e9',
                ];
            })
            ->values()
            ->all();

        $products_clicks = Product::query()
            ->orderByDesc('click')
            ->orderByDesc('id')
            ->limit(5)
            ->get()
            ->map(fn (Product $product) => [
                'image' => $product->image,
                'label' => $product->name,
                'url' => route('product-detail', $product),
                'clicks' => (int) $product->click,
            ])
            ->all();

        $chart_data = [
            'orders' => $ordersChart,
            'order_statuses' => $order_statuses,
        ];

        return view('dashboard', compact(
            'metrics',
            'products_clicks',
            'order_statuses',
            'chart_data',
        ));
    }
}
