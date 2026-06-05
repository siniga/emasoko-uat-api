# External And Mobile API Map

This document describes the API areas that currently serve:

- mobile app clients
- external service providers that integrate from their own systems

The main route files are:

- [routes/api.php](/Users/macbook/html/solocodes/emakato/routes/api.php)
- [routes/api_external.php](/Users/macbook/html/solocodes/emakato/routes/api_external.php)
- [routes/api_mobile.php](/Users/macbook/html/solocodes/emakato/routes/api_mobile.php)

## 1. Authentication Model

Authentication is handled by:

- [app/Http/Controllers/Api/ExternalApp/AuthController.php](/Users/macbook/html/solocodes/emakato/app/Http/Controllers/Api/ExternalApp/AuthController.php)
- [app/Http/Controllers/Api/MobileApp/AuthController.php](/Users/macbook/html/solocodes/emakato/app/Http/Controllers/Api/MobileApp/AuthController.php)

Available endpoints:

- `POST /api/v1/token`
  Purpose: API token login using `partnerId` and password, mainly for provider/system integrations
- `POST /api/mobile/v1/login`
  Purpose: email/password login for mobile app users

Response behavior:

- returns `token`
- external token returns `corporate_reference`, `corporate_references`, and `partners` when the authenticated user is linked to one or more partners
- uses `auth:api` for all protected external and mobile routes

Important note:

- `token()` additionally requires `is_api_user = true`
- route access after login is mostly scoped by the authenticated user roles and linked partner records

## 2. API Audience Split

After auth, the API is logically split into 2 surfaces.

### A. External Provider API

These endpoints are intended for service providers that already have their own systems.

Controllers:

- [app/Http/Controllers/Api/ExternalApp/PaymentMandateController.php](/Users/macbook/html/solocodes/emakato/app/Http/Controllers/Api/ExternalApp/PaymentMandateController.php)
- [app/Http/Controllers/Api/ExternalApp/PaymentCollectionController.php](/Users/macbook/html/solocodes/emakato/app/Http/Controllers/Api/ExternalApp/PaymentCollectionController.php)

### B. Mobile App API

These endpoints are intended for eMakato mobile users.

Controllers:

- [app/Http/Controllers/Api/MobileApp/AuthController.php](/Users/macbook/html/solocodes/emakato/app/Http/Controllers/Api/MobileApp/AuthController.php)
- [app/Http/Controllers/Api/MobileApp/PaymentMandateController.php](/Users/macbook/html/solocodes/emakato/app/Http/Controllers/Api/MobileApp/PaymentMandateController.php)
- [app/Http/Controllers/Api/MobileApp/PaymentCollectionController.php](/Users/macbook/html/solocodes/emakato/app/Http/Controllers/Api/MobileApp/PaymentCollectionController.php)

## 3. Data Access And Scoping

Most protected endpoints do not hardcode partner IDs directly in the controller. They rely on model-level filters.

Main query gates:

- [app/Models/DebitOrder.php](/Users/macbook/html/solocodes/emakato/app/Models/DebitOrder.php)
- [app/Models/Installment.php](/Users/macbook/html/solocodes/emakato/app/Models/Installment.php)
- [app/Models/Partner.php](/Users/macbook/html/solocodes/emakato/app/Models/Partner.php)

Key mechanism:

- `filterCondition()`

This means the same endpoint may return different data depending on:

- authenticated user
- user role
- partner/company linkage
- authorization flags on linked partners

## 4. External Provider API Map

### 4.1 Mandates

Controller:

- [app/Http/Controllers/Api/ExternalApp/PaymentMandateController.php](/Users/macbook/html/solocodes/emakato/app/Http/Controllers/Api/ExternalApp/PaymentMandateController.php)

Endpoints:

- `POST /api/v1/mandate/summaries`
  Purpose: provider-facing mandate and collection summary dashboard

- `GET /api/v1/mandate/all`
  Purpose: list mandates visible to the authenticated provider user
  Optional filters:
  `start_at`, `end_at`, `payment_channel`, `corporate_reference`, `lifecycle_status`, `keyword`
  Notes:
  - `payment_channel` is a channel slug such as `NMB` or `CRDB`
  - use `corporate_reference` when the authenticated API user is linked to more than one partner
  - `lifecycle_status` filters by mandate lifecycle state

- `GET /api/v1/mandate/{reference}`
  Purpose: fetch a single mandate by mandate reference

- `GET /api/mandate/channels`
  Purpose: list supported mandate payment channels for external mandate creation
  Auth: Bearer token
  Response data:
  `payment_channels[]`
  Channel item fields:
  `id`, `name`, `slug`, `channel`, `account_type_id`
  Notes:
  - use the returned `slug` as the `payment_channel` value when creating a mandate
  - `channel` is response metadata only; do not send it to create mandate
  - `account_type_id = 1` means BANK and requires `account_name` and `account_number`
  - `account_type_id = 2` means MNO and uses customer `phone`

