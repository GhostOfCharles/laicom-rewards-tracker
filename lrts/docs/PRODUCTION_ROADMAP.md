# RTS Production Hardening: Implementation Brief (one shot, phased)

You are a senior Laravel engineer working in the Rewards Tracker System repo (Laravel 12, PHP 8.2, Blade, Bootstrap, vanilla JS, MySQL; tests use in memory SQLite). Read README.md and AGENTS.md first. Follow AGENTS.md: smallest safe changes, preserve existing behavior, authorization, validation and database integrity, keep private uploads private, no new dependencies unless stated.

## Operating rules

1. Work through the phases in order. Each phase must end with `php artisan test` green and a working app. Commit after each phase with a clear message. Do not start a phase while the previous one is red.
2. Re-verify every finding against the actual code before changing it. Line numbers below are approximate. If a finding no longer holds, skip it and note that in the final report.
3. Never print, log or commit secrets. Do not read values from `.env` into output.
4. Branding rule: public interfaces and deliverables may use only "Rewards Tracker System" / "RTS" branding. No client company name, logo or photographs, and no supplier names in UI text, code identifiers, filenames, README or seed data.
5. Preserve existing business rules: order number normalization and duplicate prevention, reward calculation, stock reserved at approval, release then customer confirmation, claim window of 30 days, expiry handling, activity log immutability.
6. Keep the mobile API contract (`/api/login`, `/api/receipts`, `/api/user`) backward compatible. The Flutter client sends `product_name` strings; keep accepting them.
7. Do not rewrite the project. Do not add Redis, queue workers, WebSockets, SSE or paid infrastructure.
8. Add or update tests for every behavior change. Add a regression test for each confirmed bug first, watch it fail, then fix.
9. If a step needs a decision or credential you do not have (bucket, mail provider), implement the code path behind config with a safe default, document it in `docs/DEPLOYMENT.md`, and list it in the final report. Do not block.
10. Final report: per phase, what changed, files touched, tests added, anything skipped and why, and open decisions.

## Verified findings (baseline)

Confirmed by running or reading code:

- C1 Tests: 5 of 35 fail, all stale tests. Two use `public/images/laicom-logo.png` (now `rts-logo.png`). Three use a 1x1 grayscale alpha PNG base64 fixture that GD cannot decode (AuthorizationAndSupportTest, ReceiptRewardWorkflowTest). One (ReceiptRewardWorkflowTest ~line 142) expects admin release to set `claimed`, but the implementation sets `released` and the customer confirms to reach `claimed`.
- C2 `Admin/ClaimController::index` validates `claim_filter` in `all,awaiting_release,claimed,none_claimed`, but the view and query also use `awaiting_customer`, so that filter returns a validation error.
- C3 `Admin/ClaimController::approve` reads `$request->input('selections.' . $productName)`; product names containing a dot (e.g. "1.5L") break dot notation and return null.
- C4 `PremiumProductController::update` accepts an absolute `stock` from a possibly stale form and writes the difference as a restock. Reproduced: stock 20, reservation of 5 makes it 15, renaming from a stale form restores 20 with a phantom "restock +5". `InventoryController::update` has the same pattern.
- C5 Inventory names are not unique (no validation, no index); case and whitespace variants are accepted. Product identity across `inventories.name`, `promotions.buy_product_name` and `receipt_items.product_name` is by string. Renaming an inventory item does not update promotions. `InventoryController::destroy` does not check promotions.
- C6 No throttle on POST `/register`, POST `/dashboard/submit-order`, POST `/api/receipts`. Captcha only on registration. Login throttle is per IP only.
- C7 `config/sanctum.php` `expiration` is null; API login mints a new full access token each time and never revokes old ones; admins can mint tokens.
- C8 `database/seeders/DatabaseSeeder.php` creates an admin with a fixed password and an email/name containing the client name, plus demo customers with a fixed password.
- C9 The repo/zip contains `.env`, `storage/logs/laravel.log`, receipt and ticket images, and `.env.example` contains a real looking `APP_KEY`.
- C10 `.env.example`: APP_ENV=local, APP_DEBUG=true, LOG_STACK=single, LOG_LEVEL=debug, no secure cookie setting. `bootstrap/app.php` has no trusted proxy configuration.
- C11 `config/filesystems.php` private and public disks are local folders; AWS settings empty; README says production runs on Laravel Cloud (ephemeral disk risk, unverified).
- C12 Housekeeping: `.gitignore` contains a UTF 16 line with NUL bytes; `CustomerController::orderSlip` calls an undefined `privateFile()` and is unrouted; `inventories.reserved_stock` unused; `resources/views/welcome.blade.php` unrouted; `public/images/Red RTS Rewards Tracker Logo.png` duplicates `rts-logo.png`; duplicate HTML id `modal_empty_msg` in `customer/dashboard.blade.php`; stale "placeholder" comment on the reports route; `receipt_items.unit_price` stored but unused.
- C13 Client/supplier names appear in: `public/css/laicom.css` and `laicom-*` class names, `admin/dashboard.blade.php` labels ("SELECT UNILEVER PRODUCT", aria labels), README (title, logo reference to a missing file, live URL), seeder admin name/email/password, seeded product named after the supplier.

