# New Ops to public COA audit — September 17, 2026

## Cause and live correction

Selank SL1026246JP (WordPress 1909) and Semax SM1026246JP (1911) contained in-testing/pending metadata but were drafts. Control updates preserved draft publication status, trusted historical sync success, and ignored failed public URL verification. The separate New Ops COA date-save action did not synchronize WordPress.

Control master bce92aa was deployed successfully to Sevalla; the live version banner reports bce92aa, built 2026-09-18T01:24:05.874Z. All 1,218 tests across 41 files, TypeScript, and focused ESLint passed. A complete Control backup and isolated recovery were verified before release.

GitHub Actions build #176 succeeded in 19m10s, including all 84 browser-route checks: https://github.com/Beringela001/pepselect-control-app/actions/runs/35294997287. The actual configured registry target is ghcr.io/beringela001/pepselect-control-private:bce92aa (not the older control-app image name in AGENTS.md). Published manifest digest: sha256:5992e7f8e6e0a2609e20bc9934641f13296f701805eaf077dbcdbc8befcfd99b. Sevalla builds/deploys the same source directly; this registry image is the separate fallback.

Both existing WordPress posts were published without changing results or releasing inventory. New Ops Selank Save dates returned Website SUCCESS for in-testing/pending and stored the canonical public link. Semax Edit lab dates / Save lab timeline likewise saved the link. Both kept September 16 sent and September 17 delivered/admitted dates.

Reconciliation a7053d3f-ba3c-481b-9823-fd5d04b25549 finished September 17 at 9:35:53 PM Eastern: four checked, four succeeded, zero skipped, zero needing attention. It covered RT1026258JP, TZ2026258JP, SL1026246JP, and SM1026246JP. It reused existing records and did not change inventory or final test outcomes.

Logged-out product requests now show In testing for Selank and Semax. Both compounds appear on /testing/ as Testing in progress. Selank's exact stored public URL returns HTTP 200 with the matching batch and status.

## Page-cache correction

Signed-in pages initially differed from logged-out Selank, which retained Restocking Soon. Kinsta auto-purge was already enabled, but custom dependent paths were empty. Added Group Paths /product/, /testing/, and /shop/, then cleared site cache. Verified logged-out Selank changed to In testing after propagation.

Ordinary COA REST metadata-only changes did not call WordPress post-update hooks. The plugin fix now calls wp_update_post after changed metadata has landed, letting the existing cache integration see the finished record. Unchanged metadata avoids unnecessary invalidation. A regression test verifies both cases on a published pending record advancing to in-testing.

## Plugin 0.7.18 — tested, deployment pending

- Date-only labels now use the existing admin date parser and explicit site timezone, avoiding September 25 displaying as September 24.
- REST metadata-only updates notify WordPress page-cache integrations.
- PHP 8.1 and 8.2 / WordPress 6.5.5 regression jobs passed: https://github.com/Beringela001/coa-Plugin/actions/runs/35295751574
- Source commit: b3d2226cc0129b6f09501e88bd0e9c137e13efff.
- Installable package: dist/pepselect-coa-archive-0.7.18.zip, 117 files.
- SHA-256: d3e6ade577cab7669b2c2f161d76c16ba268d7e2d3897cca3bd01e6b7173c7ec.
- Rollback package 0.7.17 retained.

Staging installation completed after the owner signed in. WordPress confirmed replacement of 0.7.17 with 0.7.18. Package SHA-256 was verified immediately before upload. All 18 archive time labels match their stored calendar dates, including compact Ymd and ISO formats. The Retatrutide RT2026205JP report renders its batch note, measured values, and laboratory data; its vial image and expanded certificate image both load. REST metadata cache notifications and unchanged-write behavior are covered by the passing PHP regression jobs. No live REST mutation test has been claimed. A requested viewport override did not change the Chrome page width; this turn therefore verifies desktop rendering only.

Live Kinsta manual backup slots remain full (verified again after staging installation); action-time confirmation is pending to replace only the oldest September 15 8:20 PM backup, "Before COA 0.7.15 saved evidence and untested rows", with a fresh named backup. No backup has been deleted. Plugin 0.7.17 remains live; the date-label bug and automatic metadata cache notification remain pending Live deployment. Do not represent the staging package as deployed to Live.
