# Pep Select COA Archive

Version 0.4.0-beta.3 is the COA-4 refinement pass: tighter public layouts, transparent incoming and failed batch histories, clearer batch identity, and more approachable design-setting guidance.

## Public frontend

The existing server-rendered routes remain unchanged:

- `/testing/`
- `/testing/{compound-slug}/`
- `/testing/{compound-slug}/{batch-slug}/`

Archive cards use a larger contained compound image, `compound_name` as the preferred title, a separate formatted strength pill, a mission-aligned assurance treatment, three recent public batches at most, the complete public-report count, and a configured history action. Approved, incoming/vendor-vetting, and failed batches use distinct accessible treatments.

The history header is compact: configurable eyebrow, compound name, separate strength, configured “Vetting History” suffix, and latest approved report date (or an in-progress fallback). The report count is absent from the header. A non-empty archive description appears separately below it. Sections are ordered Latest Report, Incoming Reports, then Previous Reports. Latest contains only the newest approved report; incoming contains pending, in-testing, and vendor-vetting reports; previous contains older approved reports and clearly marked failed reports.

The full report retains its breadcrumb, compact header, metrics, Full-QC rows, exact public laboratory link, valid original PDF, saved-order certificate pages, notes, and navigation. A batch-state banner and vial identity block clarify approved, in-progress, and failed records. Incoming reports do not display empty scientific result panels. Public access codes, generic verification URLs, and laboratory addresses remain absent.

## Admin test fields and validation

COA Test administration adds Expected COA Date, Vial Crimp Color, Vial Cap Color, conditional Other text inputs, and In-Progress Lab URL without renaming existing ACF keys. Incoming states may omit final lab, test date, vial colors, and tested-vial count. Approved and failed states require those release identity values. Approved status additionally requires a valid original PDF, at least one valid certificate image, no failed result status, and the exact Public Lab Report URL used by “View at ILS Labs.”

Published approved, failed, pending, in-testing, and vendor-vetting records may be public after centralized eligibility checks. Archived, superseded, draft, private, explicitly private, inactive-compound, and mismatched-compound records remain excluded.

## Scientific display rules

Stored laboratory metadata is never rewritten for presentation.

- Quantities and mass values use at most two useful decimal places: `29.999999` → `30`, `31.010000` → `31.01`, and `5.500000` → `5.5`.
- Purity uses at most four useful decimal places.
- Vial counts display as integers.
- Pass uses a more visible green circular check plus the text “Pass.”
- Identity details such as “Confirmed,” heavy-metals summaries, and sterility results such as “No Growth” receive success treatment only when their stored status is `pass`.
- Fail, Pending, Not Tested, and Not Applicable keep distinct icons, colors, and visible labels.

An endotoxin status of `reported` receives a green check only when the COA post is published, `coa_status` is approved, and a non-empty endotoxin result exists. It still visibly says “Reported”; it is never relabeled Pass, no threshold is inferred, and stored data is unchanged.

“Full-QC Documented” appears only when the report is published and approved, purity/identity/heavy-metals/sterility statuses are all Pass, and endotoxin is Pass or is Reported with a non-empty result. Other public approved reports receive the configurable neutral “Independent Report Published” label.

## Certificate viewer

Certificate thumbnails retain saved order and Page N labels. Images use responsive `medium_large` sources, lazy loading, configurable radius, and `object-fit: contain` without cropping. The dependency-free fullscreen viewer uses up to approximately 96vw × 92vh, maintains page aspect ratio, prevents body scrolling while open, and restores it on close. Escape, arrow navigation, visible count, focus containment, focus return, touch-sized controls, and reduced-motion behavior remain.

## Design & Copy settings

Administrators with `manage_ps_coas` can open **COA Archive → Design & Copy**. The screen uses the WordPress Settings API and the single `pepselect_coa_design_settings` option. Every setting has a short helper and a “What this changes” link; each logical group has a linked preview example. It loads WordPress’s color picker and its small admin assets only on that screen.

Settings include:

- Colors: page, card, muted surface, primary/muted text, border, accent, accent-word, success, information, warning, and failure.
- Typography: inherited, System Sans, Arial/Helvetica, Georgia, or Times New Roman for headings and body; heading/body weights; normal or italic accent words. No remote fonts load.
- Corners and borders: card, panel, image, search field, search button, primary button, and secondary button radii (0–40px); card and input borders (0–4px).
- Buttons and search: background, text, border, hover background/text/border for primary, secondary, and search buttons; input background, text, border, placeholder, and focus border.
- Lightbox: overlay color and 0.50–1.00 opacity; control background, text/icon, border, and 0–40px radius.
- Public copy: archive eyebrow/title/introduction, history eyebrow/suffix, latest label, Full-QC label/copy, neutral label, history/report actions, search placeholder, and search button label.

