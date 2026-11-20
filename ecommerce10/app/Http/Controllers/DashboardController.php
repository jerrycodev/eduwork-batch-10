<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class DashboardController extends Controller
{
    /**
     * Menampilkan daftar data dengan filter dan relasi yang diperlukan untuk kebutuhan operasional.
     */
    public function index(Request $request)
    {
        $period = in_array($request->query('period'), ['day', 'month', 'year'], true)
            ? $request->query('period')
            : 'year';
        $dateFormat = ['day' => 'Y-m-d', 'month' => 'Y-m', 'year' => 'Y'][$period];
        $validated = $request->validate([
            'period' => ['nullable', Rule::in(['day', 'month', 'year'])],
            'date' => ['nullable', 'date_format:'.$dateFormat],
        ]);

        $selectedDate = Carbon::createFromFormat($dateFormat, $validated['date'] ?? now()->format($dateFormat));
        $selectedDate = match ($period) {
            'day' => $selectedDate->startOfDay(),
            'month' => $selectedDate->startOfMonth(),
            default => $selectedDate->startOfYear(),
        };
        $rangeStart = $selectedDate->copy();
        $rangeEnd = match ($period) {
            'day' => $selectedDate->copy()->endOfDay(),
            'month' => $selectedDate->copy()->endOfMonth(),
            default => $selectedDate->copy()->endOfYear(),
        };

        $inRange = fn ($query) => $query->whereBetween('created_at', [$rangeStart, $rangeEnd]);

        $totalProducts = $inRange(Product::query())->count();
        $totalCategories = $inRange(ProductCategory::query())->count();
        $totalOrders = $inRange(Order::query())->count();
        // Product clicks are stored as a lifetime counter, not as timestamped events.
        $totalProductClicks = (int) Product::sum('click');

        $metrics = [
            ['label' => 'Products Created', 'value' => $totalProducts, 'icon' => 'package'],
            ['label' => 'Product Clicks (All Time)', 'value' => $totalProductClicks, 'icon' => 'mouse-pointer-click'],
            ['label' => 'Categories Created', 'value' => $totalCategories, 'icon' => 'tag'],
            ['label' => 'Orders', 'value' => $totalOrders, 'icon' => 'shopping-bag'],
        ];

        if ($period === 'day') {
            $labels = collect(range(0, 23))->map(fn (int $hour) => str_pad((string) $hour, 2, '0', STR_PAD_LEFT).':00');
            $groupExpression = DB::connection()->getDriverName() === 'sqlite'
                ? "CAST(strftime('%H', created_at) AS INTEGER)"
                : 'HOUR(created_at)';
            $groupCount = 24;
        } elseif ($period === 'month') {
            $labels = collect(range(1, $selectedDate->daysInMonth))->map(fn (int $day) => (string) $day);
            $groupExpression = DB::connection()->getDriverName() === 'sqlite'
                ? "CAST(strftime('%d', created_at) AS INTEGER)"
                : 'DAY(created_at)';
            $groupCount = $selectedDate->daysInMonth;
        } else {
            $labels = collect(['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']);
            $groupExpression = DB::connection()->getDriverName() === 'sqlite'
                ? "CAST(strftime('%m', created_at) AS INTEGER)"
                : 'MONTH(created_at)';
            $groupCount = 12;
        }

        $groupedOrders = $inRange(Order::query())
            ->selectRaw($groupExpression.' as period, COUNT(*) as total')
            ->groupBy('period')
            ->pluck('total', 'period');

        $ordersChart = [
            'labels' => $labels->all(),
            'orders' => collect(range(1, $groupCount))
                ->map(fn (int $value) => (int) ($groupedOrders[$value] ?? 0))
                ->all(),
        ];

        $knownStatuses = ['pending', 'processing', 'success', 'failed', 'refund', 'cancelled'];
        $statusCounts = $inRange(Order::query())
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

        $latest_orders = $inRange(Order::query())
            ->withCount('orderItems')
            ->with('cancellationRequest')
            ->latest()
            ->limit(5)
            ->get();

        $chart_data = [
            'orders' => $ordersChart,
            'order_statuses' => $order_statuses,
        ];

        $filter = [
            'period' => $period,
            'date' => match ($period) {
                'day' => $selectedDate->format('Y-m-d'),
                'month' => $selectedDate->format('Y-m'),
                default => $selectedDate->format('Y'),
            },
            'label' => match ($period) {
                'day' => $selectedDate->translatedFormat('d F Y'),
                'month' => $selectedDate->translatedFormat('F Y'),
                default => $selectedDate->format('Y'),
            },
        ];

        return view('admin.dashboard', compact(
            'metrics',
            'latest_orders',
            'order_statuses',
            'chart_data',
            'filter',
        ));
    }
}
