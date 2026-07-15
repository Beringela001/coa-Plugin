
# Pep Select COA Archive

Version 0.4.0-beta.22 is the **COA-5D Dashboard and Admin Workflow Polish** release. It improves the existing administrative workflow surfaces without changing public COA pages, product-page COA cards, Elementor, commerce behavior, or stored COA data.

## COA-5D dashboard and admin workflow polish

The Dashboard Workflow Center retains its existing counters, active-record selection, urgency sorting, dates, edit links, and footer actions. Its table now uses purpose-sized columns, word-boundary wrapping, protected Action geometry, and a component-width container query that changes narrow widgets into labeled stacked records without horizontal scrolling. Dashboard CSS remains scoped to `index.php`.

COA Tests retain the WordPress list table and gain composable Workflow Stage, COA Status, Compound, Laboratory, and Timing Status filters. Due Soon and Overdue use one read-only classifier shared with the Dashboard, based on valid Expected COA dates and WordPress site-local calendar days. Date sorting normalizes supported ACF `Ymd` and ISO fallback values, puts missing values predictably after valid dates, and does not make urgency the list default. List CSS loads only on the COA Test list.

COA Test Add/Edit screens now include a read-only **Workflow Requirements** metabox. It evaluates saved evidence against the same stage/status conditions as `COA_Test_Validation`, updates guidance immediately when the selected stage, status, or laboratory changes, reports Complete, Missing, Not required yet, or Optional in text and icons, and never duplicates or writes form fields. Validation messages name the exact field, stage, and reason; invalid transitions still fail through normal ACF validation without partial workflow writes. The panel stylesheet and lightweight controller load only on COA Test Add/Edit screens.

The current COA Test schema has no separate Report Type field, so beta.22 does not fabricate one or impose a new Full-QC requirement matrix. It reports the evidence actually enforced today: Approved documentation/date/laboratory requirements, Complete-stage Fentanyl method/specification rules, the stricter Approved ILS Fentanyl result, and the Failed Release Decision Note.

Compound Active and Featured instructions now include the exact public-eligibility and priority definitions. Featured still cannot override an inactive Compound. The WooCommerce sidebar keeps SKU and Product ID in row one, Connection Status across row two, and its action across row three with safe word wrapping. Product Matching logic, relationships, and write handlers are unchanged.

No COA Test Quick Edit or workflow bulk transition was added. Standard WordPress non-workflow operations remain available; bulk Approve, Complete, Verification in Progress, laboratory-result, batch, purity, and report-URL changes remain deliberately absent because they could bypass the existing field-specific validation matrix. Admin views read live post/meta state and add no secondary cache, so normal WordPress cache invalidation and the existing archive invalidation hooks remain authoritative.

Deployment requires only uploading and replacing the plugin ZIP, confirming activation, clearing Kinsta cache if needed, and reloading WordPress admin. No Elementor or public-page edit is required.

### COA-5D deployment-site manual QA

The local source workspace does not include a running WordPress/WooCommerce site, so the following deployment checks are intentionally documented for the installed beta.22 package rather than marked as locally executed:

1. Dashboard: check full-width counter/headings, one-line Action, visible Edit controls, word-boundary stage wrapping, then move the widget to a narrow column and verify labeled stacking, no horizontal scroll, unchanged counts/sorting, Due Soon, and Overdue results.
2. COA Tests: exercise Verification in Progress, Pending, Compound, Laboratory, Due Soon, Overdue, and combined filters; repeat with search and pagination; clear filters and confirm the full list returns.
3. Workflow Requirements: inspect Vendor Vetting and Waiting on Vendor; verify physical-batch fields are not required; safely move a test to Verification in Progress, satisfy listed fields, and confirm save; attempt Approved without Lab Report URL, confirm the specific validation block, add the URL, and confirm a valid save.
4. Compound controls: verify both complete Active/Featured descriptions; with Featured on and Active off, confirm public ineligibility; restore the original values.
5. WooCommerce product editor: verify SKU/Product ID row one, full-width Connection Status row two, full-width action row three, safe long-status wrapping, and idempotent handling of an existing connection.
6. Regression: inspect `/testing/`, Vetting History, a Full Report, certificate lightbox, desktop/mobile product COA cards, the existing top COA button, add to cart, and stock notifier. Confirm no Elementor, price, stock, inventory, order, checkout, or shipping change.

