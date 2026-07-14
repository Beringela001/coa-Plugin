# Pep Select COA Archive

Version 0.4.0-beta.2 is Milestone COA-4C: frontend polish, clearer scientific status presentation, full-viewport certificate viewing, and administrator-controlled design and public copy.

## Public frontend

The existing server-rendered routes remain unchanged:

- `/testing/`
- `/testing/{compound-slug}/`
- `/testing/{compound-slug}/{batch-slug}/`

Archive cards now use a larger contained compound image, `compound_name` as the preferred title, a separate formatted strength pill, an assurance treatment, three recent batches at most, the complete approved-report count, and a configured history action. Search remains a sanitized GET request and has strongly scoped input/button styling.

The history header is compact: configurable eyebrow, compound name, separate strength, configured “Vetting History” suffix, and latest report date. The approved-report count is no longer in the header. A non-empty archive description appears separately below it. Latest and previous report cards preserve all existing ordering and pagination.

The full report retains its breadcrumb, header, metrics, Full-QC rows, direct laboratory report, valid original PDF, saved-order certificate pages, notes, and navigation. Public access codes, generic verification URLs, and laboratory addresses remain absent.

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

Administrators with `manage_ps_coas` can open **COA Archive → Design & Copy**. The screen uses the WordPress Settings API and the single `pepselect_coa_design_settings` option. It loads WordPress’s color picker and its small admin assets only on that screen.

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
9. Confirm View all reports remains correct.
10. Confirm mobile archive layout works.

### History

11. Open a compound history page.
12. Confirm approved-report count is removed from the top header.
13. Confirm latest report date remains.
14. Confirm the header contains less text.
15. Confirm history title uses the configured suffix.
16. Confirm archive description is outside the main compact header.
17. Confirm pass checks are more visible.
18. Confirm reported endotoxin receives a green check but still says Reported.
19. Confirm numeric values are clean.
20. Confirm mobile layout works.

### Full report

21. Open a full report.
22. Confirm `29.999999` no longer appears.
23. Confirm labeled content displays as `30 mg`.
24. Confirm Identity Pass has a visible green check.
25. Confirm Heavy Metals Pass has a visible green check.
26. Confirm Sterility No Growth has a visible green check.
27. Confirm approved Reported endotoxin has a visible green check.
28. Confirm endotoxin still says Reported.
29. Confirm access code is absent.
30. Confirm generic verification URL is absent.
31. Confirm laboratory address is absent.
32. Confirm View Lab Report uses the exact direct URL.
33. Confirm Download Original PDF still works.

### Lightbox

34. Open Page 1.
35. Confirm the entire certificate page is visible.
36. Confirm the image is not cropped.
37. Confirm it uses most of the viewport.
38. Confirm previous/next buttons work.
39. Confirm Escape closes it.
40. Confirm mobile controls work.
41. Change lightbox colors in settings.
42. Confirm controls update and are not red.

### Settings

43. Open COA Archive → Design & Copy.
44. Change page background color.
45. Change card background color.
46. Change success color.
47. Change accent-word color.
48. Change card radius.
49. Change search radius.
50. Change button radius.
51. Change lightbox colors.
52. Change heading font to System Sans.
53. Change history suffix.
54. Save settings.
55. Confirm frontend reflects changes.
56. Reset to defaults.
57. Confirm business data remains intact.

### Site safety

58. Visit homepage.
59. Visit shop.
60. Visit product pages.
61. Visit cart and checkout.
62. Confirm COA CSS variables do not affect unrelated pages.
63. Confirm no product-page COA cards were added.
64. Confirm wp-admin compound and COA editing still work.
65. Confirm CSV importer still works.
