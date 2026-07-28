# Pep Select COA Archive — Developer Handoff

## Snapshot

- Current stable version: `0.4.0`
- Stable-release commit: `5ab62eb0387956ac7999d44a74c642d10f490fc8`
- Plugin folder: `pepselect-coa-archive/`
- Public routes:
  - `/testing/`
  - `/testing/{compound-slug}/`
  - `/testing/{compound-slug}/{batch-slug}/`
- Canonical data records:
  - `ps_compound`: one scientific compound **and strength** per record
  - `ps_coa_test`: one batch/laboratory report or active vetting record
- Expected production stack: WordPress, ACF Pro, WooCommerce, Elementor/Elementor Pro, Hello Elementor, Kinsta, pretty permalinks

Read `README.md` for the release checklist and test matrix. This document is about the decisions, operational habits, and project history that are not obvious from class names.

## Roadmap after 0.4.0

There is no approved, version-numbered post-0.4 specification in Git. The original project roadmap named PDF/media support, WooCommerce, Elementor widgets, inventory relationships, search/filtering, CSV import/export, laboratory verification, analytics, and API endpoints. Version 0.4 implemented most of the public archive, media, search, workflow, and WooCommerce display work, but not all of the original integration ambitions.

Treat the following as the recommended priority order derived from that recorded intent. Confirm scope with the product owner before starting a milestone.

### 0.5 — Data operations and laboratory integration

Highest priority because data entry is still the most manual and error-prone part of the system.

1. Replace or complement the browser-only, one-record CSV helper with a server-side import/export service.
   - Support dry-run/preview, explicit confirmation, idempotency, per-row errors, audit logs, and rollback.
   - Add named mapping profiles rather than weakening the canonical field schema to match every lab export.
   - Preserve the normal validation service as the final authority.
2. Add a first-class ILS Labs adapter.
   - Normalize the actual ILS export format into the plugin schema.
   - Keep original files and imported values traceable.
   - Do not infer successful results from marketing labels or visual PDF content.
3. Formalize laboratory verification.
   - Distinguish the generic ILS portal, the batch-specific lab report URL, accession/reference data, and any access/verification code.
   - Any external API/download work needs timeouts, authentication, redaction, rate limits, and an administrator-controlled retry path.
4. Define supported API endpoints for controlled integrations.
   - Reuse record-scoped capabilities and the existing sanitization/validation rules.
   - Do not expose internal notes, access codes, or workflow-only metadata in public REST responses.

### 0.6 — Native editor/site integrations

1. Add real Elementor widgets only where they reduce operational friction.
   - Archive, Vetting History, Full Report, and product-card widgets should consume plugin view models; Elementor must not become the data source.
   - Keep the current shortcodes as backward-compatible fallbacks.
2. Improve WooCommerce operational integration without changing commerce behavior by default.
   - Possible scope: release/inventory relationships, admin warnings, and documented readiness state.
   - Do not automatically alter price, stock, product status, orders, checkout, shipping, or customer notifications.
3. Add explicit compatibility coverage for the active theme, Elementor breakpoints, and common SEO/caching plugins.

### 0.7 — Reporting and data-quality operations

1. Data-quality dashboard: duplicates, missing evidence, stale expected dates, broken links, and Product ID/SKU drift.
2. Operational analytics: turnaround time by workflow stage/laboratory, pass/fail trends, and evidence completeness.
3. Scheduled checks only after notification ownership and false-positive handling are designed. Do not add cron/email casually.
4. Exportable audit/reporting views for operations; keep public claims separate from internal analytics.

### 1.0 and later — Platform hardening

1. A supported staff role and capability-management policy. Version 0.4 grants capabilities to Administrators only.
2. A documented migration framework with resumable/idempotent jobs and rollback strategy.
3. Optional destructive uninstall/cleanup behind an explicit administrator opt-in. Current uninstall intentionally deletes nothing.
4. CI with supported WordPress/PHP/WooCommerce/ACF/Elementor matrices, PHP lint, PHPUnit, WordPress Coding Standards, browser tests, and package extraction tests.
5. Translation, accessibility, multisite, observability, and long-term support policy.

