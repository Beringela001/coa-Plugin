# COA Archive 0.7.9 Live release

Paulo authorized deployment of the approved report refinements on September 8, 2026. Source is based on 0.7.8 with local refinement commits 34448bd and 322501e. This package also includes the preceding guarded historical/current navigation and detail-report evidence safeguards.

## Package

- Install: `dist/pepselect-coa-archive-0.7.9.zip` (113 runtime/readme files).
- SHA-256: `21c16b5cdbdc7d42ce0c0c23a944bf6a5bfceb89cae6fca810021bb7cae28f0a`.
- Code rollback: `dist/pepselect-coa-archive-0.7.8-rollback.zip`, rebuilt from tracked base `a7b5d01`.
- Rollback SHA-256: `69dd4402f9ca9f9e523110f4e3078c5082232f93bc69299876e84b61264ff29d`.
- Packages exclude tests, previews, repository metadata and internal documentation. ZIP integrity checked. No stored laboratory, customer, order or payment data migration.

Five focused PHP contracts passed: refinement rendering, logo URL, evidence/current mapping, historical navigation, NAD QR correction. Five JavaScript suites passed: lightbox, product links, sitemaps, redirects and integration. Plugin entry-point syntax and version consistency passed.

## Deployment and backup

Paulo signed in to WordPress and explicitly authorized backup deletion and plugin updates on his behalf. The initial automated-review backup block was resolved by that authorization.

Verified Pep Select / Live and manual backups newest-to-oldest. Removed only the bottom/oldest **Before Shipping Restrictions 0.4.2 live - 2026-09-04**, created September 4 at 5:08 PM. Other backups retained.

Created **Before COA mobile report 0.7.9 - 2026-09-08**, shown September 8 at 1:00 PM with Restore available, before submitting the package. Paulo completed installation; WordPress showed “Plugin updated successfully.” Plugin list independently confirms active 0.7.9 replacing 0.7.8.

Requested MyKinsta Clear all caches. Edge purge displayed propagation in progress; normal public report URLs subsequently served the new 0.7.9 assets and templates without cache-busting parameters.

## Live verification

- Current RT3026233GX report: Testing passed; three white metric cards; green purity value; green recorded-content tile; no category count; correct seven-category order; matching product and original lab/PDF links.
- Mobile 390px: no horizontal overflow, two-column cards, full headings and correct green/neutral states. Desktop 1440px captured using the actual site theme.
- Certificate disclosure opens; lightbox loads original image and focuses Close; close works.
- Historical ND_R30_060326 remains explicitly historical with unchanged self-canonical, correct current-report and product links.
- RT2026205JP customer note is inside the green outcome card, occurs once, and replaces generic prose. Original notice text preserved.
- WordPress edit screen exposes Laboratory Logo URL under Batch & Vial Identity. No new logo URL or laboratory value was saved during verification.
- Printed NAD typo URL still returns 301 to ND50026205JS.
- Public testing archive, matching product page and NAD destination each returned HTTP 200.

Live captures: `docs/coa-refinement/live-mobile-current.png`, `live-desktop-current.png`, `live-mobile-note.png`.

No commerce, order-attribution, customer or stored scientific data changes. Source and release records remain committed locally; public repository publication remains separate. For code rollback, reinstall the saved 0.7.8 runtime package and clear caches. Do not restore the full database without accounting for orders since backup.
