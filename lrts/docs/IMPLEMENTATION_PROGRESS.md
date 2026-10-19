# RTS implementation progress

## Status
Application phases 0-7 implemented and verified as below. Existing application database, credentials and private uploads were not reset, rotated or deleted. New schema migrations have been exercised on isolated SQLite/MySQL fixtures, not applied to the existing application database. No deployment or Git commits were made; pre-existing README/roadmap work was preserved.

## Completed
- Phase 0: repaired obsolete image fixtures and release/confirmation tests (baseline 5 failing/30 passing became 35 passing); local-only neutral seeder, hidden-password administrator command, six factories, sanitized example key, repaired ignore encoding, removed dead method/view/duplicate ID and identical duplicate logo.
- Phase 1: stale-stock optimistic guards, normalized unique catalog keys and nullable inventory IDs/backfill with snapshots, rename propagation/deletion protection, indexed/dotted reward selections, awaiting-customer filter and pending-first queue, receipt-first locks, sorted product locks, retries, indexes and friendly database conflicts.
- Phase 2: named request limits, failed-login CAPTCHA escalation, bounded/rotated/scoped API tokens, admin API opt-in, policies, headers/CSP, trusted proxy support, production config, image pixel/memory limits, private PDF downloads, duplicate-attempt audit, safe upload failures and file lifecycle cleanup. Durable-disk configuration/copy command implemented; external adapter setup is blocked locally (below).
- Phase 3: neutral rts.css/selectors, self-hosted Bootstrap/icons with licenses, shared status/empty/field/confirmation components, accessible mobile admin navigation, red focus/validation, loading feedback, responsive customer navbar and retained approved portal styling.
- Phase 4: customer tab/modal/drawer partials and nine extracted scripts; timestamp timelines, expiry, promotion progress, resubmit, paginated receipts/rewards/tickets/notifications, account/password management, configured recovery and audited forced-change temporary passwords. Notification preview refreshes before marking only displayed items read.
- Phase 5: one on-demand receipt review panel with images, quantities, categories, customer/store/contact, promotion/stock choices and history; grouped duplicate lookup; bounded SQL catalog/inventory/claims/tickets; selected-thread loading, overlap warning, richer release confirmation; cached reports/review-time and claim metrics; spreadsheet formula escaping.
- Phase 6: scoped cached ETag/304 endpoints and incremental replies; 20s admin, 30/60s customer, 10/15s visible conversations; hidden/idle pause, abort/backoff, overlap prevention, expired-session notices, safe badges/status updates and explicit lightweight list refresh preserving drafts/modals/scroll.
- Phase 7: bounded 400px product images with rollback/replacement cleanup; background reduced to 120,975-byte JPEG / 68,486-byte WebP with fallback; stock constraints, CI MySQL job, load harness, deployment/backup/restore guidance, README and local AGENTS updates.

## Verification
| Check | Result |
|---|---|
| php artisan test | 58 passed, 623 assertions; 3 MySQL-only tests correctly skipped |
| Dedicated MySQL 8.4.3 group | 3 passed, 10 assertions: parallel approval, claim/release locks, negative stock rejection |
| Legacy-data migration | Duplicate rows and balances retained, normalized keys disambiguated; own down/up cycle verified on SQLite |
| Browser (isolated MySQL, Edge) | Desktop/390px/320px: no horizontal overflow or JS exceptions; actual image submission, 270px scrolling dropdown, shared admin panel/mobile menu, incremental public reply and notification preview; typed support draft preserved; internal notes excluded |
| Polling JS harness | Hidden/idle pause, immediate resume, backoff/reset, no overlap, expired-session stop |
| Source/dependencies | JS syntax checks, touched PHP formatting, Composer validation/platform requirements, zero locked-package vulnerability advisories; isolated route-cache generation and Blade view-cache compilation passed |
| Representative 50-user load | Harness added; not executed or claimed to meet p95 target |

## Modified files (grouped)
- app/Http/Controllers: admin catalog/review/claims/reports/tickets/counters, CustomerController, auth/API, private download controllers; new Customer/AccountController and PulseController, CustomerAccountController, PasswordResetController, TicketRepliesController.
- app/Services: receipt/reward/claim/image rules; new LoginProtection, ProductImageStorage, PulseResponse. Models/factories for catalog/receipts/rewards; Policies, ProductImageObserver; ReportTableExport; AppServiceProvider and bootstrap/app.php.
- Four 2026_10_19 migrations; seeder; routes web/api/console; filesystem/Sanctum/business config; composer files, env examples, .gitignore and public/.htaccess.
- resources/views: layouts, shared components, customer partials/profile/notifications, auth recovery, admin panel/customer/list views and catalog/report/support views.
- public: rts.css/auth.css, self-hosted vendor assets, compressed background, customer scripts, rts-poll.js, receipt-panel.js, admin-menu.js and form-feedback.js.
- Tests: repaired baseline tests; ProductionIntegrity, ProductionSecurity, AccountWorkflow, Polling, ImageStorage, CatalogMigration, ProductionRegression and MySqlConcurrency; Browser/Support/Load harnesses; ../.github/workflows/tests.yml.
- README, AGENTS (still ignored by the existing user rule), this log and DEPLOYMENT.md. Input production roadmap left unchanged.

## Remaining / external configuration
- Back up and rehearse, then apply new migrations to the existing deployment database before using this version. Review any disambiguated catalog entries and old demo accounts manually.
- Windows access errors block AWS SDK extraction. The adapter requirement/lock are ready, but complete composer install on the deployment host before S3 use. Automatic approval review rejected removal of the partial vendor directory; a non-destructive rename was also denied by Windows. The directory was preserved.
- Configure/verify bucket policy, mail delivery, CAPTCHA hostname/keys, HTTPS/proxies, scheduler, monitoring and scheduled database/upload backups; conduct a restore drill. Real SMTP/S3/CAPTCHA production integration was not verified.
- CI workflow and k6 50-VU benchmark are prepared, not remotely executed. Pulse p95 <100ms under production load is unverified. Nonce-only CSP is deferred while legacy inline handlers remain.
- Verify rights to supplied artwork. No infrastructure deployment, live secret rotation or existing-data deletion occurred. A newly created rts_test_* database contains only test/browser fixtures and is retained for repeatable checks; QA server/browser are stopped after verification.

Cleanup: stopped the dedicated 8900 QA server and 9331 headless browser; removed the temporary QA credential file and four QA-only receipt images after verifying the application database did not reference them. Existing customer files were retained.
