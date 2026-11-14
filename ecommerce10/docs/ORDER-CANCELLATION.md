# Customer Order Cancellation

## Rules

- An unpaid order in `pending` or `processing` can be cancelled directly by its owner.
- A paid order in `pending` or `processing` can only be cancelled through a customer request reviewed by an admin.
- Shipped, delivered, and completed orders cannot be cancelled; use the return workflow after delivery.
- A requested or approved cancellation blocks fulfillment transitions until the request is rejected or completed.

## Paid cancellation workflow

1. Customer submits a reason and optional description.
2. The request is stored as `requested`; the order remains in its current fulfillment status but is held from shipping.
3. Admin changes it to `approved` or `rejected`.
4. If approved, admin processes the refund manually through the payment provider.
5. After refund succeeds, admin marks the request `completed` and records the refund reference in the admin note. The order becomes `cancelled`, payment status becomes `refunded`, and stock is restored.
6. If rejected, the order remains active and can continue fulfillment.

Unpaid direct cancellation immediately marks the order `cancelled` and restores stock. Stock restoration is idempotent through `orders.stock_restored_at`.

## Late payment notification

If Midtrans settlement arrives after an unpaid order was cancelled, the order remains cancelled and the application creates an approved cancellation request for admin refund handling. This avoids silently reopening a cancelled order after a delayed payment callback.

## Deployment

Run `php artisan migrate --force` to create the cancellation request table and add cancellation/stock-restoration metadata to orders.

Refund processing is currently manual. Never mark a paid cancellation completed until the refund has actually been processed; record the provider reference in the admin note.
