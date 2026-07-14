# Pep Select COA Archive

Version 0.4.0-beta.1 delivers the COA-4B public design and interaction layer for the existing server-rendered archive, compound-history, and individual-report routes.

## COA-4B scope

The plugin presents a calm, technical documentation experience at:

- `/testing/`
- `/testing/{compound-slug}/`
- `/testing/{compound-slug}/{batch-slug}/`

The design is original to Pep Select and remains independent of Elementor and WooCommerce. It inherits the active theme’s typography and uses only strongly scoped `.ps-coa-*` selectors. No product-page cards, Elementor widgets, external icon libraries, external requests, AJAX search, analytics, or frontend frameworks are included.

## Archive behavior

The archive provides a server-rendered GET search using `coa_search`. Input is sanitized and matched against each public compound’s `display_name`, `compound_name`, and `short_name`. Search and pagination remain in the URL.

Each compact compound card contains a restrained compound image or local vial fallback, display name, strength, latest approved test date, purity, laboratory, and the real approved-report count. Up to three newest approved batches appear as a preview; this is not a history limit. The “View all reports” action opens the compound’s complete approved history.

## Compound history

The history page separates one latest report from all previous approved reports. Existing current-test logic remains unchanged: an explicitly current approved report leads; otherwise the newest test date leads. The latest report is never duplicated in Previous Reports.

The latest card shows batch, date, laboratory, purity, net content, labeled content when available, vials tested when available, and identity/heavy-metals/sterility/endotoxin statuses. Previous reports use smaller cards and remain fully available through the existing 20-record pagination.

## Status semantics

The reusable status indicator always displays text and an original inline SVG symbol:

- `pass`: restrained green check and “Pass”
- `reported`: blue information indicator and “Reported”; never reinterpreted as Pass
- `pending`: amber indicator and “Pending”
- `not-tested`: neutral indicator and “Not Tested”
- `not-applicable`: neutral indicator and “Not Applicable”
- `fail`: restrained red indicator and “Fail”

Missing status values remain “Not Reported.” Numeric purity never creates an inferred pass result.

## Full report

The report page contains a breadcrumb, compact report header, summary metrics, full-QC results, documentation actions, certificate gallery, public technical notes when present, descriptive adjacent-report links, and back navigation.

The primary documentation action uses the exact validated `lab_report_url` and opens it with `target="_blank"` and `rel="noopener noreferrer"`. A valid WordPress PDF attachment is a secondary action. The public view model and templates do not expose `lab_verification_url` or `verification_code`, and no generic verification fallback is constructed. Admin metadata remains stored and unchanged.

Only validated PDF attachments are linked. Certificate images are validated attachments, retain saved order, and render as lazy-loaded WordPress `medium_large` responsive thumbnails. Full-resolution URLs are held for the lightbox and loaded only when a page is opened.

## Gallery and accessibility

The dependency-free lightbox loads only for a rendered report with certificate images. It supports:

- semantic button triggers and a labeled modal dialog
- close, previous, and next controls
- Escape, Left Arrow, and Right Arrow keys
- visible page count
- focus containment while open
- focus return to the triggering thumbnail
- touch-friendly controls
- reduced-motion preferences

Breadcrumbs, headings, focus rings, image alternatives, external-link notices, and readable status labels support keyboard and assistive-technology use. Color is never the only status cue.

## Responsive design system

The centered container is capped at 1220px with fluid horizontal padding. Archive cards use four columns only at wide widths, three on desktop, two on tablet, and one on mobile. Report metrics collapse from four columns to two and then one. Buttons expand on narrow screens, status groups wrap, and certificate thumbnails collapse without horizontal scrolling.

The frontend stylesheet exposes these safe variables:

- `--ps-coa-primary` (uses Elementor’s primary variable when available, with a plugin fallback)
- `--ps-coa-text`
- `--ps-coa-muted`
- `--ps-coa-border`
- `--ps-coa-surface`
- `--ps-coa-soft-surface`
- `--ps-coa-success`
- `--ps-coa-info`
- `--ps-coa-warning`
- `--ps-coa-danger`
- `--ps-coa-radius`
- `--ps-coa-shadow`

No global font family is set.

## Templates and asset loading

Theme overrides remain available under `theme/pepselect-coa/`. Child theme, parent theme, then plugin fallback order is unchanged. COA-4B adds modular partials for compound cards, latest reports, previous reports, status indicators, metrics, result panels, documentation, and the certificate gallery. Existing partial filenames remain as compatibility wrappers.

`assets/css/pepselect-coa-frontend.css` loads only on COA routes or pages containing a COA shortcode. `assets/js/pepselect-coa-lightbox.js` loads only when the resolved report context contains gallery images. Shortcode rendering follows the same rules.

## Manual QA checklist

### Main archive

1. Visit `/testing/`.
2. Confirm the header and search look intentional.
3. Confirm compound cards are compact.
4. Confirm there is no large empty area inside cards.
5. Confirm each card previews at most three recent batches.
6. Confirm the real total report count is shown.
7. Confirm View all reports opens the compound history.
8. Confirm mobile layout uses one card per row.
9. Confirm search works and preserves safe URLs.

### Compound history

10. Open Retatrutide 30mg.
11. Confirm Latest Report has stronger but restrained visual hierarchy.
12. Confirm purity does not visually overwhelm the card.
13. Confirm net content, lab, date, and batch display.
14. Confirm identity, heavy metals, sterility, and endotoxin display.
15. Confirm Endotoxin Reported is not shown as a green Pass.
16. Confirm previous reports use smaller cards.
17. Confirm all previous reports remain accessible through pagination.
18. Confirm latest is not duplicated.
19. Confirm the page works on mobile.

### Full report

20. Open a full batch report.
21. Confirm report header displays compound, batch, date, status, and lab.
22. Confirm summary metrics are clear.
23. Confirm full-QC statuses are easy to scan.
24. Confirm quantitative endotoxin result displays correctly.
25. Confirm View Lab Report opens the exact saved report URL.
26. Confirm Verify with Laboratory does not appear.
27. Confirm generic lab verification address does not appear.
28. Confirm Access Code does not appear.
29. Confirm Download Original PDF appears when available.
30. Confirm gallery thumbnails are in saved order.
31. Confirm clicking an image opens the lightbox.
32. Confirm Escape closes it.
33. Confirm keyboard previous/next navigation works.
34. Confirm focus returns correctly.
35. Confirm internal notes do not appear.
36. Confirm internal batch ID does not appear.
37. Confirm previous/next report navigation works.
38. Confirm mobile layout remains readable.

### Site safety

39. Visit the homepage.
40. Visit the shop.
41. Visit a product page.
42. Visit cart and checkout.
43. Confirm no COA styles or scripts affect unrelated pages.
44. Confirm no product-page COA cards were added.
45. Confirm wp-admin and the CSV importer still work.

## Deferred work

WooCommerce product-page COA cards and Elementor widgets are explicitly deferred. This release stops at COA-4B.