## COA-5C WordPress Dashboard Workflow Center

Administrators and other users with `edit_ps_coas` receive a standard **COA Workflow Center** widget on `wp_dashboard_setup`. Users without that capability receive neither the widget registration nor its output. Each displayed row is additionally protected by WordPress's mapped `edit_post` check for the exact COA Test. Footer actions are independently gated by `create_ps_coas`, `edit_ps_coas`, and `manage_ps_compounds`.

The widget queries only COA Tests with an active operational outcome (`pending`, including the supported beta-era operational status aliases), normalizes legacy workflow stages through the existing `COA_Workflow` model, and excludes Complete, Approved, Failed, unrelated, and non-editable records. Draft, private, future, pending, and published active records follow normal WordPress post permissions; inactive Compounds remain visible to authorized administrators because the widget is an operational—not public-visibility—surface. The query requests IDs only, primes test and linked-compound post/meta caches in batches, and performs no writes.

Counters show Vendor Vetting, Waiting on Vendor, Submitted, In Testing, and Overdue totals from the same authorized active source set. Overdue means a Pending Submitted-to-Laboratory or Verification-in-Progress record with a valid Expected COA date before the current calendar date in the configured WordPress site timezone. The day count is the whole difference between those two site-local midnight dates. Vendor and waiting stages, missing/invalid dates, Complete records, Approved history, and Failed records never become overdue.

Rows sort by overdue first (oldest expected date first), then Verification in Progress, Submitted to Laboratory, Waiting on Vendor, and Vendor Vetting. Within a non-overdue stage, dated records precede undated records, expected dates sort ascending, modified time sorts descending, and post ID descending supplies the final stable tie-breaker. Only the ten most urgent rows render; counters retain the full active total, and larger sets expose the existing View All COA Tests destination. The optional next-expected summary uses the nearest valid non-past date.

The compact table shows the linked Compound Display Name, normalized human stage, Expected COA date or em dash, saved Batch Number or em dash, and the exact WordPress Edit link. It adds no inline editing. The empty state offers Add New COA Test when permitted. A dedicated stylesheet loads only on `index.php`, wraps counters, stacks table cells at narrow admin widths, keeps badges/actions readable, and adds no JavaScript. Results are built fresh on each Dashboard request, so normal WordPress post/meta invalidation is sufficient and no new cache invalidation path is required.

## COA-5.2 mobile repair and existing-button automation

The mobile squeeze was caused by the carousel shell changing to two fixed 44px grid columns for the navigation row while the viewport also spanned those same columns. A one-card slide was therefore 100% of an approximately control-width viewport instead of 100% of the product section. The corrected shell keeps two flexible full-width tracks, lets the viewport span their complete width, and places the unchanged 44px circular controls at the centered inner edges below the viewport. Slides are three-per-view at 1024px and above, two at 768–1023px, and one at 767px and below. Mobile slides are explicitly full-width and non-shrinking, cards use natural height, and the viewport remains the source of scroll offsets.

The JavaScript reads the same `--ps-coa-cards-per-view` property as CSS, measures the actual viewport, clears stale sizing properties, clamps the active index, and recalculates after initial layout, ResizeObserver updates, window resize, orientation change, and Elementor frontend initialization. Native swipe, scroll snap, disabled states, per-instance state, reduced motion, circular controls, and the no-autoplay policy remain intact.

