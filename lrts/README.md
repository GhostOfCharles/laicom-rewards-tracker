# Laicom Rewards Tracker System (LRTS)

LRTS is a Laravel 12 application for Laicom customers to submit order receipts and track promotional rewards, and for staff to review receipts, manage inventory and premium reward stock, handle reward releases, and respond to support tickets.

## Main workflows

- Customers submit an order number, order date, purchased product lines, a required receipt image, and an optional note. Web and API submissions use `ReceiptSubmissionService`.
- Receipt order numbers are normalized. A number can be reused after a receipt is rejected or cancelled, but cannot have two active submissions.
- Staff review a submitted slip, select one qualifying promotion per bought-product group, and approve or reject the receipt. Approval reserves premium stock.
- Customers request a claim code for available rewards. Staff confirm physical handover from the Claims page by code or receipt. Voiding and expiry return reserved stock when the linked product is still usable.
- Order, reward, premium-stock, inventory, promotion, authentication, and support changes append audit records.

## Business rules and code locations

- `app/Services/RewardCalculator.php` is the shared promotion qualification and reward-quantity calculation.
- `app/Services/ReceiptSubmissionService.php` validates receipt submissions and stores receipt images on the private disk.
- `app/Services/ClaimService.php` handles customer claim requests, staff releases, voids, and expiry transitions.
- `app/Services/PremiumStockLedger.php` is the only premium-product stock mutation path and records each balance movement.
- `app/Services/ActivityLogger.php` appends immutable audit entries.
- Receipt slips and support attachments are served through owner/staff-authorized controllers; do not put these files on the public disk.
- Tunable limits such as the claim window, maximum order age, and slip upload size live in `config/lrts.php`.
- Schema changes belong in new migrations. Keep historical migrations unchanged.

## Admin and customer pages

- Customer screens: order submission, order tracker and details, active promotions, claim requests and history, notifications, and support tickets.
- Admin screens: premium products and promotions, receipts, claims, activity log, inventory, reports, and support tickets.
- Admin release actions are protected by the `release-rewards` gate.

## Scheduled expiry

`lrts:expire-rewards` expires overdue reward claim windows, handles rewards linked to expired perishable products, and writes off remaining expired premium stock. It is scheduled daily at 00:10. Production should run Laravel's scheduler once per minute so the scheduled command is invoked.

## Tests

Run the full application suite with:

```sh
php artisan test
```

Tests use the configured in-memory SQLite database and fake private storage where uploads are involved.
