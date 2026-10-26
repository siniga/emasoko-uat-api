# Local Equity Checkout

The Next.js frontend is at `C:\xampp\htdocs\projects\emakato\emakato-web`.
The Laravel portal keeps all Cybersource merchant credentials. This integration uses
the configured **production** account and is intentionally available only while
Laravel's `APP_ENV=local` and Next.js runs in development mode.

## Start

1. Keep Laravel running at `http://localhost:8000`.
2. Stop the existing HTTP frontend with Ctrl+C in its terminal.
3. From the web folder, run `npm run dev:https`.
4. Open `https://localhost:3000`, then select **Equity Checkout**.
   Next.js manages its local development certificate; follow its setup instructions
   if a trusted certificate is not already available.
5. Enter an amount and a currency enabled for the production merchant. Select
   **Prepare checkout**. This creates a capture context; it does not authorize funds.
6. Select **Open secure card form**, choose card entry and complete Cybersource's
   hosted form. Card details are collected inside its iframe, not in React inputs.
7. Review the amount and check the live authorization consent box. Only selecting
   **Authorize** sends the transient token to Laravel and requests a real card hold.

## Configuration

In the portal's `.env`:

```dotenv
APP_ENV=local
CYBERSOURCE_ENVIRONMENT=api.cybersource.com
CYBERSOURCE_CHECKOUT_ORIGIN=https://localhost:3000
```

`CYBERSOURCE_CHECKOUT_ORIGIN` defaults to the value above. It must match the browser's
protocol, hostname and port exactly, with no path or trailing slash. Run
`php artisan config:clear` after changing portal configuration.

The frontend server defaults to `PORTAL_API_URL=http://localhost:8000/api`.
If Laravel runs elsewhere, set that variable in `emakato-web/.env.local` and restart
Next.js. The supplied `.env.local.example` shows the expected format.

## API flow

- Browser → `POST /api/equity-checkout/context` → portal
  `POST /api/cybersource/checkout/context` with `amount` and `currency`.
- Laravel verifies the capture-context signature and saves the amount, currency,
  reference, expiry and transient-token public key under a generated `checkout_id`.
- The frontend loads the exact signed `clientLibrary` URL with its integrity hash,
  then uses the existing client version 0.23 Accept SDK and embedded payment screen.
- The hosted card form returns a transient token. It is kept only in page memory.
- Browser → `POST /api/equity-checkout/authorize` → portal
  `POST /api/cybersource/checkout/authorize` with `checkout_id` and `transient_token`.
- Laravel verifies the token's signature and expiry, uses the saved amount, and
  submits `tokenInformation.transientTokenJwt` with `capture: false`.
- A cache lock and persisted submission state prevent replaying the same checkout.
  Repeated requests return the stored result. An uncertain upstream outcome is not
  automatically retried. Checkout records and results expire after one day; sessions
  can only be initially authorized before their capture-context expiry.

The existing Postman `/api/cybersource/capture-context` and
`/api/cybersource/authorize` endpoints are unchanged by this browser integration.

## Results

- `AUTHORIZED`: a bank hold was approved; capture is not performed by this page.
- `AUTHORIZED_RISK_DECLINED`: the bank may have placed a hold, but Decision Manager
  rejected the order. Review in Business Center and reverse the authorization if
  it has not already been reversed.
- `AUTHORIZED_PENDING_REVIEW` or `PARTIAL_AUTHORIZED`: a hold may exist. Review the
  transaction before taking another payment action.
- `UNKNOWN`: check the displayed reference in Business Center. Do not assume the
  payment failed, and do not create another payment until its outcome is resolved.

This is a local integration test page, not a public purchase workflow. Before a
public release, use authenticated customer orders, prices calculated from the order,
durable payment records and idempotency, and a reconciled capture/reversal workflow.
Do not deploy these test routes by removing the local-environment guards.

## Verification

`php artisan test --filter=EquityCheckout` tests amount binding, invalid/expired tokens,
duplicate prevention, uncertain outcomes and local-only access without live payments.
The frontend is checked with TypeScript and `npm run build`.
