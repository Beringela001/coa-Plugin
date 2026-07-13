=== Pep Select COA Archive ===
Contributors: pepselect
Tags: coa, laboratory, compounds, archive
Requires at least: 6.5
Tested up to: 7.0.1
Requires PHP: 8.1
Stable tag: 0.4.0-alpha.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Structured Pep Select COA administration with a secure public archive data layer.

== Description ==

COA-4A adds server-rendered `/testing/`, compound-history, and individual-report routes with centralized public visibility, minimal theme-safe templates, shortcodes, true 404 handling, validated document links, and theme overrides. ACF Pro, WooCommerce, and Elementor are not required for public rendering. Final COA-4B design and COA-5 product cards are not included.

== Installation ==

1. Upload `pepselect-coa-archive` to `/wp-content/plugins/` or upload its ZIP.
2. Activate Pep Select COA Archive.
3. With ACF Pro active, use COA Archive > Add New Compound.

== Frequently Asked Questions ==

= What happens if ACF Pro is disabled? =

The plugin remains active and records remain stored. Structured editing pauses and a scoped administrator notice appears.

= What happens if WooCommerce is disabled? =

Compound management continues. Product selection is hidden and saved product IDs are preserved.

= Are internal notes public in REST? =

No. Internal notes are not registered in the REST schema.

= What happens on deactivation or uninstall? =

No content is deleted. Deactivation flushes rewrite rules; uninstall intentionally preserves data and capabilities.

== Changelog ==

= 0.4.0-alpha.1 =
* Added COA-4A public routing, repositories, visibility rules, normalized view models, minimal templates, shortcodes, canonical handling, and versioned rewrite upgrades.

= 0.3.2 =
* Added a client-side single-test CSV importer, Direct Lab Report URL, and scoped list-table readability improvements.

= 0.3.1 =
* Accepted ACF raw dates and refined COA fields/defaults for ILS Full QC reports without deleting legacy metadata.

= 0.3.0 =
* Added complete COA Test administration, validation, current-test behavior, certificate media, REST metadata, and list controls.

= 0.2.5 =
* Fixed optional WooCommerce product-selector registration timing.

= 0.2.4 =
* Removed block-editor support from compounds so structured details remain near the top of the edit screen.

= 0.2.3 =
* Made ACF field-group and REST-meta bootstrap hooks explicit and idempotent.

= 0.2.2 =
* Registered the PHP-defined Compound Details group on ACF's native initialization hook.

= 0.2.1 =
* Corrected custom post-type meta-capability mapping and completed Administrator primitive capability grants.

= 0.2.0 =
* Added structured compound administration, validation, duplicate detection, optional product linking, REST metadata, and list controls.

= 0.1.0 =
* Initial COA-1 foundation.
