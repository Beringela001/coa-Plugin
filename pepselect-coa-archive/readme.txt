=== Pep Select COA Archive ===
Contributors: pepselect
Tags: certificate of analysis, laboratory testing, quality documentation
Requires at least: 6.5
Tested up to: 6.8
Requires PHP: 8.1
Stable tag: 0.4.0-beta.14
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Public compound testing histories with progressive vendor-vetting administration and transparent approved and failed reports.

== Description ==

Version 0.4.0-beta.14 redesigns the `/testing/` catalog around the approved compound cards with a navy technical hero, integrated server-rendered search, accurate result counts, a responsive three/two/one-column grid, and a contained no-results state. Search matches public compound display, base and short names, strength, and visible public batch numbers.

Archive cards preserve their approved structure and status behavior while sourcing their image from the latest approved completed report: Batch Vial Photo, COA Test Featured Image, related Compound image, then the bundled neutral vial. Previous Reports carousel behavior is unchanged; its controls now use fixed equal dimensions and non-shrinking circular geometry.

The installable archive is entry-inspected and temporary-extraction verified with one top-level `pepselect-coa-archive/` folder and the activation path `pepselect-coa-archive/pepselect-coa-archive.php`.

The exact Batch Vial Photo is optional through laboratory submission and required during Verification in Progress and Complete. An optional ordered Batch Identity Photos gallery provides supporting views. Published legacy reports remain public and receive a non-destructive admin warning until an exact image is added.

Report-specific views fall back from the exact batch image to the COA Test Featured Image, Compound image, then a bundled neutral placeholder. The redesigned responsive report follows the approved three-part hero, compact real-value metrics, truthful Full-QC strip and table, separate certificate and identity galleries, and dark final-document panel. Optional Fentanyl data never defaults to Pass.

Private access/verification metadata and internal fields remain excluded from public output. Existing workflow privacy, archive grouping, failed transparency, search, CSV import, PDF/gallery/lightbox, design settings, capabilities, and non-destructive uninstall remain intact. Product-page cards, QR codes, SKU matching, Elementor widgets, and external integrations are not included.

== Installation ==

1. Install the packaged ZIP or upload `pepselect-coa-archive`.
2. Activate the plugin.
3. Open COA Archive > Add New COA Test and select the operational stage.
4. Review public workflow wording under COA Archive > Design & Copy.
5. Visit `/testing/` after publishing an eligible record.

== Changelog ==

= 0.4.0-beta.14 =
* Rebuilt only the `/testing/` catalog surroundings with the approved navy hero, trust chips, integrated accessible search, result count, empty state, and responsive three/two/one-column grid.
* Extended server-rendered search to strength and visible public batch numbers without weakening visibility rules or requiring JavaScript.
* Preserved the approved card partial while switching its image data to the latest completed report’s safe four-step fallback chain.
* Prevented Previous Reports controls from shrinking by enforcing equal fixed dimensions, a square aspect ratio, and circular clipping.

= 0.4.0-beta.13 =
* Enlarged and strengthened the Previous Reports carousel controls with a clearer border, shadow, and hover state.
* Increased compact category-result text by about one pixel while preserving truncation and category-label hierarchy.

= 0.4.0-beta.12 =
* Rebuilt the compound Vetting History route with scoped, responsive mockup-matching templates.
* Added truthful fixed seven-category summaries, incoming empty states, and a non-destructive ten-report carousel.
* Added the optional Laboratory Logo field, reusable same-lab fallback, and bundled ILS Labs logo.

= 0.4.0-beta.11 =
* Preserved all seven QC positions with truthful full, partial, missing, and failed states.
* Reordered hero metadata and added exact Current/Past batch labels.
* Rebuilt certificate-page cards and repaired the scoped accessible fullscreen viewer.

= 0.4.0-beta.10 =
* Restored centered icon-above-label cells in the exact two-tier Full-QC bar.
* Limited Fentanyl Screen status to Pass, Fail, and Not Tested with canonical derived values.
* Added exact Immunoassay and 50 ng/mL cutoff output to the detailed results table.

= 0.4.0-beta.9 =
* Restore the pale-green Full-QC header and separate white, divided category row directly beneath Summary Metrics.
* Add dynamic rendered-category counts, saved-data detail copy, truthful per-category success styling, and responsive tablet/mobile layouts.
* Preserve the existing report hero, results table, certificate lightbox, archive/history routes, document panels, and source documentation panel.

= 0.4.0-beta.8 =
* Recompose the approved report into dense mockup-matched hero, measured-values, laboratory-data, document, and source-documentation panels.
* Add all five Fentanyl Screen fields, ILS approval validation, backward-compatible CSV mapping, and Fentanyl Free success representation.
* Preserve failed and incoming truthfulness, private metadata boundaries, responsive result cards, and the existing certificate lightbox.

= 0.4.0-beta.7 =
* Added exact Batch Vial Photo and optional ordered Batch Identity Photos fields.
* Enforced the exact-vial image at Verification in Progress and Complete, with a non-destructive legacy exemption and admin warning.
* Added the batch, Featured Image, Compound image, and local-placeholder fallback sequence to report-specific views.
* Rebuilt the full report into a responsive three-part hero, compact metrics, truthful Full-QC strip/table, separate document/photo galleries, and dark laboratory panel.
* Added optional Fentanyl fields without Pass defaults and kept private verification/internal metadata out of public output.
* Added scoped document-panel settings, 42 COA-4E coverage tests, and the complete 75-step manual QA checklist.

= 0.4.0-beta.6 =
* Added the COA-4D_Form_Rev five-stage progressive COA Test editor.
* Limited normal final status choices to Pending, Approved, and Failed while preserving legacy outcomes read-only.
* Normalized retired sample-received and coa-pending stages safely.
* Restored the native Featured Image metabox without Gutenberg or a competing image field.
* Added scoped stage-controller JavaScript/CSS, guidance, Partial Results Available, and stage-authoritative validation.
* Added the requested crimp/cap field keys with legacy custom-color fallbacks.
* Centralized public stage privacy for view models, templates, routes, REST metadata, archive cards, and partial results.
* Added tests and documentation for form scope, privacy, legacy handling, assets, and Featured Image behavior.

= 0.4.0-beta.5 =
* Added separate operational workflow tracking, expected dates, incoming/failed grouping, copy settings, and transparent failed reports.

= 0.4.0-beta.4 =
* Restored legacy approved reports, normalized archive search, and added versioned plugin-only archive caching.

= 0.4.0-beta.3 =
* Added initial incoming/failed presentation and stricter approved documentation validation.

= 0.4.0-beta.2 =
* Added scientific formatting, certificate-viewer refinements, and scoped Design & Copy settings.

= 0.4.0-alpha.1 =
* Added the initial COA-4 public route, repository, visibility, view-model, shortcode, and template foundation.
