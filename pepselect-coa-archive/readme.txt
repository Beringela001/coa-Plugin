=== Pep Select COA Archive ===
Contributors: pepselect
Tags: certificate of analysis, laboratory testing, quality documentation
Requires at least: 6.5
Tested up to: 6.8
Requires PHP: 8.1
Stable tag: 0.7.8
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Public compound testing histories with progressive vendor-vetting administration and transparent approved and failed reports.

== Description ==

Version 0.4.0 is the stable Pep Select COA Archive release. It promotes the verified release candidate without changing approved public/admin design, Elementor placement, commerce behavior, or stored COA data. It includes the production archive, Vetting History, Full Reports, exact Product ID integration, operational admin workflows, staged validation, CSV assistance, responsive presentation, and launch hardening developed across the 0.4.0 prerelease series.

Authorized users receive active-stage counters, a timezone-aware overdue count, and up to ten urgency-sorted Vendor Vetting, Waiting on Vendor, Submitted to Laboratory, or Verification in Progress records. Every row uses the linked Compound Display Name and exact COA Test edit permission. Complete, Approved historical, Failed, unrelated, and non-editable records are excluded.

The admin-only query requests IDs, primes test and linked Compound caches, and performs no writes. Results are built fresh per Dashboard request, so no additional cache invalidation mechanism is introduced. The dedicated responsive stylesheet loads only on the main Dashboard; no JavaScript, inline editing, frontend change, Elementor change, product carousel change, email, cron, QR code, or commerce behavior is added.

The carousel now uses one shared three/two/one cards-per-view property at desktop, tablet, and mobile breakpoints. At 767px and below, the viewport spans flexible full-width grid tracks, every slide is 100% and non-shrinking, cards use natural height, and the circular 44px controls sit beneath the card without compressing it. ResizeObserver, resize, orientation change, and Elementor initialization recalculate the actual viewport and clamp navigation state. Desktop styling and the approved Current/Incoming/Previous hierarchy are unchanged.

The existing ACF-backed Elementor Button is filtered in place. Current or newest eligible Approved/Complete reports display View Latest COA and link to the exact full report. Incoming-only products display View Vetting Status and link to Vetting History. With no eligible record the existing button renders no content, and stale saved URLs are ignored. No duplicate button or Elementor template edit is introduced.

The first eligible Approved/Complete report is the explicit Current report or, when none is marked current, the newest report labeled Latest Report. One active Incoming record may follow, prioritized by Verification in Progress, Submitted to Laboratory, Waiting on Vendor, then Vendor Vetting. Previous approved records fill the remaining positions. Failed, draft, private, unpublished, superseded, abandoned, inactive-compound, and wrong-compound/strength records never become product cards.

The carousel receives at most four cards and displays three on desktop, two on tablet, and one on mobile. Current uses a pale-green primary treatment, Incoming uses neutral blue without purity or passing claims, and Previous uses muted green-gray. Current/Previous link to exact full reports; Incoming links only to Vetting History. Native swipe, circular controls, responsive end states, visible focus, reduced motion, and no autoplay remain unchanged. Dedicated assets load only after the shortcode produces cards on a single-product request.

No new Elementor work is required. Upload and replace the plugin, confirm activation, clear Kinsta and browser caches, and reload the product page. The installed shortcode and existing button remain in place.

Catalog cards use CSS Grid stretching and vertical flex layout so their unchanged View All Reports footers align at the bottom. Mobile returns to natural one-column height. No fake content, fixed card height, or JavaScript measurement is used.

Public order is: current approved Complete, Verification in Progress, Submitted to Laboratory, Waiting on Vendor, Vendor Vetting, then no active current public record. Current approved releases outrank replacement batches; otherwise the most advanced public incoming stage wins. Failed and non-current historical approvals do not create Completed priority. Display Order, Display Name, and post ID provide stable ordering inside each group. Search retains the same order and counts.

One batched public-test index supplies eligibility, batch matching, and priority before pagination. Sorting does not mutate Compound or COA Test data. Existing record lifecycle invalidation remains active, and priority scope is included in the archive cache key.

Product SKU and Product ID now share the sidebar's first row; Connection Status and Create/Connect actions each receive a safe full-width row with wrapping and overflow protection. Existing matching, nonce, permission, and synchronization behavior is unchanged.

Vendor Vetting and Waiting on Vendor do not require unavailable batch identity values. Submitted to Laboratory preserves its established validation. Verification in Progress requires Batch Number, Exact Batch Vial Photo, Cap Color, and Crimp Color without requiring final results or documents. Complete keeps strict Approved/Failed rules. The optional Batch Identity Photos gallery remains optional throughout.

Early-stage tests use their linked Compound Display Name. Verification and Complete records with a batch use `{Compound Display Name} — Batch {Batch Number}` regardless of Pending, Approved, or Failed outcome. Batch and Compound Display Name changes update visible titles without rewriting existing published slugs. The existing one-record CSV preview/apply workflow triggers the same formatter on normal save and retains all CSV headers.

