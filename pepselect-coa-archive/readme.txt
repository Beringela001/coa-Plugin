=== Pep Select COA Archive ===
Contributors: pepselect
Tags: certificate of analysis, laboratory testing, quality documentation
Requires at least: 6.5
Tested up to: 6.8
Requires PHP: 8.1
Stable tag: 0.4.0-beta.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Original, configurable Pep Select compound testing history and certificate-of-analysis reports.

== Description ==

Version 0.4.0-beta.4 fixes the unsearched archive regression while preserving the beta.3 frontend and admin refinements.

Public numeric formatting removes floating-point artifacts without changing stored laboratory values. Full-QC eligibility requires published approved records with Pass statuses for purity, identity, heavy metals, and sterility plus Pass endotoxin or Reported endotoxin with a real result. Approved Reported endotoxin uses a success check while remaining visibly labeled Reported.

Archive cards use larger uncropped images, preferred compound names, separate strength, assurance labels, complete report counts, and no more than three batch previews. History headers are simpler. Certificate thumbnails and the fullscreen lightbox use contained images without cropping.

Administrators can configure scoped colors, local typography stacks, weights, radii, borders, primary/secondary/search controls, lightbox appearance, and selected public labels. Values are sanitized, request-cached, and output as `.ps-coa-app` variables only when COA CSS loads. Reset Defaults is nonce- and capability-protected and affects only design/copy settings.

Published incoming reports may show an expected COA date and public progress URL. Published failed reports remain inspectable and are clearly marked as not released for sale. Archived, superseded, draft, private, and explicitly private records remain excluded. Approved reports require their public lab URL, PDF, page image, test identity, lab, vial colors, and tested-vial count during ACF validation.

Routes, stored data, PDFs, gallery order, theme overrides, shortcodes, and administration remain compatible. Access codes and generic verification URLs stay private. WooCommerce product-page cards, Elementor widgets, AJAX, external fonts, and external libraries are not included.

== Installation ==

1. Upload the `pepselect-coa-archive` folder or install the packaged ZIP.
2. Activate Pep Select COA Archive.
3. Open COA Archive → Design & Copy to keep defaults or adjust the presentation.
4. Visit `/testing/` after an active compound has an approved published COA test.

== Changelog ==

= 0.4.0-beta.4 =
* Restored legacy approved reports and their compounds to the public archive when new vial-color metadata is absent.
* Normalized absent, empty, whitespace-only, and invalid archive search values as no search.
* Added post-title matching while preserving display-name, compound-name, and short-name search.
* Added versioned, plugin-only archive cache keys and targeted invalidation on upgrades and relevant record changes.
* Added regression coverage for default, searched, cleared, private, cached, compound-route, and report-route behavior.

= 0.4.0-beta.3 =
* Added public incoming-report and transparent failed-report history states.
* Added expected COA date, vial crimp/cap colors with conditional Other fields, and an in-progress lab URL.
* Required approved reports to include the exact public lab report URL and strengthened release-state validation.
* Tightened archive, history, report, certificate, status, and mobile presentation.
* Added linked helper descriptions and preview examples to Design & Copy settings.
* Kept archived, superseded, unpublished, and explicitly private reports out of public views.

= 0.4.0-beta.2 =
* Added scientific number formatting and strict Full-QC Documented eligibility.
* Improved pass indicators and approved Reported-endotoxin presentation without changing stored status.
* Refined archive cards, history headers, report metrics, certificate thumbnails, and fullscreen lightbox.
* Added sanitized, request-cached Design & Copy settings and scoped CSS-variable output.
* Added capability- and nonce-protected reset behavior.
* Preserved public privacy, routes, visibility, existing data, importer, and backend behavior.

= 0.4.0-alpha.1 =
* Added the initial COA-4 public route, repository, visibility, view-model, shortcode, and template foundation.