- `POST /api/v1/mandate/create`
  Purpose: create mandate from external system
  Key required fields:
  `payment_channel`, `phone`, `NIN`, `address`, `total_amount`, `debit_type`, `periodicity`, `deduction_start_date`
  Conditional fields:
  `corporate_reference`, `account_name`, `account_number`, `installment_amount`, `min_installment_amount`, `max_installment_amount`
  Partner selection:
  - `corporate_reference` is optional when the authenticated API user is linked to one authorized partner
  - `corporate_reference` is required when the authenticated API user is linked to more than one authorized partner
  - when provided, `corporate_reference` must be one of the references returned by `POST /api/v1/token`
  Payment channel values:
  - fetch live supported values from `GET /api/mandate/channels`
  - send `payment_channel` as the first mandate field, using the channel `slug` returned by that endpoint, for example `NMB`, `CRDB`, or `YAS`
  - the API derives BANK or MNO behavior from `payment_channel`
  - BANK payment channels require `account_name` and `account_number`
  - MNO payment channels use customer `phone`

- `POST /api/v1/mandate/otp/verify`
  Purpose: verify OTP for a created mandate

- `POST /api/v1/mandate/otp/create`
  Purpose: request/resend OTP for mandate confirmation

- `POST /api/v1/mandate/status`
  Purpose: fetch mandate status from bank / local mapping

- `POST /api/v1/mandate/cancellation`
  Purpose: process mandate cancellation with the payment channel and return the provider result

Behavior summary:

- provider is identified by `corporate_reference`
- the flow creates or updates:
  - customer
  - customer account
  - debit order
  - customer-provider link
- agreement evidence PDF is generated
- bank registration is delegated through `MandateActionService`

### 4.2 Collections

Controller:

- [app/Http/Controllers/Api/ExternalApp/PaymentCollectionController.php](/Users/macbook/html/solocodes/emakato/app/Http/Controllers/Api/ExternalApp/PaymentCollectionController.php)

Endpoints:

- `POST /api/v1/collections/all`
  Purpose: list expected collections for a given month/year
  Optional filters:
  `year`, `month`, `status`

- `POST /api/v1/collections/filtering`
  Purpose: filter collections by mandate reference
  Required:
  `reference`
  Optional:
  `status`

- `POST /api/v1/collections/actual`
  Purpose: aggregated actual collections by provider for a month/year
  Optional filters:
  `year`, `month`

## 5. Mobile App API Map

### 5.1 Home / Dashboard

Controller:

- [app/Http/Controllers/Api/ExternalApp/MobileAppController.php](/Users/renfridngolongolo/html/solocodes/eMakato_v1/app/Http/Controllers/Api/ExternalApp/MobileAppController.php)

Endpoints:

- `GET /api/v1/sp-homepage`
  Purpose: mobile homepage summaries
  Includes:
  - mandate counts by approval status
  - mandate values by approval status
  - this month expected vs actual collection
  - monthly mandate stats
  - recent mandates

- `GET /api/v1/debit-orders`
  Purpose: paginated mandate list for mobile app

- `GET /api/v1/debit-orders-summaries`
  Purpose: monthly mandate chart data by year

### 5.2 Mandate Registration And Actions

Controller:

- [app/Http/Controllers/Api/ExternalApp/MandateRegistrationController.php](/Users/renfridngolongolo/html/solocodes/eMakato_v1/app/Http/Controllers/Api/ExternalApp/MandateRegistrationController.php)

Endpoints:

- `POST /api/v1/filter-mandates`
  Purpose: filter/list mandates for the app

- `POST /api/v1/get-mandate-by-id`
  Purpose: fetch one mandate by `mandate_uuid`

- `POST /api/v1/create-mandate`
  Purpose: create mandate from mobile app

- `POST /api/v1/send-otp-code`
  Purpose: create/resend OTP

- `POST /api/v1/verify-otp`
  Purpose: verify OTP

- `POST /api/v1/archive-mandate`
  Purpose: archive mandate in app

- `POST /api/v1/cancel-mandate`
  Purpose: mark mandate as cancelled in app layer

- `POST /api/v1/delete-mandate`
  Purpose: soft delete mandate in app layer

Behavior summary:

- provider comes from authenticated user-to-partner linkage
- no `corporate_reference` is required from client
- the same customer/account/debit-order creation pattern is used as in the provider API

Important current gap:

- `cancel_mandate()` and `delete_mandate()` mostly mark records locally
- comments indicate bank-level cancellation/delete flow is still incomplete there

### 5.3 Collections And Histories

Controller:

- [app/Http/Controllers/Api/ExternalApp/CollectionController.php](/Users/renfridngolongolo/html/solocodes/eMakato_v1/app/Http/Controllers/Api/ExternalApp/CollectionController.php)

Endpoints:

- `GET /api/v1/collections`
  Purpose: expected collections for month/year

