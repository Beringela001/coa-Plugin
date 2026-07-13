=== Pep Select COA Archive ===
Contributors: pepselect
Tags: coa, laboratory, compounds, archive
Requires at least: 6.5
Tested up to: 7.0.1
Requires PHP: 8.1
Stable tag: 0.3.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Structured compound and laboratory COA administration for Pep Select.

== Description ==

COA-3 adds a deterministic PHP-registered COA Test Details group, compound/batch duplicate protection, quantitative and pass/fail results, certificate documents, current-test enforcement, safe REST metadata, and scoped administration controls. ACF Pro is required for structured editing but not plugin activation. Private notes and internal batch IDs are excluded from REST.

No frontend, COA-test fields, PDF/gallery, Elementor, product-page output, or import/export functionality is included.

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
