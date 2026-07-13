# Pep Select COA Archive

Version 0.2.4 is the COA-2 compound-management maintenance release for Pep Select's certificate-of-analysis archive. The compound post type now uses its structured ACF Archive Description instead of displaying the WordPress content editor; existing post content remains stored and untouched.

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

## Deferred work

COA-3 and later work—including test fields, batch data, PDFs, galleries, frontend archives/cards, Elementor, product-page output, search, imports, analytics, and laboratory verification—is not implemented.
