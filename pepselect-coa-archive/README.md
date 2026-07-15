
# Pep Select COA Archive

Version 0.4.0-beta.14 is the **COA-4G Archive Catalog Redesign and Carousel Arrow Correction** release. It rebuilds only the `/testing/` catalog surroundings around the approved compound cards and makes the existing Previous Reports controls resistant to theme-driven compression.

## Archive catalog

The archive now uses a route-scoped navy technical hero, integrated accessible search, a live server-rendered “Showing X of Y compounds” count, a contained no-results state, and a responsive three/two/one-column grid. The approved compound-card partial and its wording, statuses, recent-batch limit, report counts, spacing, and destinations remain unchanged.

Core search works without JavaScript and preserves the sanitized query in the URL. It matches public compound display names, base names, short names, strength plus unit, and visible batch numbers from eligible public completed or in-testing reports. Draft, private, inactive, archived, and otherwise ineligible records remain excluded.

Archive-card images now follow the latest approved completed report, preferring its Batch Vial Photo, then its COA Test Featured Image, then the related Compound image, and finally the bundled neutral vial. Incoming reports and unrelated compounds cannot supply the card image.

Previous Reports controls remain behaviorally unchanged but now have fixed equal dimensions, an explicit square aspect ratio, non-shrinking flex constraints, circular clipping, centered glyphs, and the existing hover, focus, disabled, keyboard, and reduced-motion behavior.

## Package verification

The release archive is built from the `pepselect-coa-archive/` source directory, inspected entry by entry for forward-slash paths and a single top-level folder, then extracted to a temporary directory and compared with the source tree. The only valid activation path is `pepselect-coa-archive/pepselect-coa-archive.php`; nested duplicate plugin folders and bundled reference screenshots are rejected.

## Exact batch identity

COA Tests now include `batch_vial_photo` (`field_ps_coa_test_batch_vial_photo`), an Attachment-ID image of the exact tested vial, and the optional ordered `batch_identity_photos` gallery (`field_ps_coa_test_batch_identity_photos`). The main photo is optional during vendor vetting, waiting, and laboratory submission, then required at Verification in Progress and Complete. Server validation verifies that it is an image the current editor may use; the stage controller communicates the same rule immediately.

Published legacy Verification in Progress and Complete records without this new image remain public and retain their URLs. Their edit screen shows a warning. An unchanged legacy record may remain untouched, but a stage change or material report update requires the image. No migration deletes or hides existing records.

Report-specific image fallback order is: exact Batch Vial Photo, COA Test Featured Image, related Compound image, then the bundled neutral vial placeholder. The main archive compound card continues to represent the compound with its generic image. Images remain responsive, preserve aspect ratio, and below-the-fold galleries are lazy-loaded.

## Full report

The `/testing/{compound-slug}/{batch-slug}/` report uses modular, theme-overridable partials and the approved composition: breadcrumb; three-column report hero; one contained Measured Values panel; one contained Independent Laboratory Data panel; one contained Original Document panel; optional Batch Identity Photos; the dark Source Documentation panel; and subtle report navigation.

The Measured Values panel places four icon-led summary cards first and the Full-QC Testing Passed bar immediately beneath them. The restored bar uses a pale-green header and a separate white, divided category row. It includes only categories supported by meaningful saved public data, keeps the label `Fentanyl Screen`, and applies green success treatment only when the saved status and evidence support success. The detailed laboratory table remains unchanged, displays only saved methods/specifications/results, and becomes equivalent labeled result cards on mobile.

The hero uses the exact tested vial when saved, with fallback order: COA Test Featured Image, related Compound image, then the bundled neutral vial placeholder. Fallbacks receive only the subtle caption `Representative vial image`; the primary report introduction never becomes a legacy-warning paragraph. Approved, failed, and incoming reports keep distinct truthful language and styling.

