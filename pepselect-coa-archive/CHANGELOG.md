# Changelog

## 0.7.6 - 2026-08-26

- Added an authenticated `/pepselect-coa/v1/compound/connect` endpoint for Ops. It delegates to the existing duplicate-safe Product Matching service, so Ops can create or recover a WooCommerce product relationship without wp-admin.
- Kept publication and archive activation as a separate explicit write; the endpoint itself preserves the existing draft-first Product Matching behavior.

## 0.7.5 - 2026-08-19

- Reduced the purchase-area batch summary height and tightened its desktop and mobile spacing.
- Kept the record role (`Current Batch` or `Latest Report`) while removing `QC Passed` and `Fully Vetted` from the compact purchase-area card; the complete history cards retain their detailed status labels.
- Kept current, incoming, previous, and failed-record selection rules unchanged.

## 0.7.4 - 2026-08-19

- Added a compact, product-specific batch-documentation summary that reads the same current or incoming COA model as the full product testing history.
- Kept current, latest, incoming, previous, failed, missing-result, and product-strength distinctions unchanged; the compact summary renders nothing when the product has no eligible public record.
- Allowed the compact summary and full history carousel to appear on the same product page without copying report values into WooCommerce content.

## 0.7.3 - 2026-08-19

- Added an accessible information card directly below the Quality Archive hero that links visitors to the approved COA reading guide. The card uses archive-specific copy and responsive presentation without changing COA records, search, batch identity, product relationships, or commerce behavior.

## 0.7.2 - 2026-08-18

- Connected public Dataset creator markup to the existing Pep Select `/#organization` entity and added the real WordPress record publication time as `datePublished`. No license, business identity, laboratory result, or commerce data is invented or changed.

## 0.7.1 - 2026-08-18

- Added conditional links from the public Quality Archive compound cards and compound-history heroes to the exact published WooCommerce product already stored by the existing product-matching system. Unmatched or unpublished products render no product link; COA records, matching data, batch identity, and commerce behavior are unchanged.

## 0.7.0 - 2026-08-15

- Added a connected `Dataset` entity to completed public batch-report pages only when a public laboratory measurement exists. The markup uses the existing public view-model allowlist and never exposes private verification, internal, product relationship, customer, or OPS data.
- Replaced generic archive and report titles with a page-specific search and share title system for the Quality Archive, compound histories, and exact batch lab reports. Visible page copy, routes, canonicals, QR behavior, COA records, SKUs, and commerce remain unchanged.

## 0.6.4 - 2026-08-14

- Replaced the one verified legacy numeric Retatrutide 10mg compound slug with `/testing/retatrutide-10mg/` through a guarded, idempotent upgrade that checks the compound name, 10mg strength, and destination availability before writing. Added exact permanent redirects for the old compound and approved batch URLs. COA records, batch identity, product/SKU relationships, commerce behavior, OPS behavior, and visible copy are unchanged.

## 0.6.3 - 2026-08-14

- Added one exact permanent redirect for the printed NAD500 QR path `/testing/nad-500-mg/progress-1269/` to the approved batch report `/testing/nad-500-mg/nd50026205jp/`. No wildcard redirects, COA records, batch identities, product/SKU relationships, commerce behavior, OPS behavior, or visible copy were changed.

## 0.6.2 - 2026-08-13

- Added route-specific SEO titles, factual meta descriptions, and connected WebPage/CollectionPage plus breadcrumb schema for the plugin-owned COA archive, compound history, and batch report routes. The change uses existing public compound and batch identity only; visible page copy, COA records, SKU/product relationships, commerce, and OPS behavior are unchanged.

## 0.6.1 - 2026-08-13

- Corrected Yoast XML sitemap URLs for the COA archive. Public compounds and reports now use the plugin-owned `/testing/{compound}/` and `/testing/{compound}/{batch}/` routes instead of intentionally blocked raw custom-post-type permalinks. The `/testing/` archive is included once, while private, invalid, empty, or ambiguous records are excluded through the same visibility and route-resolution rules as the frontend. No COA data, batch identity, product relationship, commerce behavior, or public copy changes.

## 0.6.0 - 2026-08-05

**Breaking for REST integrations.** Direct `wp/v2` writes to `ps_coa_test` and `ps_compound` now return `403 pepselect_coa_write_route_required`. Reads are untouched. Minor rather than patch because an existing write path stops working the moment the plugin updates.

