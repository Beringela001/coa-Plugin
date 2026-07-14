=== Pep Select COA Archive ===
Contributors: pepselect
Tags: certificate of analysis, laboratory testing, quality documentation
Requires at least: 6.5
Tested up to: 6.8
Requires PHP: 8.1
Stable tag: 0.4.0-beta.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Original, configurable Pep Select compound testing history and certificate-of-analysis reports.

== Description ==

Version 0.4.0-beta.2 adds COA-4C frontend refinement and a WordPress Settings API Design & Copy screen.

Public numeric formatting removes floating-point artifacts without changing stored laboratory values. Full-QC eligibility requires published approved records with Pass statuses for purity, identity, heavy metals, and sterility plus Pass endotoxin or Reported endotoxin with a real result. Approved Reported endotoxin uses a success check while remaining visibly labeled Reported.

Archive cards use larger uncropped images, preferred compound names, separate strength, assurance labels, complete report counts, and no more than three batch previews. History headers are simpler. Certificate thumbnails and the fullscreen lightbox use contained images without cropping.

Administrators can configure scoped colors, local typography stacks, weights, radii, borders, primary/secondary/search controls, lightbox appearance, and selected public labels. Values are sanitized, request-cached, and output as `.ps-coa-app` variables only when COA CSS loads. Reset Defaults is nonce- and capability-protected and affects only design/copy settings.

Routes, visibility, data, importer, direct lab reports, PDFs, gallery order, theme overrides, shortcodes, and administration remain compatible. Access codes and generic verification URLs stay private. WooCommerce product-page cards, Elementor widgets, AJAX, external fonts, and external libraries are not included.

== Installation ==

1. Upload the `pepselect-coa-archive` folder or install the packaged ZIP.
2. Activate Pep Select COA Archive.
3. Open COA Archive → Design & Copy to keep defaults or adjust the presentation.
4. Visit `/testing/` after an active compound has an approved published COA test.

== Changelog ==

= 0.4.0-beta.2 =
* Added scientific number formatting and strict Full-QC Documented eligibility.
* Improved pass indicators and approved Reported-endotoxin presentation without changing stored status.
* Refined archive cards, history headers, report metrics, certificate thumbnails, and fullscreen lightbox.
* Added sanitized, request-cached Design & Copy settings and scoped CSS-variable output.
* Added capability- and nonce-protected reset behavior.
* Preserved public privacy, routes, visibility, existing data, importer, and backend behavior.

= 0.4.0-alpha.1 =
* Added the initial COA-4 public route, repository, visibility, view-model, shortcode, and template foundation.