`WEB-1` is a separate website/theme project. Do not put general header, footer, catalog, or Elementor-site redesign work into this plugin merely because the plugin supplies COA content.

## Open bugs and known limitations

### Open, verified issues

1. **The live header COA link is still database-managed outside this repository.**
   - Elementor Theme Builder header template ID: `73`
   - ElementsKit/WordPress menu item ID: `514`
   - Visible label: `COAs`
   - Observed destination: `/coas/`
   - Required destination: `/testing/`
   - This needs a WordPress menu edit. Do not add a plugin redirect or global JavaScript click handler.
2. **Two README references still say RC after the stable promotion.**
   - The operator-document link points to `#manual-release-candidate-qa-checklist`, while the current heading is `Manual stable-release QA checklist`.
   - The first smoke check says to confirm “the RC” is active.
   - These are documentation defects only; runtime and package metadata are `0.4.0`.

No other release-blocking runtime defect was open when `0.4.0` was cut. Do not interpret that as proof that every real-data edge case has been browser-tested.

### Known limitations that are intentional in 0.4

- The CSV importer accepts exactly one header row and one data row, is client-side only, and applies values to the current ACF form. It does not save, publish, upload media, or provide server-side batch import/export.
- PDF and image fields are always manual uploads. The plugin does not OCR a COA, rasterize PDF pages, or fetch attachments from a laboratory portal.
- ACF Pro is required for structured admin editing. If ACF is unavailable, the plugin remains active and stored data remains intact, but the structured edit UI is unavailable.
- WooCommerce is optional at runtime. If it is unavailable, saved product relationships remain stored but matching/synchronization is disabled.
- Deactivation and uninstall are deliberately non-destructive. Posts, meta, attachments, options, relationships, and capabilities remain.
- Published legacy Verification/Complete records without an exact Batch Vial Photo remain public. They show an admin warning and can remain untouched; a material edit or stage change requires the photo.
- Legacy `archived`/`superseded` outcomes and retired workflow/color values are preserved read-only rather than rewritten.
- The generic ILS verification URL is only `https://lab.ils-lab.com`. It is not proof of a batch-specific record. The saved `lab_report_url` is the batch-specific public destination.
- Public and commerce regression coverage still needs a real WordPress database and authenticated staging session. Source tests cannot prove cart, checkout, stock notifier, Kinsta cache, real attachment, or real menu behavior.
- Previous history is capped at 10 records, product cards at 4, archive pages at 24 compounds, and Dashboard rows at 10. These are intentional presentation/query limits.

## Design decisions and constraints

### Why the plugin renders its own frontend

The COA archive is a regulated-looking documentation surface whose truth rules must be consistent across the archive, history, report, and product pages. Elementor is useful for placement and the site shell, but it is not a safe source of truth for batch status, privacy, result wording, or relationships.

The plugin therefore owns:

- route resolution and visibility;
- repository queries and classification;
- truthful view models;
- archive/history/report templates and scoped assets;
- canonical URLs and route-specific 404 behavior.

Templates still call the active theme's `get_header()` and `get_footer()`, and themes can override files under `pepselect-coa/`. Shortcodes allow embedding inside Elementor, but they render the same plugin templates/view models. This gives the site design flexibility without duplicating scientific/business rules in Elementor JSON.

There was a real canonical redirect loop during the first `/testing/` implementation. The current router uses dedicated query variables, disables `redirect_canonical` only for resolved COA routes, returns explicit 200/404 state, and blocks legacy CPT archive/single URLs. Do not simplify this back to ordinary CPT permalinks without reproducing all route tests.

### Admin workflow reflects operations, not WordPress publishing

Three concepts are deliberately separate:

