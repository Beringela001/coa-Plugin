# Pep Select COA Archive

Version 0.4.0-alpha.2 is the COA-4A.1 routing fix. It makes the archive, compound-history, and report URLs direct virtual routes and removes the frontend self-redirect that caused `/testing/` to loop.

## Architecture

The bootstrap uses a small class loader. `Plugin` coordinates focused services for post types, fields, validation, administration, frontend routing, repositories, visibility, view models, templates, and upgrades. Activation/deactivation remain isolated. No external requests, telemetry, Elementor widgets, WooCommerce product output, or custom SQL are used.

## COA-4A public frontend foundation

The plugin owns three narrowly matched public contexts:

- `/testing/` — active compounds with approved published reports.
- `/testing/{compound-slug}/` — one compound's approved testing history.
- `/testing/{compound-slug}/{batch-slug}/` — one exact approved report under its related compound.

The registered query variables are `ps_coa_view`, `ps_compound_slug`, and `ps_batch_slug`. Route values are sanitized before repository lookups. Invalid, inactive, unpublished, mismatched, or ineligible records set WordPress's 404 state, send a 404 status and no-cache headers, and use the active theme's 404 template. Invalid records are never redirected to the archive, and canonical redirects are disabled only while a COA route is being resolved.

### Direct virtual route ownership

No WordPress page is required or created. The compound post type no longer registers a competing built-in archive at the same path. Each plugin rewrite populates `ps_coa_view` directly, and `template_include` selects the appropriate plugin or theme-override template in the same request. The plugin does not filter page content or use an Elementor page shell.

In 0.4.0-alpha.1, a request resolved by WordPress as the `ps_compound` archive could reach `Frontend_Router::protect_legacy_routes()` without `ps_coa_view`. That method issued a 301 from `/testing/` to the identical `/testing/` URL, creating an infinite loop before core `redirect_canonical` ran. Alpha.2 removes all frontend redirect calls from the router. Legacy core post-type URLs are blocked with a true 404 instead of redirected, while recognized virtual COA routes render directly.

### Visibility and query architecture

`Frontend_Visibility` is the single public eligibility policy. A compound must be published, active, and have at least one eligible test. A test must be a published `ps_coa_test`, have `coa_status=approved`, contain a batch number, valid test date, recognized laboratory, at least one tested vial, belong to a public compound, and not carry a defensive private marker. Pending, failed, archived, superseded, draft, private, trashed, or structurally incomplete tests never enter public view models.

`Compound_Repository` and `COA_Test_Repository` use `get_posts()`, core metadata APIs, and cache priming—not raw SQL. The archive is limited to 24 compounds per page. History separates one Latest Report from up to 20 Previous Reports per page. Tests are ordered current first, test date descending, then publish date descending. When no current approved test exists, the newest approved test is a display-only fallback; metadata is not rewritten. The latest report is removed from the previous collection.

Public view models use explicit allowlists. They never copy `internal_notes`, `internal_batch_id`, private metadata, edit links, or unpublished records. Laboratory values are normalized once (`ils-labs` → ILS Labs, `janoshik` → Janoshik Analytical, `mz-biotech` → MZ Biolabs, and `other` → the saved other-lab name). Result status data retains the stored meaning; Reported is not Pass and Not Tested is not Fail.

### Templates and shortcodes

Plugin fallbacks are:

- `templates/archive-testing.php`
- `templates/single-compound-history.php`
- `templates/single-coa-report.php`
- `templates/partials/archive-compound-item.php`
- `templates/partials/report-summary.php`
- `templates/partials/report-status.php`

Child themes and parent themes can override any file at `pepselect-coa/{same-relative-path}`. Child theme overrides win, followed by parent theme, then plugin fallback.

Available shortcodes are `[pepselect_coa_archive]`, `[pepselect_compound_history compound="slug"]` (or `id="123"`), and `[pepselect_coa_report compound_id="123" test_id="456"]`. Compound and report parameters pass through the same public repositories and cannot expose ineligible records.

The stylesheet `assets/css/coa-frontend.css` contains structural, strongly prefixed rules only. It loads on COA routes or pages containing these shortcodes, with a safe late-render fallback for shortcode widgets. It sets no global fonts and includes no JavaScript, modal, carousel, or AJAX behavior.

### Canonicals, media, and caching