- `GET /api/v1/actual-collections`
  Purpose: actual aggregated collections by provider

- `GET /api/v1/monthly-collections`
  Purpose: monthly expected vs paid collection chart data

- `GET /api/v1/deduction-histories/{uuid}`
  Purpose: collection history for a specific mandate

## 6. Shared Functional Flow

The real business flow for both mobile and external-provider mandate creation is:

1. authenticate and get bearer token
2. create mandate draft/data
3. create customer if needed
4. create customer account if needed
5. create debit order
6. generate agreement PDF
7. submit to bank integration
8. create OTP if channel requires it
9. verify OTP
10. fetch status until active
11. later track collections

## 7. Existing Service Layer Worth Reusing

There is already a newer service-oriented structure that should become the shared core.

Services:

- [app/Services/PaymentMandateWorkflowService.php](/Users/renfridngolongolo/html/solocodes/eMakato_v1/app/Services/PaymentMandateWorkflowService.php)
- [app/Services/PaymentMandateQueryService.php](/Users/renfridngolongolo/html/solocodes/eMakato_v1/app/Services/PaymentMandateQueryService.php)
- [app/Services/MandateActionService.php](/Users/renfridngolongolo/html/solocodes/eMakato_v1/app/Services/MandateActionService.php)
- [app/Services/BankPayloadService.php](/Users/renfridngolongolo/html/solocodes/eMakato_v1/app/Services/BankPayloadService.php)
- [app/Services/BankApiService.php](/Users/renfridngolongolo/html/solocodes/eMakato_v1/app/Services/BankApiService.php)

What this means:

- the project is already moving toward shared workflow services
- current controllers still duplicate too much logic
- the best next refactor is to make both mobile and provider controllers call the same workflow/query services

## 8. Current Design Issues

### 8.1 Duplicated Mandate Logic

The following controllers repeat similar logic:

- [app/Http/Controllers/Api/ExternalApp/PaymentMandateController.php](/Users/renfridngolongolo/html/solocodes/eMakato_v1/app/Http/Controllers/Api/ExternalApp/PaymentMandateController.php)
- [app/Http/Controllers/Api/ExternalApp/MandateRegistrationController.php](/Users/renfridngolongolo/html/solocodes/eMakato_v1/app/Http/Controllers/Api/ExternalApp/MandateRegistrationController.php)

Repeated areas:

- request validation
- customer creation/update
- customer account creation/update
- debit order creation
- OTP create/verify
- mandate status checks

### 8.2 Route Naming Inconsistency

Examples:

- `GET /mandate/all`
- `POST /filter-mandates`
- `POST /get-mandate-by-id`
- `POST /collections/filtering`

The API mixes:

- REST-like naming
- action naming
- list/filter endpoints that could be unified

### 8.3 Response Consistency

Responses generally contain:

- `success`
- `response_code`
- message payload

But:

- HTTP status codes are not always aligned with `response_code`
- some validation errors return 422 body with 403/400-style payload codes

### 8.4 Local Action vs Bank Action

Some mobile actions currently update local records only:

- archive
- cancel
- delete

That means consumer apps need clearer documentation on whether an action:

- changed local state only
- requested bank action
- completed bank action

## 9. Recommended Target Structure

Best long-term structure:

### 9.1 Keep Separate API Audiences

Keep these route groups conceptually separate:

- Mobile API
- External Provider API

This is good because client needs differ.

### 9.2 Share Business Services Underneath

Both APIs should use the same service layer for:

- mandate workflow
- OTP workflow
- query/filtering
- summaries
- collection analytics

### 9.3 Normalize Route Style

Suggested future direction:

- `GET /v1/mandates`
- `GET /v1/mandates/{reference}`
- `POST /v1/mandates`
- `POST /v1/mandates/{reference}/otp`
- `POST /v1/mandates/{reference}/otp/verify`
- `GET /v1/mandates/{reference}/status`
- `POST /v1/mandates/{reference}/cancel`
- `GET /v1/collections`
- `GET /v1/collections/actual`

Mobile-only routes can still exist, but shared resources should look similar.

## 10. Recommended Refactor Order

1. Move both mandate creation flows to `PaymentMandateWorkflowService`
2. Move both mandate list/filter endpoints to `PaymentMandateQueryService`
3. Introduce a shared collection query service for:
   - expected collections
   - actual collections
   - monthly chart data
   - deduction history
4. Standardize response shape across mobile and provider endpoints
5. Clarify which actions are:
   - local only
   - async bank request
   - fully completed bank confirmation

## 11. Practical Conclusion

The current API is already usable for:

- mobile application integration
- external service provider integration

The strongest part of the design is the role/partner scoping model.

The main weakness is controller duplication, especially in mandate registration and OTP handling.

If this area is going to support both mobile and third-party providers long-term, the best next move is not adding more controllers. It is consolidating the business logic into shared workflow/query services and letting each client surface format the response it needs.
