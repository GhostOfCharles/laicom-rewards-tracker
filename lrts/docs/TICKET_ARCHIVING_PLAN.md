# Ticket cleanup proposal

Status: implemented, reviewed, and verified locally. Authorized scope includes archive/restore and the four verified bugs. No commit, push, or deployment was performed.

## Completion record

All five execution steps below are complete. Archive/restore is administrator-only and restricted to closed tickets; archived conversations remain available to their customer, including authorized private downloads. Reopen and live admin controls now follow actual status in both directions without replacing drafts. Admin change fingerprints include receipt/reward/ticket states and archive state, covering same-second updates.

Business dates now use configurable BUSINESS_TIMEZONE=Asia/Manila. Stored timestamps, claim-window durations, and existing timestamp values remain UTC. Order validation/form limits, active promotions, product/inventory expiry, report day boundaries, and the daily expiry schedule share the business calendar.

Verification:

- Final full SQLite suite: 73 passed, 7 failed, 4 skipped, 765 assertions. All seven failures are the same pre-existing unfinished DashboardHeartbeatTest/VisualAssetsTest failures; no existing test was removed or weakened. MySQL-only tests are skipped in this default run.
- Dedicated MySQL 8.4 group: 4 passed, 20 assertions, including duplicate archive requests and archive/reopen concurrency. Test database: rts_test_archive_20261108, separate from application records.
- Forward migration on a separate MySQL legacy fixture preserved the ticket, receipt link, reply and attachment path; SQLite forward-migration regression preserved existing history and foreign-key enforcement. The migration runs outside a transaction so SQLite can safely disable foreign keys during its table rebuild.
- Existing deterministic polling and customer browser checks passed, including private-note exclusion, live replies, closed state, and customer draft preservation.
- New isolated application browser check passed actual Archive/Restore/Reopen submissions plus resolved/closed/archived/open polling states with admin drafts preserved. Screenshots verified at 1440, 390 and 320 pixels; archive confirmation matches existing navy/border/button styles. Latest artifacts: OS temp rts-archive-qa-qXP5HW.
- New-file Pint checks, JavaScript syntax checks, route inspection and git diff whitespace checks passed.

Fresh review used ds-review-loop with a separate reviewer for two rounds. Round 1 found two inventory date paths still using UTC; both were corrected and covered by a midnight regression for create/update, expiry badge, and report consistency. Round 2 verdict: APPROVE. No findings were declined or left open. Tracked diff at first review: 24 files, 236 additions / 58 deletions; final tracked diff: 26 files, 240 additions / 61 deletions, plus new implementation/test/plan files.

Local application setup: only the new archive migration was pending. A full logical local database backup was saved outside Git at C:/Users/Charles/.codex/backups/rts/before-ticket-archive-20261108-023520.sql, then that specific migration was applied. Hash comparison over all pre-existing columns confirmed unchanged records across 22 data tables. Migration status now shows batch 3. Remote environments still require their normal backup and migration release procedure.

## Approved execution plan

1. Add focused regression tests for archive authorization/history, closed-ticket reopening, same-second admin updates, and Philippine midnight dates. Acceptance: reproduce missing behavior before edits.
2. Implement the migration, locked archive/restore transitions, audit events, scoped admin queue, and matching UI. Acceptance: focused archive workflow tests pass without deleting records or files.
3. Repair active polling and fingerprints, preserving customer drafts. Acceptance: deterministic and real-browser polling regressions pass, including admin status controls.
4. Introduce configurable Asia/Manila business dates while retaining UTC timestamp storage. Cover order validation/form limits, promotion visibility, expiry, report day boundaries, and scheduler timezone. Acceptance: midnight boundary regressions and existing reward/report tests pass.
5. Run the complete baseline suite, isolated migration checks and browser UI QA; get a fresh reviewer to check the scoped diff, fix real findings, then record results here. Acceptance: no new regressions beyond the seven known unrelated failures; report MySQL/browser checks actually performed and any limitations.

Plan recheck: no global archive scope, no hard deletion, no automatic age cutoff, no timestamp data conversion, and no integration of unrelated untracked heartbeat/assets. These preserve the existing intentional workflow. Admin replies to closed tickets will require Reopen, matching the current interface's stated behavior. Resolved tickets retain their existing staff reply capability.

## Recommended behavior

Add an administrator-only Archive action for closed tickets. Archive removes a ticket from the default administrator queue; an Archived filter provides access and a Restore action. Restore returns it to the queue with its closed status intact. Customers retain their conversation history and authorized attachment access. This addresses queue clutter, not disk usage.

Use a nullable indexed archived_at column and a nullable archived_by foreign key to users with nullOnDelete. Archiving is separate from ticket status. Do not introduce a global model scope or SoftDeletes: customer history, attachment authorization, and reply polling still need these records.

