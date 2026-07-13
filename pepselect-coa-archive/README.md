# Pep Select COA Archive

Version 0.3.0 adds the COA-3 laboratory-test administration workflow while preserving the compound-management foundation.

## Architecture

The bootstrap uses a small class loader. `Plugin` coordinates focused services: `Post_Types`, `Rewrites`, `Capabilities`, `Dependencies`, `Compound_Fields`, `Compound_Validation`, and `Compound_Admin`. Activation/deactivation remain isolated. No frontend assets, external requests, telemetry, Elementor integration, or custom SQL are used.

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

Activation registers post types and rewrites, grants Administrator capabilities idempotently, and flushes rewrites once. Deactivation only flushes rewrites. Uninstall deletes nothing. Existing COA-1 compounds without field metadata remain editable and receive empty/default ACF controls; no record is published, deleted, or rewritten automatically. `ps_coa_test` is unchanged.

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

COA-4 frontend work—including public archives, history/detail pages, viewers, Elementor, product-page output, search, imports, and analytics—is not implemented.

## COA-3 test management

The PHP-defined `group_ps_coa_test_details` group contains 45 stable fields under Test Identification, Sample Information, Test Results, Certificate Documents, and Notes tabs. Its explicit keys use the `field_ps_coa_test_*` prefix and cover `compound_id`, batch/date/lab/status fields, sample quantities, standardized result statuses, certificate media/URLs, and public/report/internal notes. Records link to one compound and capture batch/date/lab data, manual scientific results, certificate PDF/page images, verification URLs, status, and current-record state. The `ps_coa_test` block editor is hidden; existing `post_content` remains untouched.

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