Each valid context provides a self canonical URL. Canonical metadata never performs an HTTP redirect. Filters integrate with Yoast, Rank Math, and SEOPress; fallback markup is suppressed when those plugins or SureRank are detected so another SEO owner is not duplicated. Core `redirect_canonical` is disabled only when a strongly prefixed COA route is recognized, preventing WordPress from misinterpreting the virtual query while leaving normal pages, posts, and WooCommerce routes unchanged.

Direct Lab Report URL is the primary external action. Lab Verification URL is the fallback when no direct URL exists, and PDF remains a separate download. External actions use a new browsing context with `noopener noreferrer`. A PDF URL is returned only for an existing inherited `application/pdf` attachment. Gallery items must be valid image attachments, retain their saved order, use the WordPress `large` image size, lazy loading, and saved or fallback alt text. PDFs and full-size images are never loaded in archive/history queries.

COA-4A deliberately uses WordPress's post, metadata, and attachment object caches with explicit cache priming. No cross-request transient is introduced, avoiding stale visibility state and eliminating a custom invalidation surface. Persistent object-cache installations benefit through core APIs automatically.

### Upgrade behavior

The installed version is stored in `pepselect_coa_archive_version`. Activation registers structures, flushes rewrite rules once, and records the version. Existing installations run `Upgrade::maybe_upgrade()` after post types and routes register; it flushes once only when the stored version differs, then records `0.4.0-alpha.2`. This installs the direct virtual-route rules once and remains idempotent on later requests.

## Compound fields

The PHP-registered ACF group `group_ps_compound_details` targets only `ps_compound` and uses these stable keys:

| Field | ACF key | Rules |
|---|---|---|
| Display Name | `field_ps_compound_display_name` | Required, 120 characters |
| Base Compound Name | `field_ps_compound_name` | Required, 100 characters |
| Short Name | `field_ps_compound_short_name` | Optional, 40 characters |
| Strength Value | `field_ps_compound_strength_value` | Positive decimal |
| Strength Unit | `field_ps_compound_strength_unit` | `mg`, `mcg`, `g`, `mL`, `IU`, or `mg/mL` |
| Compound Category | `field_ps_compound_category` | Controlled optional selection |
| Related WooCommerce Product | `field_ps_compound_woocommerce_product_id` | Optional; registered only while products are available |
| Archive Description | `field_ps_compound_archive_description` | Optional, 500 characters |
| Compound Image | `field_ps_compound_image_id` | Attachment ID; not synchronized to featured image |
| Display Order | `field_ps_compound_display_order` | Integer, zero or greater |
| Active | `field_ps_compound_is_active` | Defaults on |
| Featured | `field_ps_compound_is_featured` | Defaults off |
| Internal Notes | `field_ps_compound_internal_notes` | Administrative only |

ACF Pro is optional to plugin availability but required for the structured editor. If its registration API is missing, compounds remain accessible and stored while a scoped administrator notice explains the limitation. WooCommerce is also optional: its product selector is registered only when WooCommerce and the `product` post type are present. Temporarily disabling WooCommerce never deletes a saved product ID.

## Validation and record behavior

Values are normalized before ACF persistence. Required names, maximum lengths, positive strength, allowed units/categories, non-negative integer ordering, and valid product IDs are enforced through ACF validation. An exact case-insensitive base-name plus numeric strength plus unit duplicate is blocked, excluding the record currently being edited.

The WordPress title remains canonical. After ACF saves a compound, an empty title is initialized once from Display Name. Existing non-empty titles and manually managed slugs are never overwritten. WordPress continues to own slug generation and collision handling.

## Compound administration

The compound list shows Compound, Strength, Category, Related Product, Active, Featured, Display Order, and Date. Strength, Display Order, Active, and Featured are sortable. Active, Featured, and Category filters are restricted to the main `ps_compound` administration query. Default ordering is Display Order ascending and title ascending, including older records without structured metadata.

## REST strategy

Safe compound metadata is registered through WordPress core `register_post_meta`; no custom route is created. REST writes require permission to edit the post. Display/base/short names, strength, unit, category, archive description, image ID, ordering, flags, and optional product ID are exposed on the existing `ps_compound` endpoint. ACF's group-level REST output is disabled to prevent duplication. `internal_notes` is deliberately not registered in the REST schema and therefore is never publicly returned. Core WordPress post-status permissions continue to protect unpublished compounds.

## Lifecycle and backward compatibility