Only closed tickets can be archived. Resolved tickets must be closed first using the existing status action. Do not introduce an arbitrary age cutoff, automatic removal, bulk operations, or permanent deletion in the first version. Ticket replies cascade on hard deletion, but private attachment objects have no ticket deletion cleanup hook.

## Interface

- Place Archive beside ticket actions for a closed, unarchived ticket. Reuse adm-btn-outline, Bootstrap Icons' archive icon, and the existing confirmation-modal component.
- Confirmation: "Archive this closed ticket? It will move out of the admin queue. Its messages and attachments will remain available." Use Cancel and Archive ticket.
- Add Archived to the existing filter pills. All and each existing status filter exclude archived tickets. Archived includes only archived tickets and supports the current search and pagination.
- Archived details show an Archived label, date, and Restore button. A direct link to an archived ticket redirects to its Archived view so queue selection remains consistent.
- Use the existing navy, white, borders, button sizing, modal styling, and feedback toast. Keep controls usable at mobile widths and by keyboard.
- Provide Reopen for closed, unarchived tickets. Archived tickets must be restored first. Customers retain the same closed conversation and cannot reply until staff reopen it.

## Implementation sequence and acceptance checks

1. Add the forward-only migration, Ticket casts, and explicit archived/unarchived query scopes. Check: migration on isolated SQLite and dedicated MySQL preserves all existing tickets, replies, and receipt links; existing tickets remain unarchived.
2. Add authenticated admin POST archive/restore endpoints with transaction locks and audit events ticket.archived / ticket.restored. Record archival actor/time, clear current archive metadata on restore, and retain both events. Check: guests redirect, customers receive 403, active/resolved tickets cannot archive, repeat requests are harmless, and no attachment or reply is deleted.
3. Apply explicit scopes to the administrator queue and selected-ticket lookup. Guard admin reply/status mutations under the same ticket lock: archived tickets require restoration before mutation. Leave customer queries and attachment policies unchanged. Check: search, pagination, direct selection, archived visibility, restore, ownership, and internal-note privacy behave as specified.
4. Add the matching Archive/Restore UI and confirmation, and repair the existing closed-ticket Reopen control gap and polling branch that disables status controls. Check: resolved tickets can still close; closed tickets can archive or reopen; archived tickets can restore; controls follow state changes in both directions without discarding drafts.
5. Invalidate relevant pulse/thread caches on archive/restore and include archive state plus actual ticket statuses in the active admin change fingerprint. Keep existing customer modal/draft protections. Check: same-second mutations are detected, closed archived tickets do not inflate active counts, archive/restore appears consistently after navigation, and concurrent replies/reopening cannot leave an active archived ticket.
6. Add focused archive workflow regressions and run existing support, integrity, polling, and full application checks. Perform browser QA of desktop/mobile queue, confirmation, archive/restore, reopened conversation, private attachment access, and an open customer draft during an admin archive. Check: new tests pass, existing passing tests remain passing, and known unrelated failures are reported separately. Never run checks against the populated application database.

Expected files: a new database migration; app/Models/Ticket.php; app/Http/Controllers/Admin/TicketController.php; app/Http/Controllers/Admin/NavCountController.php; routes/web.php; resources/views/admin/tickets.blade.php; public/js/rts-poll.js; and focused feature/browser tests. Add shared CSS only if existing classes cannot meet the layout.

## Plan critique and revision

- Resolved-only archiving was rejected because it blurs an existing state that still permits staff replies. Closed-only archiving is simpler and preserves that design.
- A global archive scope was rejected after checking CustomerController, TicketPolicy, TicketAttachmentController, and TicketRepliesController: it could hide customer history or disrupt authorized downloads and polling.
- Queue filtering alone is insufficient. Direct selected-ticket lookup, reply/status endpoints, same-second pulse fingerprints, and concurrent mutations must respect archive state.
- The existing closed-ticket UI has no reopen action, and admin polling disables status controls for resolved tickets. These two previously verified defects are prerequisites for a coherent cleanup workflow. The Manila timezone issue is independent and should be a separate change.
- Permanent deletion would require an explicit retention decision and reliable storage cleanup/retry behavior. Archive does not claim to reclaim storage.

## Verification performed before implementation

Schema review confirms ticket_replies.ticket_id cascades on ticket deletion, tickets.related_receipt_id references receipts with nullOnDelete, and private attachment files are stored separately. Archive metadata changes therefore need no receipt/reward/stock writes.

Current targeted baseline: php artisan test --compact --filter='AuthorizationAndSupportTest|PollingTest|ProductionIntegrityTest' passed 13 tests and 143 assertions. Both node tests/Browser/polling-smoke.cjs and node tests/Browser/customer-polling-smoke.cjs passed, including customer modal draft preservation.

Earlier full-suite baseline before implementation: 64 passed, 7 failed, 3 skipped. The failures concern pre-existing untracked heartbeat integration and visual preloads. Completed implementation results are recorded above.
