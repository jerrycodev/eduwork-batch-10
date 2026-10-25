<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductCategory;

class DashboardController extends Controller
{
    public function index()
    {
        $total_products = Product::count();
        $total_categories = ProductCategory::count();
        $total_orders = Order::count();
        $total_customers = 658;
        $metrics = [
            ['label' => 'Total Products', 'value' => $total_products, 'icon' => 'package', 'description' => '+'.$total_products.'% from last month'],
            ['label' => 'Total Categories', 'value' => $total_categories, 'icon' => 'tag', 'description' => '+'.$total_categories.'% from last month'],
            ['label' => 'Total Orders', 'value' => $total_orders, 'icon' => 'circle-dollar-sign', 'description' => '0% from last month'],
            ['label' => 'Total Customers', 'value' => $total_customers, 'icon' => 'users', 'description' => '+280% from last month'],
        ];
        $operating_systems = [['name' => 'Android', 'value' => 28, 'color' => '#ef4444'], ['name' => 'IOS', 'value' => 23, 'color' => '#f59e0b'], ['name' => 'Windows', 'value' => 19, 'color' => '#eab308'], ['name' => 'Linux', 'value' => 14, 'color' => '#22c55e'], ['name' => 'Mac', 'value' => 9, 'color' => '#3b82f6'], ['name' => 'Others', 'value' => 5, 'color' => '#8b5cf6']];

        $orders_chart = [
            'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            'income' => [380000, 420000, 350000, 500000, 450000, 580000, 523000, 610000, 570000, 660000, 700000, 750000],
            'expense' => [320000, 350000, 300000, 420000, 370000, 450000, 410000, 430000, 390000, 470000, 440000, 520000],
        ];

        $products = Product::limit(5)->orderBy('id', 'desc')->get();
        $products_clicks = [];
        foreach ($products as $product) {
            $lastMonthClicks = 180 + (($product->id * 47) % 820);
            $thisMonthClicks = 150 + (($product->id * 71) % 900);
            $previousClicks = 1200 + (($product->id * 137) % 2800);

            $products_clicks[] = [
                'image' => $product->image,
                'label' => $product->name,
                'url' => route('product-detail', $product),
                'last_month_clicks' => $lastMonthClicks,
                'this_month_clicks' => $thisMonthClicks,
                'total_clicks' => $previousClicks + $lastMonthClicks + $thisMonthClicks,
                'trend' => (($thisMonthClicks - $lastMonthClicks) / max($lastMonthClicks, 1)) * 100,
            ];
        }
        $chart_data = [
            'orders' => $orders_chart,
            'operating_systems' => $operating_systems,
        ];

        return view('dashboard', compact(
            'total_products',
            'total_categories',
            'total_orders',
            'total_customers',
            'metrics',
            'operating_systems',
            'chart_data',
            'products_clicks',
        ));
    }
}