Suspected (write a test; fix if reproduced):

- S1 Deadlock: `ClaimService::requestClaim` locks receipt then rewards; `release()` locks rewards then receipt.
- S2 `ReceiptSubmissionService::compressSlip` decodes full images with GD with no pixel cap; very large photos can exceed memory_limit (fatal).
- S3 Behind a proxy `$request->ip()` may be the proxy IP (shared rate limit bucket, wrong audit IPs).
- S4 Polling keeps sessions alive indefinitely.

---

## Phase 0: Stabilize and clean

1. Fix the stale tests: generate the PNG fixture with GD in a shared test helper (valid image), change logo path to `rts-logo.png`, and update the release test to release then `customer.receipts.confirm-received` before asserting `claimed`. Add a test that release alone yields `released`.
2. Add factories: Receipt, ReceiptItem, Promotion, PremiumProduct, Inventory, EarnedReward. Refactor the new tests to use them (do not mass rewrite old ones).
3. Seeder: remove the fixed admin password and client name. Create an artisan command `lrts:make-admin {email} {name}` that prompts for a password (or generates one and prints it once). Seed demo customers and demo catalog only when `app()->environment('local')`. Replace supplier named seed data with neutral names.
4. Fix `.gitignore` (UTF-8, no NULs; ignore `.env`, `storage/logs/*.log`, `storage/app/private/*`, `public/storage`). Remove from the repo anything that should not ship: `.env`, logs, `.phpunit.result.cache`, private receipt and ticket images.
5. Replace the real looking `APP_KEY` in `.env.example` with an empty value. Add a note in `docs/DEPLOYMENT.md`: rotate the Turnstile secret and APP_KEY anywhere they were shared.
6. Housekeeping from C12: remove `orderSlip`, unused `reserved_stock` usage (keep the column unless a safe migration is trivial), unrouted `welcome.blade.php`, duplicate logo file, stale route comment, duplicate id.

Exit: tests green, `git grep -i "uniliver\|unilever"` clean in seeders and code paths touched here.

## Phase 1: Data integrity

1. Stock edits (C4): in `PremiumProductController` and `InventoryController`, stop treating the edit form's stock as an absolute overwrite. Preferred: add a separate "adjust stock" action (signed quantity, reason required, type `restock` or `adjustment`) that goes through `PremiumStockLedger` (premium) or a locked update plus `stock_movements` (inventory). The edit form changes only name, category, dates and image. If the UI change is too large, fallback: send hidden `expected_stock` and reject with a validation error when it differs from the locked value. Regression test: reserve after form load, submit edit, stock must stay 15 and no phantom restock row exists.
2. Catalogue identity (C5): migration adds `inventories.name_key` (lowercase, trimmed, whitespace collapsed) with a unique index, backfilled; resolve existing duplicates by suffixing and logging them, never deleting. Add nullable `inventory_id` to `promotions` and `receipt_items`, backfilled by `name_key` match. Keep `buy_product_name` and `product_name` as snapshots. Validation: unique normalized name on create and update. Rename: update `promotions.buy_product_name` for that item inside the same transaction and write an activity log entry. Delete: block when any active promotion references the item. `RewardCalculator` matches by `inventory_id` when present, otherwise by normalized name. `ReceiptSubmissionService` and the API still accept `product_name`, resolve it to `inventory_id`, and store both.
3. Reward selection (C3): change the review form radios to `selections[<groupIndex>]` or by buy product `inventory_id`, and read via array lookup, never dot notation. Test with a product named "Shampoo 1.5L" and two competing promotions.
4. Filter (C2): add `awaiting_customer` to validation. Default the admin receipts list to `pending`, ordered oldest first, when no status is given.
5. Locks (S1): in `ClaimService::release` lock the Receipt first, then rewards, matching `requestClaim`. Wrap claim, release, confirm, void and approve transactions with `DB::transaction($callback, 3)` so deadlocks retry. Catch `Illuminate\Database\QueryException` deadlock leftovers and return a friendly error, not a 500.
6. Indexes migration: `receipts(status)`, `receipts(user_id, updated_at)`, `earned_rewards(claim_status)`, `earned_rewards(receipt_id, claim_status)`, `tickets(status)`.
7. Add a MySQL service job to CI config (GitHub Actions file if the repo uses it, otherwise document) and a test group `@group mysql` covering parallel approve of the same receipt, and claim versus release. Skip automatically on SQLite.