The WooCommerce product editor includes an opt-in COA Archive panel with COA Display Name and confirmed strength fields. COA Archive > Product Matching lists products first, ranks SKU searches ahead of ID/title matches, supports deliberate existing-compound connections, reports duplicate/missing/changed states, and provides safe bulk review. Product deletion or WooCommerce deactivation never deletes compounds or historical documentation.

Product images are fallback media only. Public priority is Batch Vial Photo, COA Test Featured Image, connected WooCommerce product image, Compound image, then the neutral local placeholder.

The certificate lightbox now moves its existing single root under document.body so isolated theme/report contexts, the footer, admin bar, and floating widgets cannot paint above it after scrolling. It covers 100vw by 100vh/100dvh at an isolated high stacking level and restores the exact scroll position, inline overflow/padding styles, temporary class state, and launch focus on close.

Archive cards preserve their approved structure and status behavior while sourcing their image from the latest approved completed report: Batch Vial Photo, COA Test Featured Image, related Compound image, then the bundled neutral vial. Previous Reports carousel behavior is unchanged; its controls now use fixed equal dimensions and non-shrinking circular geometry.

The installable archive is entry-inspected and temporary-extraction verified with one top-level `pepselect-coa-archive/` folder and the activation path `pepselect-coa-archive/pepselect-coa-archive.php`.

The exact Batch Vial Photo is optional through laboratory submission and required during Verification in Progress and Complete. An optional ordered Batch Identity Photos gallery provides supporting views. Published legacy reports remain public and receive a non-destructive admin warning until an exact image is added.

Report-specific views fall back from the exact batch image to the COA Test Featured Image, Compound image, then a bundled neutral placeholder. The redesigned responsive report follows the approved three-part hero, compact real-value metrics, truthful Full-QC strip and table, separate certificate and identity galleries, and dark final-document panel. Optional Fentanyl data never defaults to Pass.

Private access/verification metadata and internal product relationship fields remain excluded from public REST output. Existing workflow privacy, archive grouping, failed transparency, search, CSV import, PDF/gallery behavior, design settings, capabilities, and non-destructive uninstall remain intact. The product carousel adds no QR codes and does not modify prices, stock, inventory, orders, checkout, or shipping.

== Installation ==

1. Install the packaged ZIP or upload `pepselect-coa-archive`.
2. Activate the plugin.
3. Open COA Archive > Add New COA Test and select the operational stage.
4. Review public workflow wording under COA Archive > Design & Copy.
5. Visit `/testing/` after publishing an eligible record.

== Changelog ==

= 0.4.0 =
* Promoted the verified COA-7 release candidate to the stable 0.4.0 release without adding features or changing approved public/admin behavior.
* Finalized the production-ready COA Archive, Vetting History, Full Reports, exact Product ID matching, and WooCommerce product-page integration established throughout the prerelease series.
* Finalized the Dashboard Workflow Center, staged validation, CSV import assistance, responsive behavior, stable metadata, operator guidance, upgrade/rollback documentation, and 62-check release-hardening contract established throughout the prerelease series.
* Preserved all Compounds, COA Tests, product relationships, settings, attachments, capabilities, public routes, Elementor placement, and commerce behavior.

= 0.4.0-rc.1 =
* Prepared the first release candidate from the verified beta.23 source without adding features or changing approved public/admin behavior.
* Added an explicit release-hardening static suite covering upgrade safety, privacy, Product ID isolation, truthful reporting, permissions, asset scoping, limits, and package-source integrity.
* Added beginner-friendly staging installation, smoke-test, manual-QA, cache-refresh, and rollback instructions to README.md.
* Corrected two corrupted punctuation sequences in developer documentation; runtime output and stored data were not affected.

= 0.4.0-beta.23 =
* Replaced narrow one-field-per-row stacking with compact Compound/Stage, Expected COA/Timing, and Batch/Edit rows inside the existing 620px widget container query.
* Removed the 782px viewport fallback so the compact layout responds only to the actual Dashboard widget width.
* Preserved accessible table headings, word-boundary wrapping, counters, workflow selection, timing, sorting, links, public pages, Elementor, and commerce behavior.

= 0.4.0-beta.22 =
* Corrected Dashboard word wrapping and Action geometry with purpose-sized columns plus a component-width stacked layout, preserving existing counters, record selection, timing, sorting, and destinations.
* Added composable COA Test workflow, outcome, compound, laboratory, and timing filters; shared site-timezone Due Soon/Overdue classification; restrained Expected COA badges; and predictable chronological date/compound-name sorting.
* Added a capability-protected, read-only Workflow Requirements metabox sourced from active validation conditions, with explicit Complete, Missing, Not required yet, and Optional states and clearer field/stage validation errors.
* Added exact Active/Featured explanations and finalized the three-row WooCommerce COA sidebar layout without changing visibility, Product Matching, relationships, or write behavior.
* Kept unsafe COA workflow Quick Edit/bulk transitions absent, scoped every new asset to its relevant admin screen, and preserved public pages, Elementor, real COA data, and commerce systems.