The existing Elementor Button widget remains in place and retains its classes, icon, typography, colors, hover treatment, and placement. Its existing ACF URL source is `view_latest_co`; the plugin filters that exact value and the exact ACF-backed Button output only. The canonical resolution is WooCommerce Product ID → exactly one active public Compound → explicit Current Approved/Complete report, newest eligible Approved/Complete fallback, or one public Incoming record. Approved reports keep the label **View Latest COA** and link to `/testing/{compound}/{batch}/`. Incoming-only products use **View Vetting Status** and `/testing/{compound}/`. Products with no eligible record render no button content, so stale manual URLs and dead links cannot survive. Failed, draft, private, unpublished, inactive, ambiguous, and other-strength records are never selected.

The resolver is computed from current public records on every render; it does not introduce a second destination cache. Existing Compound and COA Test save, ACF save, trash, untrash, and delete hooks continue to invalidate the plugin archive namespace, while normal WordPress meta/post cache invalidation makes Product Matching and workflow transitions visible without re-saving the Elementor template. Deployment requires only replacing the ZIP, activating, clearing Kinsta and browser caches, and reloading the product page. No Elementor edit is required.

## COA-5.1 product report hierarchy

`[pepselect_product_coa_carousel]` renders only on a valid published WooCommerce single-product request. For preview and focused testing, `[pepselect_product_coa_carousel product_id="123"]` may select another valid published product while remaining in single-product context. The shortcode resolves exactly one active public COA Compound through its canonical `woocommerce_product_id` relationship. Product-title and SKU similarity are never public fallbacks, so another strength or similarly named product cannot supply reports.

The repository performs one compound-scoped public-test query and creates three server-side roles. **Current** is the explicit current Approved/Complete report; when none exists, the newest eligible Approved/Complete report becomes **Latest Report** without being falsely called current. **Incoming** is at most one active Pending record, selected by Verification in Progress, Submitted to Laboratory, Waiting on Vendor, then Vendor Vetting. Within the same stage, the nearest valid future Expected Report Date wins, followed by modified date and post ID. Remaining approved records become **Previous Report**, newest first.

The exact order is Current/Latest, Incoming when available, then Previous, with a hard maximum of four cards. Incoming counts toward the limit. Failed reports and reports containing a recorded failed category never render or consume a position, while existing Vetting History transparency remains unchanged. Draft, private, unpublished, abandoned, superseded, inactive-compound, other-compound, and other-strength records remain outside public product-card eligibility.

Current/Latest keeps the compact documented information model and uses a restrained pale-green primary surface. Previous uses the same truthful result, purity, batch, date, laboratory, and full-report destination with a lower-emphasis green-gray surface. **Fully Vetted** still requires all seven documented categories and every explicit category status to pass; successful partial panels use **QC Passed**, and insufficient evidence remains neutral **Report Published**. Purity is never fabricated and is formatted to at most two decimal places.

Incoming uses a pale neutral-blue surface, the exact workflow label and stage sentence, and no purity, pass, QC Passed, Fully Vetted, certificate, PDF, or final-result content. Vendor Vetting exposes no batch, laboratory, or fabricated date. Waiting on Vendor may expose a real saved public expected date. Submitted to Laboratory exposes the saved expected date while protecting batch and laboratory. Verification in Progress may expose saved batch, expected date, and laboratory through the existing privacy allowlist. Incoming links to `/testing/{compound}/`; documented cards link to `/testing/{compound}/{batch}/`.

The vanilla-JavaScript carousel remains presentation-only. It uses native horizontal scrolling and scroll snapping, displays three equal near-square cards on desktop, two on tablet, and one on mobile, and receives at most four server-selected cards. Controls respond to actual overflow, touch/swipe remains native, resize/orientation changes recalculate the visible count, focus stays visible, reduced-motion preferences are honored, and there is no autoplay or keyboard trap. Role and workflow text ensure meaning is not conveyed by color alone.

The dedicated stylesheet and script enqueue only after the shortcode has resolved a connected compound and at least one eligible card on a valid single-product request. No carousel assets load on the archive, Vetting History, full reports, cart, checkout, WordPress administration, or an unconnected/no-record product. Status transitions need no Elementor change: an Incoming record that becomes Approved/Complete is reclassified on reload; a Failed record disappears from the product carousel while remaining governed by the existing history rules.

