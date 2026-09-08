# COA Archive 0.7.9 release preparation

Paulo authorized deployment of the approved report refinements on September 8, 2026. Source is based on 0.7.8 with local refinement commits 34448bd and 322501e. This package also includes the preceding guarded historical/current navigation and detail-report evidence safeguards.

## Package

- Install: `dist/pepselect-coa-archive-0.7.9.zip` (113 runtime/readme files).
- SHA-256: `21c16b5cdbdc7d42ce0c0c23a944bf6a5bfceb89cae6fca810021bb7cae28f0a`.
- Code rollback: `dist/pepselect-coa-archive-0.7.8-rollback.zip`, rebuilt from tracked base `a7b5d01`.
- Rollback SHA-256: `69dd4402f9ca9f9e523110f4e3078c5082232f93bc69299876e84b61264ff29d`.
- Packages exclude tests, previews, repository metadata and internal documentation. ZIP integrity checked. No stored laboratory, customer, order or payment data migration.

Five focused PHP contracts passed: refinement rendering, logo URL, evidence/current mapping, historical navigation, NAD QR correction. Five JavaScript suites passed: lightbox, product links, sitemaps, redirects and integration. Plugin entry-point syntax and version consistency passed.

## Deployment state

Not installed yet. Both in-app and Chrome WordPress sessions require login; Chrome was left open for Paulo to sign in and complete the human-verification check.

MyKinsta is authenticated. Verified Pep Select / Live environment and manual backup list, newest to oldest. All five slots occupied. Oldest bottom backup is **Before Shipping Restrictions 0.4.2 live - 2026-09-04**, created September 4, 2026, 5:08 PM. Newer September 5, 6 and 8 backups remain present.

Automatic approval review rejected selecting deletion of that oldest backup because deployment approval does not explicitly authorize irreversible removal of rollback data. No backup was deleted. Explicit approval is required to remove that exact oldest backup and create the named pre-release backup before installation.

Next: after login and backup approval, recheck current version and manual backup order; create `Before COA mobile report 0.7.9 - 2026-09-08`; verify it completes; install the exact hashed package; clear relevant caches; inspect current/past/note reports, logo URL field, mobile layout, certificate viewer, verified product links and existing QR redirects. Do not restore the full database for a code rollback without accounting for orders since backup.