1. WordPress post status controls publication/visibility.
2. Workflow stage describes where the physical batch/testing process is.
3. COA status is the final outcome: Pending, Approved, or Failed.

The stages are:

1. Vendor Vetting
2. Waiting on Vendor
3. Submitted to Laboratory
4. Verification in Progress
5. Complete

This separation came from the real process: staff need to track a prospective/replacement batch before a laboratory report exists, but the public site must not invent a batch number, lab, purity, or pass claim. The progressive form hides fields until they are operationally meaningful. Server validation—not JavaScript—enforces the final state.

Failed records remain in Vetting History for transparency but cannot be Current and never appear in the WooCommerce product carousel. “Fully Vetted”/Full-QC wording requires all seven documented successful categories; partial or missing evidence stays partial/neutral.

### Compatibility scars worth preserving

- ACF field groups must register on `acf/init`; REST meta registration belongs on core `init`. Registering definitions without the lifecycle hook produced empty edit screens.
- Both post types use mapped custom capabilities. A previous capability registration mistake blocked Administrators from the edit screens.
- `ps_compound` intentionally does not support the block editor; the ACF Compound Details panel is the content editor.
- The plugin must activate without ACF, WooCommerce, or Elementor. Optional integrations should fail closed and preserve data.
- WooCommerce may load later than the plugin bootstrap. Avoid one-time dependency assumptions made too early in the request.
- The certificate lightbox is moved under `document.body` because Elementor, the footer, admin bar, and floating widgets created stacking contexts above a nested overlay.
- The product carousel's one-card mobile layout was broken by grid-track constraints, not just card CSS. Preserve its full-width track, resize/orientation recalculation, native scrolling, and no-autoplay behavior.
- Public CSS and JavaScript are route/shortcode scoped. Admin assets are screen scoped. Do not globally enqueue them to work around theme issues.
- Windows ZIP creation previously produced flattened filenames containing backslashes. Build archives with explicit POSIX entry names and always extract/hash-check them before delivery.

### Theme and Elementor boundaries

- The plugin does not own the site header or footer navigation.
- The product carousel is inserted through `[pepselect_product_coa_carousel]`; it does not replace WooCommerce templates.
- The top product COA button is an existing Elementor Button using the ACF-backed `view_latest_co` value. The plugin only filters that exact value/output to select the current report, incoming history, or hide the button.
- Product pages with no unambiguous eligible record should show no COA output. Empty output is safer than borrowing a similarly named product's report.
- Theme overrides are supported, but override templates must preserve the view-model truth rules, privacy boundaries, and URL semantics.

## Actual data-entry workflow

### A. Prepare the product and compound

1. In the WooCommerce product editor, enable **Include in COA Archive**.
2. Confirm the product has one unique SKU.
3. Enter a reviewed **COA Display Name** when the storefront name is not the scientific name. The built-in special mapping recognizes `GLP-3 R` as Retatrutide, but do not rely on title parsing for new naming conventions.
4. Enter confirmed Strength and Strength Unit. A value parsed from a title/SKU is only a suggestion; it is not silently persisted.
5. Use **Create and Connect** or deliberately connect an existing Compound.
6. Review the created Draft Compound, image, display name, strength/unit, Active state, and Product ID relationship. Publish and activate the Compound only when it should be eligible for public COA pages.

There must be one Compound for each scientific compound/strength combination. Retatrutide 10 mg and Retatrutide 30 mg are different Compound records even if the storefront naming is similar.

### B. Start the COA Test before a report exists

1. Open **COA Archive → Add New COA Test**.
2. Select the exact related Compound first.
3. Start at **Vendor Vetting**, with COA Status **Pending**.
   - Record vendor/internal notes only.
   - Do not guess a physical batch, laboratory, test date, or result.
4. Move to **Waiting on Vendor** when a real batch is expected.
   - Claimed content, unit, vial counts, known packaging details, and expected date may be entered.
   - Cap, crimp, batch number, and exact vial photo are not required yet.