Activation registers post types and rewrites, grants Administrator capabilities idempotently, flushes rewrites once, and records the installed version. Versioned upgrades flush only after a version change. Deactivation only flushes rewrites. Uninstall deletes nothing. Existing records remain editable; no record is published, deleted, or rewritten automatically.

## Installation, testing, and packaging

Upload `pepselect-coa-archive.zip` in WordPress or copy the folder into `wp-content/plugins`. For automated tests, install the WordPress PHPUnit test library and PHPUnit, set `WP_TESTS_DIR` if needed, and run `phpunit -c phpunit.xml.dist`. Run `php -l` against every PHP file. Package the folder so `pepselect-coa-archive/` is the single ZIP root.

## Manual QA checklist

1. Activate with ACF Pro active and open COA Archive → Add New Compound.
2. Confirm every documented field appears; create Retatrutide 30mg.
3. Save with a blank title; confirm it becomes Retatrutide 30mg and the slug becomes `retatrutide-30mg`.
4. Confirm Strength shows 30mg and Active/Featured render correctly in the list.
5. Confirm category filtering and display-order sorting.
6. Link a WooCommerce product and confirm the admin-list edit link.
7. Disable WooCommerce; confirm no fatal, a scoped notice, and preserved product metadata.
8. Disable ACF Pro; confirm no fatal, the dependency notice, and accessible compound records.
9. Re-enable ACF Pro and confirm structured data remains.
10. Attempt the same base name, strength, and unit; confirm the duplicate is blocked.
11. Request a compound through REST; confirm safe metadata appears and `internal_notes` does not.
12. Deactivate/reactivate; confirm compounds remain intact.
13. Confirm `ps_coa_test` remains registered and unchanged.

### WooCommerce product-selector QA

1. Activate WooCommerce.
2. Confirm at least one WooCommerce product exists.
3. Open COA Archive → Add New Compound.
4. Confirm Related WooCommerce Product appears between Compound Category and Archive Description.
5. Search for and select a WooCommerce product.
6. Save the compound.
7. Refresh the edit page.
8. Confirm the selected product remains selected.
9. Temporarily disable WooCommerce.
10. Confirm the COA plugin remains active and existing compound data remains intact.
11. Re-enable WooCommerce.
12. Confirm the product selector returns and the saved relationship remains selected.

## Deferred work

COA-4B final visual cards, polished responsive design, frontend interactions, modal gallery, and PDF viewer are not implemented. COA-5 WooCommerce product-page cards and all Elementor widgets are also deferred.

## COA-3 test management

The PHP-defined `group_ps_coa_test_details` group uses stable `field_ps_coa_test_*` keys under Test Identification, Sample Information, Test Results, Certificate Documents, and Notes tabs. Date validation accepts ACF's raw `Ymd` and normalized `Y-m-d` formats. ILS-oriented editable defaults cover appearance, endotoxin reporting/unit, heavy-metals summary, and sterility result.

ILS records receive `https://lab.ils-lab.com` only when the URL is empty. Bioburden and residual-solvents fields remain hidden from the form/REST schema; their historical metadata is intentionally retained.

In 0.3.2, `lab_report_url` returns as **Direct Lab Report URL** and is the primary future external report link. Certificate Documents are ordered COA Number, Direct Lab Report URL, Access Code, Lab Verification URL, Certificate Version, PDF, and page images. Verification fields remain optional fallbacks.

### Single-test CSV importer

The Import Test CSV panel appears only on authorized COA Test Add/Edit screens. It reads at most 1 MB locally in the browser, requires one header row and exactly one non-empty data row, previews changes, confirms replacements, and snapshots prior form values for Clear Imported Values. It never uploads the CSV, saves a draft, creates a post, publishes, or makes an external request. PDF and gallery values are intentionally excluded.

Supported columns are the structured COA fields documented above, excluding `coa_pdf_id` and `coa_page_images`. Compound matching tries `compound_id`, then `compound_slug`, then `compound_display_name`; ambiguous or missing matches are never guessed. Dates accept `YYYY-MM-DD`, `YYYYMMDD`, or `MM/DD/YYYY`. Booleans accept `1/0`, `true/false`, `yes/no`, and `on/off`. Status/lab labels are normalized case-insensitively.

### COA-3.2 importer QA