### Deployment and existing Elementor placement

No Elementor edit is required for beta.20. Upload the updated ZIP, replace and activate the existing plugin, clear Kinsta and browser caches, and reload the product page. The installed `[pepselect_product_coa_carousel]` widget and the existing ACF-backed top Button remain in place.

1. Open **Templates**.
2. Open **Theme Builder**.
3. Choose **Single Product**.
4. Select **Edit with Elementor** for the active template.
5. Add a **Shortcode** widget immediately after **Product Data Tabs**.
6. Place it before **You may also like**.
7. Enter `[pepselect_product_coa_carousel]`.
8. Update the template.

The plugin does not edit Elementor JSON or the template database record. It filters only the existing Button's `view_latest_co` dynamic URL and rendered label/visibility.

## COA-5B.2 archive alignment and sorting

The existing catalog grid now stretches its real card items within each desktop three-column and tablet two-column row. Each card remains a vertical flex container at full row height, its body consumes available space, and the unchanged View All Reports footer sits at the bottom. No placeholder content, fake batch pills, truncation, JavaScript measurement, or rigid fixed card height is used. At the single-column mobile breakpoint, cards return to natural content height to avoid excess whitespace and horizontal overflow.

The archive builds one batched public-test index before compound pagination. Compounds sort by: current approved Complete report; Verification in Progress; Submitted to Laboratory; Waiting on Vendor; Vendor Vetting; then no current approved or active incoming public record. A current approved release remains first even when a replacement batch is incoming. Without one, the most advanced public incoming stage wins. Failed reports and non-current historical approvals never create documented priority; draft, private, inactive, and otherwise non-public records cannot influence sorting.

Within each status group, Compound Display Order sorts ascending, followed by Display Name and WordPress post ID. Status priority always overrides Display Order, and no stored order or test metadata is rewritten. Search uses the same batched priority map before filtering/pagination, so matching results retain status order and accurate counts. The cache key includes the public priority scope, while existing Compound/COA Test save, ACF save, trash, untrash, and delete hooks continue to advance the archive namespace when ordering data changes.

## COA-5B.1 admin workflow corrections

The WooCommerce product-edit COA Archive panel now places Product SKU and Product ID in two equal first-row columns, Connection Status on a wrapping full-width second row, and its Create/Connect or connected actions in a full-width third row. Sidebar-specific sizing prevents horizontal overflow without changing Product Matching, nonces, permissions, or synchronization behavior.

Compound **Active** controls public archive and Vetting History eligibility without deleting the Compound, its tests, reports, or WooCommerce relationship. **Featured** provides priority placement only in supported sections and cannot make an inactive Compound public. The edit controls now explain those definitions directly.

Vendor Vetting and Waiting on Vendor no longer require a batch number, cap color, crimp color, exact vial photo, or optional identity gallery. Submitted to Laboratory preserves its established cap/crimp and expected-date requirements while keeping final results and documents unavailable. Verification in Progress requires Batch Number, Exact Batch Vial Photo, Cap Color, and Crimp Color, but not final PDF or final results. Complete retains the existing strict Approved/Failed documentation, laboratory URL, and truthful-result validation. The additional Batch Identity Photos gallery remains optional at every stage.

COA Test titles are maintained from the linked Compound Display Name. Vendor, waiting, and submitted records use the compound-only name; Verification in Progress and Complete records with a batch use `{Compound Display Name} — Batch {Batch Number}` for Pending, Approved, and Failed outcomes. Batch changes and normal Compound Display Name saves update linked test titles only when needed. Existing published `post_name` values and public URLs are preserved. The one-record CSV importer keeps its headers and preview/apply flow; its normal WordPress/ACF save invokes the same title synchronization.

## WooCommerce source of truth

The permanent relationship is the WooCommerce Product ID. The unique SKU is a matching and audit key, stored as a snapshot with the last successful synchronization time, product status, URL, title, and fallback image. The product title never automatically overwrites an existing scientific COA display name, base compound name, short name, description, slug, or public history. Products may define an optional **COA Display Name** so a storefront title such as `GLP-3 R` can create a draft compound named `Retatrutide`.

