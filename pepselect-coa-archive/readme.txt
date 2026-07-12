=== Pep Select COA Archive ===
Contributors: pepselect
Tags: coa, laboratory, compounds, archive
Requires at least: 6.5
Tested up to: 7.0.1
Requires PHP: 8.1
Stable tag: 0.2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Structured compound administration for the Pep Select COA archive.

== Description ==

COA-2 adds a deterministic PHP-registered ACF Compound Details group, validation, duplicate protection, optional WooCommerce product linking, secure REST metadata, and compound-only admin columns, sorting, and filters. ACF Pro is required for structured editing but not for plugin activation or post access. WooCommerce is optional. Internal notes are excluded from REST.

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

= 0.2.0 =
* Added structured compound administration, validation, duplicate detection, optional product linking, REST metadata, and list controls.

= 0.1.0 =
* Initial COA-1 foundation.