5. Move to **Submitted to Laboratory** when the physical sample ships.
   - Current validation requires expected COA date plus cap and crimp colors.
   - Select the lab and enter accession/pending URL when known.
   - Batch/lab details at early stages are deliberately restricted in public output.
6. Publish the Pending record only if it should appear publicly as an Incoming report. Draft/private records remain admin-only.

### C. Track testing

1. Move to **Verification in Progress** when testing is underway.
2. Before saving this stage, provide:
   - Batch Number
   - Testing Laboratory
   - Expected COA Date
   - Cap Color and Crimp Color, including the matching Other text when applicable
   - Batch Vial Photo showing the exact tested vial
3. Add a pending laboratory URL if one exists.
4. Enable **Partial Results Available** only when the lab has supplied real partial data. This unlocks result fields; it does not make the record Approved or Full-QC.

### D. Enter the final laboratory report

1. Keep the record Pending while entering/reviewing evidence.
2. Use the one-row CSV importer for scalar fields if a prepared CSV exists:
   - Choose file.
   - Preview every row/value.
   - Resolve invalid or ambiguous Compound matching.
   - Confirm any replacement of existing values.
   - Apply to the form.
3. Manually upload/enter what CSV cannot supply:
   - Original COA PDF
   - ordered Certificate Page Images
   - exact Batch Vial Photo and optional Batch Identity Photos
   - final Lab Report URL
   - any laboratory logo
4. Compare the form against the PDF. Do not treat importer success as scientific review.
5. Move Workflow Stage to **Complete** only after final evidence is available.

### E. Approve, fail, and publish

For **Approved**:

- Workflow must be Complete.
- Test Date and Testing Laboratory are required.
- Vials Tested must be a whole number of at least 1.
- A valid PDF, at least one certificate page image, and a valid Lab Report URL are required.
- No recorded result category may be Failed.
- ILS reports must satisfy the exact Fentanyl rule described below.

For **Failed**:

- Workflow must be Complete.
- A Release Decision Note is required.
- The record cannot be Current.
- Preserve truthful failed category data; do not convert it to neutral data merely to save.

To make an Approved report Current:

1. Publish the COA Test.
2. Set **Current COA**.
3. Save. The service clears the Current flag from other tests of the same Compound.
4. Confirm the linked Compound is published and Active.
5. Verify the archive, Vetting History, Full Report, top product button, and product carousel.

The service synchronizes the admin title from Compound Display Name and, once a real advanced-stage batch exists, uses `{Display Name} — Batch {Batch Number}`. It preserves an existing published slug so editing a title does not silently break public URLs.

## ILS Labs and import lessons

### What the importer actually accepts

- Maximum file size: 1 MB.
- Exactly two non-empty rows: one header plus one data row.
- Headers must be lowercase internal field names and must be unique.
- A UTF-8 BOM is stripped.
- Quoted commas and doubled quotes are supported.
- Accepted date forms are `YYYYMMDD`, `YYYY-MM-DD`, and `MM/DD/YYYY`; values are normalized to ACF's `YYYYMMDD` storage form.
- Boolean values accept `1/0`, `true/false`, `yes/no`, and `on/off`.
- Spaces and underscores in statuses/stages normalize to hyphens.
- Retired stages map as follows:
  - `sample-received` → `submitted-to-lab`
  - `coa-pending` → `in-testing`

Compound resolution is attempted in this order:

1. `compound_id`
2. `compound_slug`
3. `compound_display_name`

Matching is exact and case-insensitive. If the supplied value matches zero or multiple Compounds, staff must select the Compound manually. Never add fuzzy title matching here; similar compound names and different strengths make it unsafe.

### Laboratory normalization

- `ILS Labs`/`ILS Laboratories` normalize to `ils-labs`.
- Known Janoshik and MZ naming variants normalize to their stored choices.
- An unknown laboratory becomes `other`, with the original text placed in Other Laboratory Name.
- Selecting ILS fills the generic ILS portal only when the verification URL is blank. It never overwrites a manual URL.