Certificate pages preserve saved order and use the existing keyboard-accessible lightbox. The dark laboratory panel exposes only the exact saved final `lab_report_url` and a valid uploaded PDF. Public models and templates do not expose access/verification codes, generic verification URLs, internal batch IDs, internal notes, or laboratory street addresses. Methods, specifications, accreditation, results, and claims are never fabricated.

## Fentanyl Screen data and workflow

The Test Results section includes five stable fields: `fentanyl_status`, `fentanyl_result`, `fentanyl_method`, `fentanyl_specification`, and `fentanyl_notes`. Status offers only Pass, Fail, and Not Tested and defaults to Not Tested. The form keeps method/specification read-only at `Immunoassay` and `Immunoassay, 50 ng/mL cutoff`; the result is derived as `Not detected`, `Detected`, or blank from the selected status.

These fields remain unavailable during Vendor Vetting, Waiting on Vendor, and Submitted to Laboratory. They become editable during In Testing only when Partial Results Available is enabled, and are available at Complete. A completed approved ILS report requires Pass, or Reported with an explicitly successful saved result, plus result, method, and specification. A completed failed report may truthfully store Pass, Reported, or Fail without automatically changing the overall outcome.

## CSV example

The five Fentanyl columns are optional, so existing single-record CSV files remain compatible. A reviewable partial-results import may use:

```csv
compound_slug,batch_number,workflow_stage,coa_status,testing_lab,partial_results_available,fentanyl_status,fentanyl_result,fentanyl_method,fentanyl_specification,fentanyl_notes
retatrutide-30-mg,RT30-0726-B,in-testing,pending,ils-labs,1,pass,Not detected,Immunoassay,"Immunoassay, 50 ng/mL cutoff",Independent screen
```

The importer validates the dedicated status set, keeps blank status blank, normalizes common successful result phrases only when an explicit successful status and result are supplied, and always preserves preview/review before the normal WordPress save or publish action.

## Responsive behavior

- 1180â€“1240px centered report width; three-part hero and four metrics on desktop.
- Two-row/tablet hero, two-column metrics, and adaptive document/laboratory panels at 1024px and 768px.
- Identity â†’ vial â†’ outcome stacking, non-scrolling result cards, one-column document/photo galleries, and full-width actions at 480px, 390px, and 360px.
- Scoped active-theme typography and existing configurable colors/radii; new laboratory-panel color variables live in the existing Design & Copy screen.
- Logical headings, icon-plus-text statuses, focus visibility, meaningful alt text, external-link indication, dark-panel contrast, and reduced-motion compatibility.

## Manual visual QA — beta.11

For complete reports, confirm all seven QC positions remain visible. A complete seven-category success reads `Full-QC Testing Passed`; a successful partial panel reads `QC Testing Passed`, retains all seven positions, and shows `--` for untested categories. Confirm the hero metadata order is COA Reference, Report Date, Cap / Certificate Version, Batch Status, Crimp and that Batch Status reads exactly `Current` or `Past`.

For certificate pages, confirm two large, uncropped cards render per row on desktop and one per row on mobile. Open each page and verify the counter, disabled boundary navigation, arrow keys, Escape, backdrop close, focus restoration, focus containment, and scroll lock.

1. Open an approved report.
2. Confirm Summary Metrics appear first.
3. Confirm the Full-QC bar appears immediately beneath them.
4. Confirm Independent Laboratory Data appears immediately after the bar.
5. Compare the bar side by side with `Full_report_page_mockup.png`.
6. Confirm the pale-green top row matches.
7. Confirm the white category row matches.
8. Confirm the circular check icons match.
9. Confirm seven categories display when all seven contain saved data.
10. Confirm Fentanyl Screen displays.
11. Confirm the category count is correct.
12. Confirm no duplicate component exists.
13. Confirm desktop spacing matches the mockup.
14. Confirm the tablet layout remains usable.
15. Confirm the mobile layout has no horizontal scrolling.
