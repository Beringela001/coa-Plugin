# Changelog

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
