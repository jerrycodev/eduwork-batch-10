<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
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
        $period = in_array($request->query('period'), ['day', '7days', '30days', 'month', 'year'], true)
            ? $request->query('period')
            : 'year';
        $dateFormat = in_array($period, ['day'], true) ? 'Y-m-d' : (in_array($period, ['month'], true) ? 'Y-m' : 'Y');
        $validated = $request->validate([
            'period' => ['nullable', Rule::in(['day', '7days', '30days', 'month', 'year'])],
            'date' => ['nullable', 'date_format:'.$dateFormat],
        ]);

        $selectedDate = Carbon::createFromFormat($dateFormat, $validated['date'] ?? now()->format($dateFormat));
        [$rangeStart, $rangeEnd] = match ($period) {
            'day' => [$selectedDate->copy()->startOfDay(), $selectedDate->copy()->endOfDay()],
            '7days' => [now()->subDays(6)->startOfDay(), now()->endOfDay()],
            '30days' => [now()->subDays(29)->startOfDay(), now()->endOfDay()],
            'month' => [$selectedDate->copy()->startOfMonth(), $selectedDate->copy()->endOfMonth()],
            default => [$selectedDate->copy()->startOfYear(), $selectedDate->copy()->endOfYear()],
        };
        $rangeLength = $rangeStart->diffInSeconds($rangeEnd) + 1;
        $previousEnd = $rangeStart->copy()->subSecond();
        $previousStart = $previousEnd->copy()->subSeconds($rangeLength - 1);
        $inRange = fn ($query) => $query->whereBetween('created_at', [$rangeStart, $rangeEnd]);
        $paidInRange = fn ($query) => $query->where('payment_status', 'paid')
            ->whereBetween(DB::raw('COALESCE(paid_at, created_at)'), [$rangeStart, $rangeEnd]);
        $paidPrevious = fn ($query) => $query->where('payment_status', 'paid')
            ->whereBetween(DB::raw('COALESCE(paid_at, created_at)'), [$previousStart, $previousEnd]);
        $percentChange = function (float|int $current, float|int $previous): ?float {
            if ((float) $previous === 0.0) {
                return $current > 0 ? 100.0 : null;
            }
            return round((($current - $previous) / $previous) * 100, 1);
        };

        // Penjualan hanya menghitung pesanan dengan pembayaran yang sudah berhasil.
        $sales = (int) $paidInRange(Order::query())->sum('total');
        $previousSales = (int) $paidPrevious(Order::query())->sum('total');
        $paidOrders = $paidInRange(Order::query())->count();
        $previousPaidOrders = $paidPrevious(Order::query())->count();
        $totalOrders = $inRange(Order::query())->count();

        $newCustomers = User::query()->where('role', 'customer')->whereBetween('created_at', [$rangeStart, $rangeEnd])->count();
        $previousCustomers = User::query()->where('role', 'customer')->whereBetween('created_at', [$previousStart, $previousEnd])->count();
        $totalCustomers = User::query()->where('role', 'customer')->count();
        $totalAdmins = User::query()->where('role', 'admin')->count();
        $activeProducts = Product::query()->where('is_active', true)->count();
        $totalProducts = Product::query()->count();
        $totalCategories = ProductCategory::query()->count();
        $pendingPayments = $inRange(Order::query())->whereIn('payment_status', ['unpaid', 'pending', 'challenge'])->count();
        $inTransit = $inRange(Order::query())->where('status', 'shipped')->count();
        $totalProductClicks = (int) Product::sum('click');

        $metrics = [
            ['label' => 'Total Penjualan', 'value' => $sales, 'format' => 'currency', 'icon' => 'wallet', 'change' => $percentChange($sales, $previousSales), 'comparison' => 'vs periode sebelumnya'],
            ['label' => 'Pesanan Dibayar', 'value' => $paidOrders, 'format' => 'number', 'icon' => 'shopping-bag', 'change' => $percentChange($paidOrders, $previousPaidOrders), 'comparison' => 'vs periode sebelumnya'],
            ['label' => 'Total Customer', 'value' => $totalCustomers, 'format' => 'number', 'icon' => 'users', 'detail' => number_format($newCustomers, 0, ',', '.').' customer baru pada periode ini', 'change' => $percentChange($newCustomers, $previousCustomers), 'comparison' => 'customer baru vs periode lalu'],
            ['label' => 'Produk Aktif', 'value' => $activeProducts, 'format' => 'number', 'icon' => 'package', 'detail' => number_format($totalProducts, 0, ',', '.').' total produk'],
            ['label' => 'Menunggu Pembayaran', 'value' => $pendingPayments, 'format' => 'number', 'icon' => 'clock'],
            ['label' => 'Dalam Pengiriman', 'value' => $inTransit, 'format' => 'number', 'icon' => 'truck'],
        ];

        if ($period === 'day') {
            $labels = collect(range(0, 23))->map(fn (int $hour) => str_pad((string) $hour, 2, '0', STR_PAD_LEFT).':00');
            $groupExpression = DB::connection()->getDriverName() === 'sqlite'
                ? "CAST(strftime('%H', COALESCE(paid_at, created_at)) AS INTEGER)"
                : 'HOUR(COALESCE(paid_at, created_at))';
            $groupCount = 24;
            $salesLabel = 'Penjualan per jam';
        } elseif ($period === '7days' || $period === '30days') {
            $days = $period === '7days' ? 7 : 30;
            $labels = collect(range(0, $days - 1))->map(fn (int $offset) => $rangeStart->copy()->addDays($offset)->format('d M'));
            $groupExpression = DB::connection()->getDriverName() === 'sqlite'
                ? "DATE(COALESCE(paid_at, created_at))"
                : 'DATE(COALESCE(paid_at, created_at))';
            $groupCount = $days;
            $salesLabel = 'Penjualan harian';
        } elseif ($period === 'month') {
            $labels = collect(range(1, $selectedDate->daysInMonth))->map(fn (int $day) => (string) $day);
            $groupExpression = DB::connection()->getDriverName() === 'sqlite'
                ? "CAST(strftime('%d', COALESCE(paid_at, created_at)) AS INTEGER)"
                : 'DAY(COALESCE(paid_at, created_at))';
            $groupCount = $selectedDate->daysInMonth;
            $salesLabel = 'Penjualan harian';
        } else {
            $labels = collect(['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des']);
            $groupExpression = DB::connection()->getDriverName() === 'sqlite'
                ? "CAST(strftime('%m', COALESCE(paid_at, created_at)) AS INTEGER)"
                : 'MONTH(COALESCE(paid_at, created_at))';
            $groupCount = 12;
            $salesLabel = 'Penjualan bulanan';
        }

        $groupedSales = $paidInRange(Order::query())
            ->selectRaw($groupExpression.' as period, SUM(total) as total')
            ->groupBy('period')
            ->pluck('total', 'period');
        $salesValues = collect(range(0, $groupCount - 1))->map(function (int $index) use ($period, $groupedSales, $rangeStart): int {
            $key = match ($period) {
                'day', 'month', 'year' => $index + 1,
                default => $rangeStart->copy()->addDays($index)->toDateString(),
            };
            return (int) ($groupedSales[$key] ?? 0);
        })->all();

        $groupedOrders = $inRange(Order::query())
            ->selectRaw(match ($period) {
                'day' => DB::connection()->getDriverName() === 'sqlite' ? "CAST(strftime('%H', created_at) AS INTEGER)" : 'HOUR(created_at)',
                '7days', '30days' => 'DATE(created_at)',
                'month' => DB::connection()->getDriverName() === 'sqlite' ? "CAST(strftime('%d', created_at) AS INTEGER)" : 'DAY(created_at)',
                default => DB::connection()->getDriverName() === 'sqlite' ? "CAST(strftime('%m', created_at) AS INTEGER)" : 'MONTH(created_at)',
            }.' as period, COUNT(*) as total')
            ->groupBy('period')->pluck('total', 'period');
        $ordersValues = collect(range(0, $groupCount - 1))->map(function (int $index) use ($period, $groupedOrders, $rangeStart): int {
            $key = match ($period) {
                'day', 'month', 'year' => $index + 1,
                default => $rangeStart->copy()->addDays($index)->toDateString(),
            };
            return (int) ($groupedOrders[$key] ?? 0);
        })->all();

        $knownStatuses = ['pending', 'processing', 'shipped', 'delivered', 'completed', 'cancelled'];
        $statusCounts = $inRange(Order::query())
            ->selectRaw('LOWER(TRIM(status)) as normalized_status, COUNT(*) as total')
            ->groupBy('normalized_status')->pluck('total', 'normalized_status');
        $order_statuses = collect($knownStatuses)->merge($statusCounts->keys())->unique()
            ->map(function (string $status) use ($statusCounts) {
                $colors = ['pending' => '#f59e0b', 'processing' => '#3b82f6', 'shipped' => '#0ea5e9', 'delivered' => '#06b6d4', 'success' => '#22c55e', 'completed' => '#22c55e', 'failed' => '#ef4444', 'refund' => '#8b5cf6', 'refunded' => '#8b5cf6', 'cancelled' => '#64748b'];
                return ['name' => ucwords(str_replace(['_', '-'], ' ', $status)), 'value' => (int) ($statusCounts[$status] ?? 0), 'color' => $colors[$status] ?? '#0ea5e9'];
            })->values()->all();

        $topProducts = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.payment_status', 'paid')
            ->whereBetween(DB::raw('COALESCE(orders.paid_at, orders.created_at)'), [$rangeStart, $rangeEnd])
            ->select('order_items.product_id', 'order_items.product_name')
            ->selectRaw('SUM(order_items.quantity) as quantity_sold, SUM(order_items.total) as revenue')
            ->groupBy('order_items.product_id', 'order_items.product_name')
            ->orderByDesc('quantity_sold')->limit(5)->get();

        $lowStockProducts = Product::query()->where('is_active', true)->whereBetween('stock', [1, 5])->orderBy('stock')->limit(6)->get(['id', 'name', 'stock', 'sku']);
        $outOfStockProducts = Product::query()->where('is_active', true)->where('stock', '<=', 0)->count();
        $lowStockCount = Product::query()->where('is_active', true)->whereBetween('stock', [1, 5])->count();
        $pendingCancellations = DB::table('order_cancellation_requests')->whereIn('status', ['requested', 'approved'])->count();
        $pendingReturns = DB::table('order_return_requests')->whereIn('status', ['requested', 'approved', 'revision_requested'])->count();
        $overdueDeliveries = Order::query()->where('status', 'shipped')->whereNotNull('estimated_delivery_at')->where('estimated_delivery_at', '<', now())->count();
        $problemShipments = Order::query()->where('payment_status', 'paid')->whereIn('biteship_status', ['cancelled', 'rejected', 'courier_not_found'])->whereNotIn('status', ['cancelled', 'completed'])->count();
        $pendingReviews = DB::table('product_reviews')->where('is_approved', false)->count();

        $latest_orders = $inRange(Order::query())->with('cancellationRequest')->latest()->limit(5)->get();
        $filter = [
            'period' => $period,
            'date' => match ($period) {
                'day' => $rangeStart->format('Y-m-d'),
                'month' => $rangeStart->format('Y-m'),
                default => $rangeStart->format('Y'),
            },
            'label' => match ($period) {
                'day' => $rangeStart->translatedFormat('d F Y'),
                '7days' => $rangeStart->format('d M').' – '.$rangeEnd->format('d M Y'),
                '30days' => $rangeStart->format('d M').' – '.$rangeEnd->format('d M Y'),
                'month' => $rangeStart->translatedFormat('F Y'),
                default => $rangeStart->format('Y'),
            },
            'sales_label' => $salesLabel,
        ];
        $chart_data = [
            'sales' => ['labels' => $labels->all(), 'values' => $salesValues, 'orders' => $ordersValues],
            'order_statuses' => $order_statuses,
        ];
        $secondaryMetrics = [
            ['label' => 'Total Pesanan', 'value' => $totalOrders],
            ['label' => 'Customer Baru', 'value' => $newCustomers],
            ['label' => 'Admin', 'value' => $totalAdmins],
            ['label' => 'Kategori', 'value' => $totalCategories],
            ['label' => 'Klik Produk', 'value' => $totalProductClicks],
        ];
        $operations = [
            ['label' => 'Pengajuan Pembatalan', 'value' => $pendingCancellations, 'description' => 'Menunggu pemeriksaan', 'route' => 'orders.index', 'icon' => 'x-circle'],
            ['label' => 'Pengajuan Retur', 'value' => $pendingReturns, 'description' => 'Menunggu pemeriksaan', 'route' => 'orders.index', 'icon' => 'package-check'],
            ['label' => 'Pengiriman Terlambat', 'value' => $overdueDeliveries, 'description' => 'Melewati estimasi tiba', 'route' => 'orders.index', 'icon' => 'truck'],
            ['label' => 'Shipment Bermasalah', 'value' => $problemShipments, 'description' => 'Perlu tindak lanjut', 'route' => 'orders.index', 'icon' => 'alert-triangle'],
            ['label' => 'Review Menunggu', 'value' => $pendingReviews, 'description' => 'Belum disetujui', 'route' => 'reviews.index', 'icon' => 'star'],
        ];

        return view('admin.dashboard', compact('metrics', 'secondaryMetrics', 'latest_orders', 'order_statuses', 'chart_data', 'filter', 'topProducts', 'lowStockProducts', 'lowStockCount', 'outOfStockProducts', 'operations'));
    }
}