= 0.4.0-beta.21 =
* Added the standard WordPress Dashboard COA Workflow Center for users with edit_ps_coas, with per-record mapped edit checks and independently gated footer actions.
* Added active-stage counters, WordPress-timezone overdue logic, whole-calendar-day warnings, deterministic urgency sorting, a ten-row limit, next expected summary, and an actionable empty state.
* Added an IDs-only active workflow query with batched post/meta cache priming and no mutations or secondary result cache.
* Added Dashboard-only responsive CSS plus focused PHP/static coverage for the 79 COA-5C acceptance checks while preserving every public, Elementor, carousel, Product Matching, form, CSV, and commerce surface.

= 0.4.0-beta.20 =
* Fixed the mobile squeeze at its root by spanning the carousel viewport across flexible full-width grid tracks and enforcing one full-width, non-shrinking, natural-height slide at 767px and below.
* Unified exact desktop/tablet/mobile card counts at 3/2/1 and added viewport ResizeObserver, resize, orientation, Elementor-init, stale-style cleanup, and index-clamping behavior without changing the approved desktop cards.
* Automated the existing ACF-backed Elementor top COA button from the canonical Product ID relationship, with exact current/latest report, Incoming Vetting History, and no-record hidden behavior.
* Excluded failed and non-public records, preserved all unrelated Elementor buttons and the shortcode, and added no duplicate button, template edit, QR code, pricing, stock, inventory, order, checkout, or shipping change.

= 0.4.0-beta.19 =
* Added Current/Latest, one Incoming, and Previous server-side card roles with a strict four-card order and limit.
* Added advanced-stage Incoming selection, future-date/modified/ID tie-breakers, exact stage copy, privacy-safe fields, and Vetting History-only destinations.
* Added restrained green, neutral-blue, and muted green-gray role surfaces without changing approved card dimensions or carousel behavior.
* Excluded Failed and failed-category records from product cards while preserving existing Vetting History transparency and all non-public visibility rules.
* Preserved the shortcode, Elementor placement, three/two/one responsive behavior, route-scoped assets, product page, pricing, stock, inventory, orders, checkout, shipping, and QR-free behavior.

= 0.4.0-beta.18 =
* Added `[pepselect_product_coa_carousel]` with exact WooCommerce Product ID relationship resolution and no title/SKU fallback.
* Added deterministic latest-six Approved/Complete selection, truthful Fully Vetted/QC Passed/Report Published projection, two-decimal purity display, and failed-category exclusion.
* Added dedicated accessible product-card templates plus dependency-free three/two/one-card responsive navigation, native swipe, resize handling, and reduced-motion support.
* Loaded dedicated assets only after valid single-product output and documented exact Elementor placement without modifying the template or existing View Latest COA button.
* Preserved product layout, archive/history/report designs, pricing, inventory, stock, orders, checkout, shipping, and QR-free behavior.

= 0.4.0-beta.17 =
* Aligned unchanged archive-card footers with catalog-scoped CSS Grid stretching and flex growth on desktop/tablet, while restoring natural height on mobile.
* Added batched public workflow indexing and stable Completed, Verification, Submitted, Waiting, Vendor, and no-active-record ordering before pagination.
* Preserved Display Order within status groups, with Display Name and post ID tie-breakers; failed/non-current/private records cannot elevate public order.
* Preserved search fields and counts while applying the same workflow order to filtered results.
* Reused lifecycle cache invalidation, added priority scope to cache keys, and verified the extracted installable package structure.

= 0.4.0-beta.16 =
* Reflowed the product-edit COA Archive facts and actions into safe two-column and full-width sidebar rows without changing Product Matching behavior.
* Added exact Active and Featured explanations while preserving existing public visibility rules and stored records.
* Made packaging identity optional during Vendor Vetting and Waiting on Vendor, retained Submitted behavior, and preserved strict Verification/Complete requirements with an always-optional identity gallery.
* Maintained COA Test titles from scientific Compound Display Names and batches after normal ACF/CSV saves while preserving published slugs and URLs.
* Rebuilt and extraction-verified the installable archive with one top-level plugin folder and forward-slash ZIP paths.

= 0.4.0-beta.15 =
* Added canonical WooCommerce Product ID relationships with unique SKU snapshots, safe synchronization metadata, COA Display Name, confirmed strength, and non-destructive missing/inactive states.
* Added product and compound edit panels, product-primary Product Matching audit/actions, deliberate Connect Existing, idempotent Draft creation, duplicate/race protection, and filtered bulk operations.
* Inserted the WooCommerce product image into the approved fallback chain without replacing batch evidence or COA-owned compound media.
* Moved the existing certificate viewer under document.body, raised its isolated viewport stacking layer, and added exact scroll/style restoration with scrollbar compensation.
* Preserved all public COA designs and added no public product-page output, QR codes, pricing, inventory, order, checkout, or shipping behavior.

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
