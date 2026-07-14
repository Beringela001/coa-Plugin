=== Pep Select COA Archive ===
Contributors: pepselect
Tags: certificate of analysis, laboratory testing, quality documentation
Requires at least: 6.5
Tested up to: 6.8
Requires PHP: 8.1
Stable tag: 0.4.0-beta.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Original, server-rendered Pep Select compound testing history and certificate-of-analysis reports.

== Description ==

Version 0.4.0-beta.1 adds the COA-4B public design system to the existing `/testing/`, compound-history, and individual-report routes.

The archive has sanitized GET search, compact compound cards, a three-batch newest-report preview, and real complete-history counts. Compound pages clearly separate the latest report from all paginated previous approved reports. Individual reports present metrics, full-QC statuses, direct laboratory documentation, valid PDFs, ordered certificate thumbnails, public notes, and accessible report navigation.

Status values remain scientifically literal: Reported is never presented as Pass, and missing data is not invented. The exact validated `lab_report_url` is the primary external action. Generic lab verification URLs and access codes remain stored for administration but are excluded from public view models and templates.

The vanilla JavaScript certificate viewer supports Escape, arrow keys, visible page count, focus containment, and focus return. CSS is strongly scoped, responsive, theme-typography friendly, and loaded only on COA routes or shortcode output. Gallery JavaScript loads only for reports with images.

Theme overrides remain available under `pepselect-coa/`. WooCommerce product-page cards, Elementor widgets, AJAX filtering, and frontend frameworks are not included.

== Installation ==

1. Upload the `pepselect-coa-archive` folder to `/wp-content/plugins/` or install the packaged ZIP.
2. Activate Pep Select COA Archive.
3. Visit Settings > Permalinks and save only if routes were not refreshed automatically.
4. Open `/testing/` after at least one active compound has an approved published COA test.

== Changelog ==

= 0.4.0-beta.1 =
* Added the original responsive Pep Select COA-4B archive, history, and full-report design.
* Added sanitized server-rendered compound search and three-batch card previews.
* Added modular status, metric, result, documentation, and gallery partials.
* Added an accessible dependency-free certificate lightbox that loads only when needed.
* Removed verification URLs and access codes from public report contexts while retaining admin metadata.
* Preserved all public visibility, current-test, relationship, routing, admin, importer, and attachment rules.

= 0.4.0-alpha.1 =
* Added the initial COA-4 public route, repository, visibility, view-model, shortcode, and template foundation.