### Fentanyl is stricter than the other result categories

The ILS Full-QC format led to several iterations here. Keep these exact rules unless the real laboratory format changes and the change is documented:

- Allowed Fentanyl status values: Pass, Fail, Not Tested.
- Pass derives the result `Not detected`.
- Fail derives the result `Detected`.
- Approved ILS reports require all of:
  - status `pass`
  - result `Not detected`
  - method `Immunoassay`
  - specification `Immunoassay, 50 ng/mL cutoff`
- The importer should include both `fentanyl_status` and `fentanyl_result`; when both are present, preview derives the canonical result from status. A result-only column may preserve non-canonical wording and then fail normal validation.
- Phrases such as `ND`, `N/D`, and “no fentanyl detected” are conceptually equivalent, but the stored Approved value is still the canonical `Not detected`.

Other category statuses can include Reported, Pending, Not Tested, and Not Applicable. These are not equivalent to Pass. The frontend intentionally renders neutral/reporting language instead of a green pass state.

### Media and PDF quirks

The columns `coa_pdf_id`, `coa_page_images`, `batch_vial_photo`, and `batch_identity_photos` are ignored by CSV and marked for manual upload. This is deliberate: attachment IDs are site-specific and unsafe to import from an external spreadsheet.

ILS PDFs often contain a summary, chromatogram, heavy-metals table, sterility, signatures, access code, and vial image in one document. Version 0.4 does not parse it. Staff must:

- transcribe/import only values actually reported;
- upload the untouched original PDF;
- upload ordered page images for the public lightbox;
- record the exact tested vial separately;
- keep access/verification codes private unless a future approved integration defines otherwise.

Heavy-metals detail is currently stored as a summary string rather than per-analyte structured rows. Preserve the lab wording accurately; do not claim analytes that the supplied report did not test.

## Product matching lessons

- WooCommerce Product ID is the permanent relationship and public isolation key.
- SKU is required to create/connect and is saved as an audit snapshot, but SKU is not the public lookup key. SKUs can change or collide.
- Product title is never a relationship fallback. Storefront names, scientific names, and strength variants made title matching unsafe.
- One product must resolve to exactly one public Compound. Ambiguity returns no product COA output.
- Create/Connect requires a unique SKU, reviewed scientific name, and confirmed structured strength.
- The creation lock prevents concurrent duplicate Draft Compounds.
- Synchronization is intentionally narrow: Product ID, SKU snapshot, product status/URL/title snapshot, fallback image, confirmed strength/unit, sync time, and sync status.
- Synchronization must never modify COA results, batch identity, lab evidence, public descriptions, slugs, price, stock, orders, checkout, or shipping.
- Disconnecting or losing WooCommerce preserves the Compound and all history.
- Image fallback order is exact Batch Vial Photo → COA Test Featured Image → WooCommerce product image → Compound image → neutral local vial.

The product carousel order is Current/Latest, at most one Incoming, then Previous, with a total maximum of four. Failed reports and reports with a failed category are excluded even though failed history remains publicly transparent on the Compound history page.

## Before changing behavior

1. Reproduce against a staging clone with real records; do not use production records as fixtures.
2. Preserve the distinction between workflow stage, COA outcome, WordPress post status, Compound Active, and Current COA.
3. Add or update source contracts and WordPress integration tests.
4. Re-test archive, history, full report, product button/carousel, Dashboard, filters, validation, lightbox, mobile, and cache invalidation.
5. Verify no price, stock, cart, checkout, shipping, order, account, or notification behavior changed.
6. Build ZIPs from the actual `pepselect-coa-archive/` directory with one top-level folder and POSIX paths.
7. Extract the ZIP, compare file hashes with source, run tests from the extracted copy, verify CRC, and record SHA-256.
8. Never delete or rewrite legacy data merely to make current validation simpler.

