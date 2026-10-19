# RTS deployment and recovery

## Release procedure
- Back up database and uploads; rehearse restoration and forward migrations on a copy. Never reset a populated database.
- Install PHP 8.2+ with GD, ZIP, PDO MySQL and Composer-required extensions. Run composer install --no-dev --optimize-autoloader from the lockfile.
- Use .env.production.example; configure HTTPS, database, a new installation key, CAPTCHA, secure encrypted cookies and explicit trusted proxy addresses. APP_DEBUG must be false.
- Do not package .env, logs, caches or private uploads. Git tracks code, not secrets/uploads. Existing local data and credentials were preserved; review existing demo accounts manually. Rotate shared CAPTCHA secrets through the provider. Do not rotate a live APP_KEY without a backup/re-encryption plan.
- Create administrators with php artisan lrts:make-admin email@example.com "Administrator". Password prompts are hidden; the production seeder creates no accounts or sample data.
- Apply php artisan migrate --force only after backup and rehearsal. Catalog duplicates are retained, disambiguated and logged; ambiguous historical name matches link to the first item. Review these records manually. MySQL 8.0.16+ stock CHECK constraints skip existing negative balances for manual reconciliation; MariaDB/older versions need separate verification.
- Run php artisan storage:link for local public assets; then config:cache, route:cache and view:cache. Ensure storage/bootstrap cache directories are writable. Run schedule:run every minute so lrts:expire-rewards runs at 00:10. Monitor /up and error/backup alerts; use daily logs with 14-day retention.

## Storage and mail
Local disks remain the default. Ephemeral hosts need durable buckets before accepting uploads. Configure PRIVATE_DISK_DRIVER=s3, PRIVATE_BUCKET and optional PRIVATE_STORAGE_PREFIX, with AWS endpoint/region/credentials. Deny anonymous reads/listings. Receipts and support attachments stay behind authorized download controllers; PDFs download as attachments.
Configure PUBLIC_DISK_DRIVER=s3, PUBLIC_BUCKET, optional PUBLIC_STORAGE_PREFIX and PUBLIC_STORAGE_URL for product assets. Remote PUBLIC_DISK_VISIBILITY defaults to private; deliberately configure the public CDN/bucket policy, or public ACL only when supported. Never expose the private bucket.
The required S3 adapter is locked in Composer, but Windows file access blocked local SDK extraction. Automatic approval review rejected deleting its partial vendor directory; it was preserved. Finish composer install on the host and verify real bucket writes/downloads before enabling remote disks.
Run php artisan lrts:migrate-uploads --dry-run, then without --dry-run, to copy legacy-private to private. Public copies use --from=legacy-public --to=public. Originals remain and existing target keys are skipped. Pause writes for final copy; compare counts/sizes and sample hashes/downloads before switching traffic. Investigate mismatched keys manually before removing originals.
Set a real MAIL_MAILER/provider and PASSWORD_RESET_ENABLED=true to expose reset links. Log/array mailers do not prove delivery. Test delivery, expiration and single use. Without mail, an administrator can verify identity and set a temporary password in Customer accounts; share it privately. Sessions/tokens are revoked and the customer must change it on sign-in. ALLOW_ADMIN_API is an explicit opt-in, not needed for web administration.

## Backup and restore drill
Arrange consistent daily database backups and daily copies of both upload disks to a separate protected destination. Object redundancy or lifecycle deletion alone is not a backup. Proposed initial retention is 7 daily and 4 weekly copies; encrypt backups and alert on failures. Agree targets such as 24-hour recovery point and 4-hour recovery time; these are proposals, not measured guarantees.
At least monthly restore a database plus matching uploads into an isolated environment, restore the compatible key/config, apply appropriate migrations and verify authentication, private downloads, stock balances and claim history. Record dates, duration and results. Never restore over the live database. Keep deployment secrets in a protected secrets manager. Backup infrastructure/retention still require operator setup; no deployed snapshots are claimed.

## Verification and performance
- php artisan test: SQLite regressions; MySQL-only tests skip by default.
- Set RTS_MYSQL_TEST_DATABASE to an existing isolated rts_test_* database, then php artisan test --group=mysql. Tests run forward migrations and parallel PHP workers without resetting data. GitHub Actions provisions its own MySQL service. Never point this at the application database.
- node tests/Browser/polling-smoke.cjs: deterministic hidden/idle pause, immediate resume, backoff, overlap and expired-session behavior.
- tests/Browser/visual-smoke.mjs: local-only Edge CDP on 9331, test server on 8900 and temporary QA config outside the repo. Guarded workers inject only isolated test fixtures. Browser results/screenshots are written under the OS temp directory. Real upload, incremental support, notification updates and mobile layout were verified; hidden/backoff behavior was verified in the deterministic harness.
- Also inspect DevTools: hidden tabs should send no polls; offline retries should back off; 15 minutes without input should stop requests; typed forms/open dialogs should survive updates.
- Install k6 separately and run k6 run tests/Load/pulse.js against a dedicated seeded server. Supply BASE_URL and RTS_LOAD_ACCOUNTS through the environment with 50 distinct test accounts (email/password/role); do not log or commit them. Nonlocal targets require ALLOW_NONLOCAL_LOAD=true. The script exercises login, lists and pulse at 50 VUs; target pulse p95 <100ms has not been measured here. PHP's single-worker development server is not a production benchmark.
- Enable gzip/brotli in the host and one-year immutable caching only for versioned assets. Never publicly cache authenticated HTML, private files or pulse responses. CSP retains unsafe-inline for existing handlers/styles and permits CAPTCHA providers; nonce-only CSP is a separate migration.

## External requirements
Deployment hostname/proxies, mail/CAPTCHA, bucket policy, backup destination, monitoring and representative load testing need operator configuration. Review rights to supplied background/product artwork. No deployment, live key rotation or populated database reset was performed.
