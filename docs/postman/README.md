# eMakato Mobile API Postman Collection

Files:

- `emakato-mobile-api.postman_collection.json`
- `emakato-mobile-api.postman_environment.json`

## How to Test

1. Import both files into Postman.
2. Select the `eMakato Mobile API - Local/UAT` environment.
3. Update these environment variables before running:
   - `base_url`
   - `email`
   - `password`
   - `payment_channel`
   - `corporate_reference` if the test user is linked to more than one service provider
   - `deduction_start_date`
4. Run `Auth / Login` first. It saves `access_token` automatically.
5. Run dashboard, mandate list, profile, and collection requests.
6. For action endpoints, set or capture:
   - `mandate_reference`
   - `mandate_uuid`
   - `notification_id`

## Notes

- Authenticated requests use `Authorization: Bearer {{access_token}}`.
- Collection expected values use `payment_date`.
- Collection paid values use `effective_payment_date` with statuses `CLOSED` and `PARTIAL_PAID`.
- `COLLECTED` is included as a testing alias where the API supports it; internally it maps to paid statuses.
- `mandates/summaries` is defined as `ANY` in Laravel. The collection uses `POST` for easier request body testing.
