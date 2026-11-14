# Biteship Shipping Setup

Checkout uses Biteship's live courier-rate API with one warehouse origin and the customer's saved destination address.

## Environment

Set these values in `.env`:

```dotenv
BITESHIP_BASE_URL=https://api.biteship.com
BITESHIP_API_KEY=biteship_test.your_test_key
BITESHIP_COURIERS=jne,sicepat,jnt,anteraja,pos
BITESHIP_TIMEOUT=15
BITESHIP_WAREHOUSE_POSTAL_CODE=16431
```

- Use a `biteship_test.` key for development and QA. Replace it with your `biteship_live.` key in production.
- Set `BITESHIP_WAREHOUSE_POSTAL_CODE` to the actual five-digit postal code of the warehouse. The example `16431` is illustrative only; do not use it unless it is your warehouse postal code.
- Confirm courier codes and courier activation in the Biteship dashboard. The configured list can be adjusted without code changes.
- Never commit `.env` or expose the API key.

After changing environment values, refresh Laravel's configuration cache:

```bash
php artisan config:clear
# Production deployments may then run:
php artisan config:cache
```

## Product weights

Every product now has `weight_gram` (grams per item). Existing products receive a default of 1,000 grams. Review and update each product's actual weight in the admin product form before enabling live checkout rates. Biteship receives each line's unit weight and quantity.

## Checkout flow

1. Customer selects a saved address with a valid five-digit Indonesian postal code.
2. The application requests rates from `POST /v1/rates/couriers` using warehouse and destination postal codes, courier codes, product values, quantities, and weights.
3. Customer selects a courier service. The server stores the quote in the session for ten minutes.
4. On checkout, the server validates the quote and requests rates again. Client-submitted prices are never used.
5. The order stores product subtotal and a snapshot of courier, service, shipping cost, and ETA. Midtrans receives product lines plus a shipping line, with `gross_amount` equal to subtotal plus shipping.

If Biteship is unavailable or no courier serves the route, checkout is blocked rather than silently charging zero shipping.

## Biteship references

- Rates endpoint: https://biteship.com/en/docs/api/rates/overview
- Get rates request and response: https://biteship.com/en/docs/api/reference/get-rates
- Authentication: https://biteship.com/en/docs/api/authentication
- Plans and API quotas: https://biteship.com/en/docs/plans
