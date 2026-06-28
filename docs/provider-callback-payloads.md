# Provider Callback Payloads

eMakato sends callbacks as JSON `POST` requests to the provider `callback_url`.

Callbacks use `callback_type` to identify the payload category:

- `MANDATE_STATUS`
- `COLLECTION_STATUS`

## Signature

When `callback_secret` is configured for the provider, every callback includes:

```http
Content-Type: application/json
Accept: application/json
X-Signature: sha256=<hex_hmac_sha256>
X-Signature-Algorithm: HMAC-SHA256
```

Verify the signature using the exact raw request body:

```php
$expected = 'sha256=' . hash_hmac('sha256', $rawRequestBody, $callbackSecret);

if (!hash_equals($expected, $requestSignature)) {
    // reject request
}
```

## Retries

If the provider endpoint is unavailable, rate limited, times out, or returns a server error, eMakato retries the callback job.

Retryable responses:

- HTTP `408`
- HTTP `429`
- HTTP `5xx`
- Connection or timeout errors

Non-retryable responses:

- HTTP `4xx` except `408` and `429`

Current retry schedule:

- 1 minute
- 5 minutes
- 15 minutes
- 30 minutes

The callback can be attempted up to 5 times within 24 hours.

## Mandate Status

Example event types:

- `MANDATE_ACTIVE`
- `MANDATE_FAILED`
- `MANDATE_CANCELLED`
- `MANDATE_CANCELLATION_REQUESTED`
- `MANDATE_SUBMITTED`
- `MANDATE_VERIFIED`

```json
{
  "event_id": "8f3c88f2-a8cf-4c01-8f7e-9085e5a1bb02",
  "callback_type": "MANDATE_STATUS",
  "event_type": "MANDATE_ACTIVE",
  "event_time": "2026-06-11T10:15:30+03:00",
  "corporate_reference": "CO0001",
  "channel": "NMB",
  "mandate": {
    "payment_channel": "NMB",
    "reference": "2801202624643",
    "periodicity": "MONTHLY",
    "debit_type": "FIXED",
    "installment_amount": 100000,
    "min_installment_amount": 100000,
    "max_installment_amount": 100000,
    "total_amount": 1200000,
    "paid_amount": 0,
    "outstanding_amount": 1200000,
    "number_of_installment": 12,
    "start_date": "2026-06-01",
    "end_date": "2027-05-01",
    "lifecycle_status": "active",
    "remarks": "Mandate approved"
  }
}
```

## Collection Status

Example event types:

- `COLLECTION_PAID`
- `COLLECTION_PARTIAL_PAID`
- `COLLECTION_FUNDSCHECK`
- `COLLECTION_FAILED`

```json
{
  "event_id": "dc69f0c9-5381-48af-a0cd-99bdc9dfdf55",
  "callback_type": "COLLECTION_STATUS",
  "event_type": "COLLECTION_FUNDSCHECK",
  "event_time": "2026-06-11T10:20:45+03:00",
  "corporate_reference": "CO0001",
  "channel": "NMB",
  "collection": {
    "mandate": {
      "reference": "2801202624643",
      "debit_type": "FIXED",
      "start_date": "2026-06-01",
      "end_date": "2027-05-01",
      "lifecycle_status": "active"
    },
    "installment_amount": 100000,
    "current_balance": 0,
    "outstanding_amount": 100000,
    "last_paid_amount": 0,
    "payment_date": "2026-06-06",
    "effective_payment_date": "2026-06-06",
    "reference": "COL-202606060001-897426",
    "status": "FUNDSCHECK",
    "remarks": "Insufficient funds"
  },
  "meta": {
    "settlement_reference": "COL-202606060001-897426",
    "external_reference": null,
    "bank_status": "FUNDSCHECK",
    "settlement_status": "FUNDSCHECK",
    "paid_amount": 0,
    "transaction_at": "2026-06-06T08:08:36+03:00"
  }
}
```