Exit: tests green; migrations run cleanly on a copy of seeded data and are reversible where feasible.

## Phase 2: Security and hosting

1. Rate limits (C6): named limiters in `AppServiceProvider::boot` via `RateLimiter::for`. Register: 5 per hour per IP. Submit order (web and API): 10 per minute and 40 per day per user. Login (web and API): keyed by lowercase email plus IP, 5 per minute; after 3 failures for that key require the Turnstile field on the login form (reuse `components/auth-captcha`). Return 429 with a friendly message.
2. API tokens (C7): set `sanctum.expiration` (60 days in minutes). On API login delete the user's previous `flutter-mobile-app` tokens. Create customer tokens with limited abilities (`receipts:create`, `user:read`); refuse API login for admin role unless an env flag allows it.
3. Production config (C10, S3): add `.env.production.example` (APP_ENV=production, APP_DEBUG=false, LOG_CHANNEL=daily, LOG_LEVEL=warning, SESSION_SECURE_COOKIE=true, SESSION_ENCRYPT=true, SESSION_SAME_SITE=lax). In `bootstrap/app.php` configure `trustProxies` from an env variable (default none) and document when to set it. Add a security headers middleware (HSTS in production, X-Frame-Options SAMEORIGIN, X-Content-Type-Options nosniff, Referrer-Policy strict-origin-when-cross-origin). Add a CSP only after Phase 3 self hosts assets.
4. Storage (C11): make disks configurable by env. Add an `s3` backed private disk option selected with `PRIVATE_DISK_DRIVER`, default stays local. Replace the per call `Storage::disk('public')->exists()` in `ProductImageResolver` with a path check only. Document bucket setup and a one time migration command `lrts:migrate-uploads` that copies existing private files to the new disk idempotently. Serve ticket PDFs with `Content-Disposition: attachment`.
5. Images (S2): in `compressSlip`, call `getimagesizefromstring` first; reject above 40 megapixels with a clear validation message; log GD failures separately from "invalid image". Test with a synthetic oversized header.
6. Delete the old product or inventory image file when replaced or when the record is deleted (all four controllers: store keeps, update and destroy clean up).
7. Authorization: add `ReceiptPolicy` and `TicketPolicy`; use them in customer controllers and attachment/slip controllers instead of inline `abort_unless`. Behavior must stay identical; existing authorization tests must pass.
8. Log blocked duplicate order number attempts as an activity event (`receipt.duplicate_blocked`) without revealing the other customer.

Exit: tests green including 429 tests, token expiry test, policy tests.

## Phase 3: Design system and shared components

1. Rename `public/css/laicom.css` to `public/css/rts.css` and every `laicom-*` class to `rts-*` via a scripted replace across `resources/views` and CSS; update `filemtime` references. Rename `.lrts-*` leave as is. Add a snapshot style test that every view renders (HTTP 200) for admin and customer after the rename.
2. In `rts.css` define a tokens block (about 12 colors, spacing scale, type scale, radius, shadow). Replace repeated hex values with tokens where it is mechanical; remove `!important` only where trivially safe. Add visible `:focus-visible` styles for all interactive elements and a `prefers-reduced-motion` block.
3. Blade components: `x-status-badge` (single map from receipt, reward, ticket status to label and color, replacing scattered `is-{{ status }}` usage), `x-empty-state`, `x-confirm-modal`, `x-form-field`. Use them in receipts, claims, tickets, inventory, dashboard.
4. Self host Bootstrap 5.3.x and Bootstrap Icons (npm build with the existing Vite or vendored files in `public/vendor`), remove CDN links from `layouts/admin`, `layouts/auth`, `customer/dashboard`. Then add a CSP header.
5. Admin layout: below `md`, convert the sidebar into an offcanvas menu with a top bar toggle. Keep desktop unchanged.
6. Accessibility: `aria-live="polite"` on toast and badge count containers, labels on all inputs, check contrast of status colors, keep modal focus trapping. Keep all caps only on status chips; use sentence case for labels and headings.