- New validated write endpoint under `pepselect-coa/v1`: `POST /coa-test`, `PATCH /coa-test/<id>`, `POST /compound`, `PATCH /compound/<id>`. It runs the same validators and the same post-save side effects as the admin form, so an integration no longer has to reimplement roughly sixty rules and keep them in sync. Failures return `400 pepselect_coa_invalid_record` with `data.errors[]` of `{field, message}` carrying the plugin's own text; advisory guidance returns as `warnings[]` on a success and never blocks.
- Core REST writes previously bypassed every `acf/validate_value` rule and every `acf/save_post` side effect, so a caller could publish a Failed report with no Release Decision Note, mark a failed batch as the Current COA, or approve a record whose sub-tests read Failed. All are now rejected. `pepselect_coa_allow_core_rest_write` reopens `wp/v2` for migrations.
- Both validators accept an injected value context, so the rules run outside an ACF form submission. Partial updates merge stored values under the submitted ones before validating: a `PATCH` carrying one field can never fail a record against its own stored, valid data. Creates receive the same `default_value` set the form applies.
- Post-save invariants now reach REST writes: `populate_empty_title` (a compound created without a title took the post ID as its slug, which is why one report sits at `/testing/961/`), `synchronize_title`, `clear_other_current_tests` and `apply_ils_verification_default`.
- Four constraints that existed only in the ACF field definitions moved into `validate()` so the endpoint has real parity: `fentanyl_status` may not be empty, `vials_tested` must be at least 1 whenever recorded, `batch_vial_photo` is restricted to JPG/PNG/WebP, and `compound_image_id` must be an image attachment — it was previously unvalidated entirely.
- The Batch Vial Photo legacy exemption is now an explicit closed list (`ND_R30_060326`, `TB10-6926`) resolved by batch number, replacing an inference that compared submitted against stored values. Under a partial request that inference could not tell an omitted field from an unchanged one, so a caller could have bought the exemption by omission. The admin form keeps the original behaviour.
- Continuous integration: PHPUnit now runs on GitHub Actions against PHP 8.1/WP 6.5.5 and PHP 8.3/WP latest.

Known gap: `DELETE /wp/v2/ps_coa_test/<id>` remains open. `rest_pre_insert_*` has no delete counterpart, and nothing deletes COA records today.

## 0.5.14 - 2026-08-04

- Report hero ("banner") card rebalanced after the 0.5.13 note card. Verified against the live Retatrutide 10 mg report at 1440px. The hero copy and the report-note card were capped at 55ch inside a 508px column, so the note wrapped to eight bold lines with ~110px of unused column beside it; both now use the full identity column, dropping the note to six lines and tightening it to .79rem/1.5 line-height with slightly roomier 12px 14px padding. The hero grid moves from `1.36fr / minmax(210px, .7fr) / 1fr` to `1.3fr / minmax(230px, .8fr) / 1fr`, and the vial image changes from a fixed 320px height to `height: auto; max-height: 100%`, so the vial fills the taller panel (249x309 to 283x351) instead of floating in ~190px of dead space. The stacked layout at 800px and below keeps the previous fixed image heights (320px, and 294px at 520px and below) unchanged. CSS only; markup, view models, and data untouched.

## 0.5.13 - 2026-08-04

- Report Notes now render as their own emphasised card in the report hero. The note previously sat as light grey body text inside the white hero card and read as an afterthought next to the certificate introduction. It is now a separate element (`ps-coa-hero-note-card` in `templates/partials/report-hero.php`) on a pale blue tinted surface with a rounded border, a navy left accent rule, a soft shadow, and bold darker text, so a batch-specific note such as the mislabelled-packaging explanation is clearly distinguished from the standard hero copy. Public Notes keep their existing quiet `ps-coa-hero-notes` treatment. Presentation only — no change to stored data, visibility gating, or the complete-stage rule that report notes appear only on completed records.

## 0.5.11 - 2026-07-28

- Fixed a batch vial photo leaking into compound-level images. The archive card grid (Frontend_View_Model::archive_compound) and the compound history header (Frontend_Router::build_compound) both overrode the compound's stock image with the latest/completed batch's `batch_vial_photo` whenever one existed, so the two compounds with uploaded vial photos showed a specific lot's vials instead of the studio product shot. Both now use the compound's own image only (compound_image_id / Woo product image / placeholder). The individual COA/report page still shows the batch vial photo — unchanged — so a customer can match the exact vial in hand.

## 0.5.10 - 2026-07-28

- Ops as single entry point: the ENTIRE ps_coa_test field vocabulary is now REST-writable so ops can populate a complete record and the WP record never needs hand-editing. Added the full class-coa-test-importer.php::field_map() set to the REST-safe list (internal_batch_id, date_received, lab_accession_number, partial_results_available, vial cap/crimp colours, claimed_content, content_unit, vials_submitted, net-content min/max/std-dev/variance, sample_appearance, purity/identity/endotoxin/heavy-metals/sterility/fentanyl status+method fields, lab_report_url, pending_lab_url, verification_code, lab_verification_url, certificate_version, vendor_status_note, release_decision_note, public_notes, report_notes, internal_notes). Added `coa_page_images` as an array meta (it was NOT in 0.5.9). vials_submitted typed integer; partial_results_available boolean.

## 0.5.9 - 2026-07-28

- COA PDF + vial photos from the ops app. Added attachment-ID metas to the REST-safe list so the ops app can link media it pushes to the WP media library on COA pass: `coa_pdf_id` and `batch_vial_photo` (single integer), and `batch_identity_photos` (array of integers, registered with an explicit array schema and the gallery sanitizer). The plugin requires `coa_pdf_id` for an approved completed report, so until now every ops-published record was incomplete by the plugin's own rules.

## 0.5.8 - 2026-07-28