The product edit screen provides **Include in COA Archive**, COA Display Name, confirmed Strength and Strength Unit, connection status, and explicit Create/Connect/Sync actions. Create and Connect requires a valid unique SKU, a reviewed scientific name, and confirmed structured strength; it is nonce- and capability-protected, uses an atomic creation lock, creates exactly one Draft compound, and never fabricates a batch, test, result, laboratory, PDF, or report.

**COA Archive → Product Matching** uses WooCommerce products as primary rows. Exact SKU ranks first, followed by exact Product ID, SKU prefix, partial SKU, exact title, and partial title. Administrators can deliberately connect an existing compound, create an eligible draft, synchronize safe product-owned fields, disconnect without deleting history, or review Missing SKU, Duplicate SKU, Duplicate Product Link, SKU Changed, Needs Review, Product Missing, and WooCommerce Inactive states. Bulk inclusion, eligible draft creation, and connected-product synchronization skip ambiguous products.

Synchronization is intentionally one-way and narrow: Product ID, SKU snapshot, product status/URL/title, product image fallback, confirmed strength/unit, last sync, and status. It never changes testing, batches, laboratories, PDFs, certificate images, Batch Vial Photos, cap/crimp data, public names, descriptions, categories, display order, slugs, prices, inventory, stock, orders, checkout, or shipping. Product deletion, trashing, privacy changes, or WooCommerce deactivation preserve the compound and historical documentation.

Public image priority is now exact Batch Vial Photo, COA Test Featured Image, connected WooCommerce product image, Compound image, then the bundled neutral placeholder. The product image is only a fallback and never replaces saved batch evidence.

## Certificate lightbox viewport correction

The active viewer was nested inside the isolated report tree, so theme, Elementor, footer, and floating-widget stacking contexts could paint above it after the page was scrolled. The existing single lightbox node is now moved under `document.body` during initialization, with its approved design variables copied from the report. It remains `position: fixed`, `inset: 0`, `100vw`, `100vh`/`100dvh`, isolated at z-index `2147483000`, and route-scoped to full reports that contain certificate images.

Opening stores the exact scroll coordinates and existing body/document overflow, padding, and class state. It locks background scrolling and compensates for scrollbar width without moving the document. Closing restores every previous inline style and class state, returns to the exact saved scroll position, and restores focus to the launching thumbnail. Counter, contain sizing, close/backdrop behavior, previous/next controls, arrow keys, Escape, and focus trapping are unchanged.

## Archive catalog

The archive now uses a route-scoped navy technical hero, integrated accessible search, a live server-rendered “Showing X of Y compounds” count, a contained no-results state, and a responsive three/two/one-column grid. The approved compound-card partial and its wording, statuses, recent-batch limit, report counts, spacing, and destinations remain unchanged.

Core search works without JavaScript and preserves the sanitized query in the URL. It matches public compound display names, base names, short names, strength plus unit, and visible batch numbers from eligible public completed or in-testing reports. Draft, private, inactive, archived, and otherwise ineligible records remain excluded.

Archive-card images now follow the latest approved completed report, preferring its Batch Vial Photo, then its COA Test Featured Image, then the related Compound image, and finally the bundled neutral vial. Incoming reports and unrelated compounds cannot supply the card image.

Previous Reports controls remain behaviorally unchanged but now have fixed equal dimensions, an explicit square aspect ratio, non-shrinking flex constraints, circular clipping, centered glyphs, and the existing hover, focus, disabled, keyboard, and reduced-motion behavior.

## Package verification

The release archive is built from the `pepselect-coa-archive/` source directory, inspected entry by entry for forward-slash paths and a single top-level folder, then extracted to a temporary directory and hash-compared with the source tree. The only valid activation path is `pepselect-coa-archive/pepselect-coa-archive.php`; nested duplicate plugin folders, the ZIP itself, Graphify output, test artifacts, and reference screenshots are rejected.

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
