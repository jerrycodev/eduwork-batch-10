# Order Fulfillment and Returns

## Order lifecycle

1. `pending`: waiting for payment.
2. `processing`: payment is verified and the store is preparing the order.
3. `shipped`: admin has dispatched the parcel and saved the courier tracking number.
4. `delivered`: admin confirms the parcel has been delivered. The customer can confirm receipt.
5. `completed`: customer confirmed receipt, or the system auto-completes the order three days after `delivered`.
6. `cancelled`: order was cancelled before dispatch.

Admins cannot set an order directly to `completed`. The supported admin transitions are pending → processing/cancelled, processing → shipped/cancelled, and shipped → delivered. Payment must be marked paid before processing, shipping, or delivery.

## Automatic completion

The `orders:auto-complete` Artisan command completes delivered orders whose `auto_complete_at` has passed. It skips orders with a return request in `requested` or `approved` state. The command is scheduled hourly in `routes/console.php`.

Production must run Laravel's scheduler. Configure the server cron entry:

```cron
* * * * * cd /path/to/ecommerce10 && php artisan schedule:run >> /dev/null 2>&1
```

## Returns

Customers can request a return within seven days of `delivered_at`, for delivered or completed orders. A request includes a reason, description, and up to five optional image files (5 MB each). One return request is allowed per order.

Admins can approve or reject a request. Rejection requires an explanation. After approval, the admin can mark the request completed only after the returned goods and refund have been handled. Refunds are **not** automatically sent through Midtrans; process them through the payment provider and then record completion in the admin order page.

Active return requests pause automatic order completion. Rejected requests close the return workflow and complete the order.

## Deployment

Run the new migrations during deployment:

```bash
php artisan migrate --force
php artisan storage:link
```

Ensure the public storage disk is writable and the Laravel scheduler cron is installed.
