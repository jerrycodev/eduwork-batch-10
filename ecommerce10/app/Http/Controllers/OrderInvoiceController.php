<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class OrderInvoiceController extends Controller
{
    public function __invoke(Order $order): Response
    {
        abort_unless((string) $order->user_id === (string) auth()->id() || auth()->user()?->role === 'admin', 403);

        $order->load('orderItems');

        return Pdf::loadView('invoices.order', [
            'appName' => config('app.name'),
            'order' => $order,
            'isPaid' => $order->payment_status === 'paid',
        ])->setPaper('a4')->download(($order->payment_status === 'paid' ? 'bukti-pembayaran-' : 'invoice-').$order->order_number.'.pdf');
    }
}