- Fixes compounds vanishing from the archive after the ops app stamps them to in-testing. is_test_public() requires expected_coa_date (submitted-to-lab + in-testing), a whitelisted testing_lab (in-testing + complete), and vials_tested >= 1 (completed approved). Those fields were not REST-writable, so ops-app stamps left them empty and the record failed the public gate. Added to the REST-safe list: expected_coa_date, testing_lab, other_testing_lab, vials_tested.
- Laboratory list: added 'freedom-labs' (Freedom Diagnostics Testing) and removed 'mz-biotech' (unused). Updated the labs() choices, the frontend label map, and both is_test_public() lab whitelists so freedom-labs records display and pass the public gate.

## 0.5.7 - 2026-07-28

- Ops-app idempotency fix (§16.7). New authenticated route `GET /wp-json/pepselect-coa/v1/coa-test?batch_number=X` returns the existing ps_coa_test post id for an exact `batch_number` meta match. The ops app addresses records by batch number (the real vial↔certificate key), not by post slug — owner-entered records carry numeric/auto slugs, so the previous slug lookup always missed and created duplicate empty posts. No public-page or data change.

## 0.5.6 - 2026-07-28

- Ops-app integration (control.pepselect.co) for the Ops Spec §16 COA lifecycle. Additive; no change to public pages, templates, or the archive typeahead.
- Archive lone-failure fix (§16.6): the public archive now always includes a compound whose only record is a failure — a lone failure is exactly when transparency matters most. The `show_failed_only_compounds` design toggle no longer suppresses it.
- New authenticated resolver route `GET /wp-json/pepselect-coa/v1/compound?product_id=&sku=` (Compound_Resolver_Endpoint, mirroring the archive-search endpoint), returning the compound post id via the existing `compounds_for_product()` / `compounds_for_sku()`. Gated on `edit_posts`.
- Widened the `ps_coa_test` REST-safe meta list so the ops app can stamp `batch_number`, `is_current`, and the certificate scalars it owns (`coa_number`, `purity_percentage`, `average_net_content`, `test_date`) alongside the existing state trio. Sanitize is key-aware with a safe default; rich enrichment stays owner-only in wp-admin.

## 0.5.5 - 2026-07-18

- Compound results in the archive typeahead now show the batch number alongside the status when the current batch is in lab testing (Verification in Progress) or fully documented — stages where batch numbers are public and never change. Earlier stages continue to show no batch, matching the existing privacy gate in the view model. Additive model fields only; COA cards, templates, and data logic untouched.

## 0.5.4 - 2026-07-18

- Fixed the typeahead dropdown being clipped at the bottom edge of the hero card. The hero carried overflow: hidden, which cut the suggestion list off at the card frame; nothing inside the hero relies on that clipping, so the hero now allows overflow and stacks above the catalog band so open suggestions float cleanly over the heading and cards below. CSS-only change; COA cards, templates, and data logic untouched.

## 0.5.3 - 2026-07-18

- Fixed the archive typeahead not appearing on the /testing/ page. The search script was only enqueued on the embedded-shortcode path, not the main archive route, so live results never loaded while the server-side form still worked. The script now loads on the archive route.

## 0.5.2 - 2026-07-18

Archive typeahead search (M5 Beta 2). Compound cards, batch reports, and data logic remain untouched.

- Added a live typeahead to the archive hero search, matching the storefront header search. As you type, results appear beneath the field. A base query (for example a compound family) returns each strength variant with its public status label; an exact or partial public batch code appears as a batch result.
- Choosing a compound opens its history page; choosing a batch opens that batch report. Submitting the form still runs the normal server-side search, so the feature degrades gracefully.
- New public read-only REST route (pepselect-coa/v1/search) that searches only certificate-archive data: public compounds by name and strength, and public batch codes. Results are gated by the existing compound and test visibility rules, so nothing hidden is ever exposed.

## 0.5.1 - 2026-07-18

- The new archive copy and status descriptions now seed automatically on update through the plugin's existing copy-migration, so previously saved defaults are replaced without visiting Design Settings. Intentionally customized copy is preserved; only recognized prior defaults or empty fields are updated.
- Card-section heading: removed the redundant "On the record" eyebrow (it repeated the title) and restyled "The selection, on the record." with the site's serif and cyan italic accent on "on the record.".

## 0.5.0 - 2026-07-18

Archive landing page top section restyled to match the Pep Select coded site (M5 Beta 1). Compound cards, batch reports, and all data logic are untouched.

- Hero rebuilt in the site design language: navy gradient card, Georgia serif title with a cyan italic accent, cyan search button. New title "Every batch has a permanent address." and a rewritten, de-slopped introduction (both editable in Design Settings).
- Card-section heading changed from "Documented Compounds / Certificate archive" to "On the record / The selection, on the record.", restyled to the serif treatment.
- Reworded the four incoming-stage status descriptions (Design Settings copy only; workflow stage keys unchanged, so storefront status bands are unaffected): vetting vendor, waiting on vendor, submitted to laboratory, and verification in progress now read in plainer, calmer language.