1. Open COA Archive → Add New Test, choose a one-row CSV, and click Preview CSV.
2. Review values/warnings, click Apply CSV to Form, and confirm no post was created or saved.
3. Review imported values, upload PDF/page images manually, and confirm Direct Lab Report URL populated.
4. Publish manually, refresh, and confirm persistence.
5. Confirm two-row CSVs, invalid dates, and invalid purity are rejected.
6. Confirm replacements require approval and Clear Imported Values restores pre-import values.
7. Confirm the COA Test list remains readable and columns can still be hidden through Screen Options.

### COA-4A.1 routing QA

1. Activate version 0.4.0-alpha.2.
2. Visit `/testing/`.
3. Confirm it loads with HTTP 200.
4. Confirm there is no `ERR_TOO_MANY_REDIRECTS`.
5. Visit `/testing` without the trailing slash.
6. Confirm at most one redirect occurs.
7. Visit a valid compound route.
8. Confirm it loads without redirecting repeatedly.
9. Visit a valid batch route.
10. Confirm it loads without redirecting repeatedly.
11. Visit an invalid compound route.
12. Confirm a true 404.
13. Visit an invalid batch route.
14. Confirm a true 404.
15. Visit a valid batch under the wrong compound.
16. Confirm a true 404.
17. Visit the homepage.
18. Visit the shop.
19. Visit a product page.
20. Visit the cart.
21. Visit checkout.
22. Confirm unrelated routes are unaffected.
23. Confirm `wp-admin` remains accessible.
24. Confirm existing compound and COA records remain intact.
25. Confirm the CSV importer still works.
26. Confirm no final frontend styling was added.

Validation requires a valid compound, batch, date, laboratory, and at least one vial tested. It validates controlled choices, numeric ranges, content bounds, URLs, PDF/image attachments, other-lab names, and exact compound/batch duplicates. Approved records require a PDF and at least one page image and cannot contain a failed result status. Scientific status is never inferred from numeric values.

When a valid test is saved as Current, every other test for that compound is set non-current without deletion or status changes. Other compounds are unaffected. Unchecking Current does not select a replacement. A blank initial title becomes `{Compound Display Name} — Batch {Batch Number}`; manual titles are preserved.

The COA Tests list includes compound, batch, test date, lab, purity, vials, overall status, current state, PDF availability, and date. Compound, status, current, laboratory, and test-year filters are scoped to this list. Safe structured metadata is registered on the existing REST endpoint. `internal_notes` and `internal_batch_id` are excluded; WordPress protects unpublished records.

ACF Pro is required only for structured editing: if unavailable, the post type and stored data remain accessible and a scoped notice appears. WooCommerce and Elementor are not required for COA Test management. Attachments are referenced by ID and are never deleted automatically by deactivation or uninstall.

### COA-3 manual QA

1. Open COA Archive → Add New Test and confirm no large block editor appears.
2. Confirm COA Test Details appears near the title; select Retatrutide 30mg.
3. Enter batch `RT30-0726-A`, a valid test date, ILS Labs, 3 vials tested, and 99.79 purity.
4. Mark identity, endotoxin, heavy-metals, and sterility results Pass.
5. Upload a PDF and two ordered page images; add an ILS verification URL.
6. Set Approved and Current, publish, refresh, and confirm persistence and list columns.
7. Create a second test for the compound, mark it Current, and confirm the first is no longer Current.
8. Confirm an exact duplicate batch, purity over 100, zero vials, and Approved with a failed result are blocked.
9. Confirm filters and sorting work.
10. Deactivate/reactivate and confirm tests and attachments remain intact.

### COA-3.1 ILS refinement QA

1. Open Add New COA Test.
2. Select a Test Date using the calendar and confirm it saves without “Enter a valid date.”
3. Select Date Received using the calendar.
4. Confirm Sample Appearance defaults to White lyophilized powder.
5. Confirm Endotoxin Unit defaults to EU/mL and Endotoxin Status defaults to Reported.
6. Confirm the heavy-metals summary is prefilled and Sterility Result is No Growth.
7. Confirm bioburden and residual-solvents fields are absent.
8. Confirm COA Number and Access Code appear.
9. Select ILS Labs and confirm an empty verification URL becomes `https://lab.ils-lab.com` after saving.
10. Confirm Lab Report URL is absent.
11. Confirm PDF and page-image upload fields still work.
12. Save, refresh, and confirm all entered and manually replaced values persist.