Empty or invalid settings fall back to the COA-4B appearance. Color, choice, text, integer, and opacity inputs are normalized by type. No arbitrary CSS input exists.

`wp_add_inline_style()` outputs sanitized variables only when the frontend COA stylesheet loads. Variables are scoped to `.ps-coa-app`, including:

`--ps-coa-page-bg`, `--ps-coa-surface`, `--ps-coa-surface-muted`, `--ps-coa-text`, `--ps-coa-text-muted`, `--ps-coa-border`, `--ps-coa-accent`, `--ps-coa-accent-word`, `--ps-coa-success`, `--ps-coa-info`, `--ps-coa-warning`, `--ps-coa-danger`, typography variables, radius/border variables, button/search variables, and lightbox variables.

Reset to Defaults requires `manage_ps_coas`, a valid nonce, and confirmation. It deletes only the design/copy option. Compounds, reports, routes, relationships, and media remain untouched.

## Performance, security, and compatibility

Normalized settings are cached in memory for the request, preventing option queries per card. Frontend CSS still loads only for COA routes or shortcodes; gallery JavaScript still loads only for reports with images. Admin assets load only on Design & Copy. No external fonts, frameworks, AJAX, telemetry, or HTTP requests were added.

Visibility, current-report ordering, routes, rewrite rules, template overrides under `theme/pepselect-coa/`, shortcodes, ACF keys, CSV import, direct report links, PDF validation, gallery order, capabilities, and non-destructive uninstall behavior are preserved. WooCommerce product-page cards and Elementor widgets remain deferred.

## Manual QA checklist

### Archive

1. Visit `/testing/`.
2. Confirm compound image is larger and not cropped.
3. Confirm compound title is easier to read.
4. Confirm strength is shown separately without duplicate wording.
5. Confirm assurance label appears appropriately.
6. Confirm search button says Search.
7. Confirm search button is not blank or red.
8. Confirm search radius setting changes the search form.
9. Confirm View all reports works and its count includes all public batch states.
10. Confirm mobile archive layout works.

### History

11. Open a compound history page.
12. Confirm approved-report count is removed from the top header.
13. Confirm latest report date remains.
14. Confirm the header contains less text.
15. Confirm history title uses the configured suffix.
16. Confirm archive description is outside the main compact header.
17. Confirm Latest Report contains only the newest approved report.
18. Confirm Incoming Reports contains in-testing and vendor-vetting batches, photos, dates, and available lab-progress links.
19. Confirm Previous Reports contains older approved and failed batches.
20. Confirm failed batches say they were not released for sale and remain inspectable.
21. Confirm green/progress/fail pills, clean numeric values, and mobile layout work.

### Full report

22. Open approved, incoming, and failed full reports.
23. Confirm spacing is tighter and metric boxes are centered.
24. Confirm `29.999999` no longer appears and labeled content displays as `30 mg`.
25. Confirm Pass, Reported, and No Growth are visually obvious where semantically successful.
26. Confirm Download Original PDF works.
27. Confirm View at ILS Labs uses the exact Public Lab Report URL.
28. Confirm incoming reports show progress documentation without empty result panels.
29. Confirm access codes, generic verification URLs, and laboratory addresses are absent.

### Lightbox

30. Confirm certificate thumbnails are prominent, grouped, and uncropped.
31. Open Page 1 and confirm it uses most of the viewport without cropping.
32. Confirm previous/next, Escape, focus return, and mobile controls work.

### Settings

33. Open COA Archive → Design & Copy.
34. Confirm helper text, Preview example, and What this changes links exist and reach the right examples.
35. Change representative colors, radii, buttons, lightbox, typography, and copy; save and confirm the frontend updates.
36. Reset to defaults and confirm business data remains intact.

### Admin form

37. Create pending, in-testing, and vendor-vetting reports without final release fields; confirm they can save.
38. Confirm crimp/cap dropdowns work and Other reveals and requires its text input.
39. Confirm approved and failed reports require test date, lab, vial colors, and at least one tested vial.
40. Confirm approved cannot save without a valid public lab URL, PDF, and certificate image.
41. Confirm expected date and pending lab URL save and appear on Incoming Reports.

### Site safety

42. Visit homepage, shop, product pages, cart, and checkout.
43. Confirm COA CSS variables do not affect unrelated pages.
44. Confirm no product-page COA cards, hooks, or Elementor widgets were added.
45. Confirm wp-admin compound/COA editing and CSV import still work.