Exit: every page renders, no CDN requests, visual parity on desktop.

## Phase 4: Customer workflows

1. Split `customer/dashboard.blade.php` into partials per tab (order, tracker, promos, help, drawer, modals) and move the 10 inline scripts into `public/js/customer/*.js` modules. No behavior change in this step; tests must stay green.
2. Tracker timeline per receipt using existing timestamps (`submitted_at`, `reviewed_at`, rewards' `claim_requested_at`, `released_at`, `claimed_at`). Stop eager loading `activityLogs.user` on the dashboard.
3. Show claim expiry date and days remaining on each unclaimed or requested reward (`expires_at`).
4. Order modal: for each added line show the active promotion it counts toward and how many more to qualify, computed client side from the already provided promotions data (add `inventory_id` or normalized name to the JSON).
5. Rejected receipts: show reason and a "Resubmit" button that opens the order form prefilled with the same items.
6. Notifications: paginated notifications page, per item read, keep the dropdown for the latest 10.
7. Profile page: edit name, store name, phone (same validation as registration), change password (current password required, same password rules). Route group `auth, customer`, throttled.
8. Account recovery: implement Laravel password reset with a configurable mailer (`MAIL_MAILER`). If mail is not configured, hide the "Forgot password" link and add an admin action "Issue temporary password" on a customer record (admin only, forces change at next login via a `must_change_password` flag, logged in activity log).
9. Pagination on the customer dashboard: latest 10 receipts with "load more" (server rendered page links are acceptable), tickets limited to latest 10.

Exit: tests for each new route, ownership checks, and validation.

## Phase 5: Administrator workflows

1. Receipts page: remove the per receipt modals. Add a single shared detail modal loaded on demand from `GET admin/receipts/{receipt}/panel` (HTML partial). The panel shows the large slip image (zoom), customer name, store, phone, order date, customer note, items with product image, category and the promotion each line counts toward, duplicate slip warning, reward options with stock, and approve and reject controls (reject reason presets preserved). Reuse `x-product-image` and existing services. Keep POST routes unchanged.
2. Replace the per receipt duplicate slip query with one grouped query for the page's hashes. Stop eager loading `activityLogs.user` on the list; load history only in the panel. Remove the `Inventory::get()` full load; fetch images for the page's product names only.
3. Claims: confirm dialog before release showing claim code, customer, store, phone and items. Keep code lookup field focused on page load.
4. Dashboard (`DashboardController`): apply `limit` and counts in SQL instead of loading all rows and calling `take(5)`; stop loading premium products twice.
5. Tickets: paginate (20 per page), add search by subject or customer, load replies only for the selected ticket.
6. Premium list: show reserved versus on hand if derivable from active earned rewards, and warn when two active promotions overlap on the same buy product.
7. Reports: add a claim funnel (approved, requested, released, confirmed, expired) and average review time; cache each report for 60 seconds per parameter set.

Exit: tests for panel authorization (admin only), approve and reject still work, page query counts asserted.

## Phase 6: Automatic updates (conditional polling)

Do not use SSE or WebSockets.

1. Extend `Admin/NavCountController` (route `admin.nav-counts`) to return counts plus a `version` string, set an ETag, honor `If-None-Match` with 304, cache the computed payload for 5 seconds with the database cache, `Cache-Control: no-store, private`, throttle 60 per minute.
2. Add `Customer/PulseController` at `GET /dashboard/pulse` (auth, customer). Returns only the authenticated user's data: unread notification count, pending receipt count, a receipts version (count plus max `updated_at`), tickets with unseen support replies count. Never accept a user id. Same ETag, cache (keyed per user), throttle.
3. Add `GET /dashboard/tickets/{ticket}/replies?after={id}` (owner only, excludes internal notes) and the admin equivalent for the selected ticket.
4. Create `public/js/rts-poll.js`, used by both layouts, replacing the inline interval code in `layouts/admin.blade.php`. Requirements: `setTimeout` chain (no overlapping requests), `AbortController`, pause when `document.hidden`, immediate refresh on `visibilitychange` to visible, exponential backoff 20s, 40s, 80s, cap 120s with jitter, reset on success, stop on 401 or 419 and show a "session expired, sign in again" notice, stop after 15 minutes without user input (mouse, key, touch) so sessions can expire (S4).
5. Intervals: admin counters 20s; customer tracker and promos tabs 30s, other tabs 60s; open ticket thread 10s only while visible; admin tickets page 15s.
6. Never replace page content automatically. Update badges and read only status chips in place. When a list changed, show a dismissible banner ("3 new receipts, refresh list") that fetches the list partial and preserves filters, scroll and unsaved input. Keep `aria-live` announcements polite.
7. No polling for inventory stock, promotions, reports, activity log or profile.
8. Tests: pulse scoping (user A never sees user B), 304 on unchanged ETag, throttle, auth required, internal notes excluded, correct counts after status changes.

Exit: manual check that a hidden tab sends no requests and backoff works (document the steps in `docs/DEPLOYMENT.md`).

## Phase 7: Performance and release readiness

1. Image pipeline: generate and store a 400px thumbnail for product images on upload; use `loading="lazy"` and `width/height` on product images; compress `public/images/portal-bg.jpg` below 300 KB (webp with jpg fallback). If the background image or any product image is a client photograph, replace it with a neutral RTS asset and list it in the report.
2. Enable gzip or brotli guidance in `docs/DEPLOYMENT.md`; add far future cache headers for versioned assets.
3. Database: confirm indexes from Phase 1 exist; add `CHECK (stock >= 0)` for `premium_products.stock` and `inventories.stock_balance` on MySQL 8.0.16+ (guard with a driver check so SQLite tests still run).
4. Ops docs in `docs/DEPLOYMENT.md`: scheduler must run every minute (`php artisan schedule:run`) so `lrts:expire-rewards` fires at 00:10; `php artisan storage:link`; `php artisan config:cache route:cache view:cache`; uptime monitor on `/up`; daily database backup plus backup of receipt and ticket storage; a restore drill checklist; log rotation (daily, 14 days); how to create the first admin with `lrts:make-admin`.
5. Load test script (k6 or ab) for login, dashboard, receipts list, pulse endpoints against a seeded MySQL; record results in the docs. Target: pulse p95 under 100 ms with 50 concurrent users.
6. Update README: neutral RTS branding only, accurate architecture, setup, test and deployment steps; remove the client name, company, live URL and missing image references. Update AGENTS.md with new files and commands.

Exit: full suite green on SQLite, MySQL group green in CI or documented manual run, docs complete.

## Test checklist (must exist by the end)

- Auth and permissions: admin routes forbidden to customers, customers cannot read other customers' receipts, slips, tickets, attachments; internal notes never reach customers.
- Orders: required slip, date window, duplicate order number across users, reuse after reject or cancel, normalization, dotted and mixed case product names, 50 line limit, oversized image.
- Promotions: exact threshold, multiples, date edges, inactive, overlapping options requiring a choice, expired reward product.
- Stock: reserve on approve, void returns stock, expiry returns stock unless product expired, no negative stock, stale edit does not create a phantom restock, concurrent approvals.
- Claims: request, release, confirm, wrong user, repeated requests idempotent, lock order no deadlock under MySQL.
- Tickets: create, reply, resolved ticket blocks replies, attachments private.
- Limits: 429 on register, submit, login, API.
- API: token expiry, role restriction, receipt submission, backward compatible response shape.
- Polling endpoints: scoping, ETag, throttle, auth.
- Rendering: every admin and customer page returns 200 after the CSS rename and component changes.

## Definition of done

All phases complete, all tests green, no secrets or client branding in the repo or built output (`git grep -i` for the client and supplier names returns nothing in code, views, CSS, README, seeders), `docs/DEPLOYMENT.md` complete, and a final report listing changes, skipped items and open decisions (uploads bucket, mail provider, whether any images are client photographs).