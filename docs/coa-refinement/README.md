# Original-layout COA refinement — September 8, 2026

Paulo authorized these changes with “Good to go” after the accumulated screenshot feedback. This revision preserves the original report components. It supersedes the standalone layout proposal in `docs/coa-redesign`.

Implemented: shorter approval heading; prominent batch/lab/date; cap above crimp; public/report notes in the outcome card replacing generic copy; three compact measurement cards; gray Reported states; sample counts removed from public templates while retained internally; fixed seven-category strip with explicit pass/fail/neutral colors; compact expandable certificate thumbnails and laboratory metadata; visible report/PDF actions; verified product links at top and bottom with explicit historical context.

Mobile borrows the two-column approach from the Website repository's `pepselect-order-experience/assets/order-experience-overrides.css`. Important results remain expanded. Long notes use the full card width. Native disclosure controls work without JavaScript; the existing certificate lightbox remains available.

## Review previews

Run `php docs/coa-refinement/render.php`, then serve the repository root with a local HTTP server. Open `docs/coa-refinement/current.html`, `past.html`, `note.html`, or `states.html`.

These render **production PHP templates and CSS**, using local fixtures and lightweight WordPress function substitutes. They are not a full WordPress/theme integration test, and fixture scientific values are not reconciled source corrections. The note and fail/not-tested scenarios are illustrative. The earlier audit's outstanding dates, assay sensitivity, and sample-count questions remain separate; no stored scientific data was changed.

Screenshots: desktop-current.png, mobile-current.png, mobile-past.png, mobile-note.png, mobile-states.png. The original document thumbnails are reduced local preview copies; the public report buttons retain source destinations.

## Milestone 4: laboratory wording path

Planning only; do not implement automatic wording in this revision. Coordinate with **Explore responsive WordPress control** in the Control-app repository.

| Stage | Current source | Required future behavior |
|---|---|---|
| Lab selection | `class-coa-test-fields.php`: `testing_lab`, `other_testing_lab`; `class-coa-test-form.php` | Selecting ILS/Freedom proposes the wording approved for that lab. New lab setup collects Paulo's wording. |
| Entry and writes | `class-coa-test-form.php::result_fields()`, `class-rest-write-endpoint.php`, `class-coa-test-fields.php` | Carry explicit per-test methods/results through validation and persistence. Preserve report-specific overrides and provenance. |
| Stored fields | `purity_method`, `identity_method`, `heavy_metals_summary`, `sterility_result`, `endotoxin_result`, `endotoxin_unit`, `fentanyl_*`, numerical values and statuses | Defaults must never manufacture an actual finding, pass, cutoff, sensitivity, or measured value. |
| Public transformation | `Frontend_View_Model::report()`, `result_rows()`, `qc_strip_rows()` | Verify the chosen wording reaches the correct field and is preserved on the public report. |
| Display | `full-qc-results-table.php`; summary metrics and category strip | Detailed lab wording belongs in the evidence table. Compact cards omit methods. Reported stays gray. |
| Public notices | `public_notes`, `report_notes` → `report-hero.php` | Display once in the outcome card, replacing generic prose. |

Known legacy defaults needing attention in M4: `class-coa-test-fields.php` and `class-rest-write-endpoint.php` still default fentanyl to Immunoassay / 50 ng/mL; `history_report()` also has hardcoded wording. The report detail path uses `Report_Evidence::fentanyl()` to avoid asserting these unsupported defaults. Do not mistake these legacy defaults for source evidence or bulk-correct stored reports without reconciliation.

## Verification and release

Focused PHP contracts cover report rendering, report-only status, category order, public-note placement/replacement, hidden sample counts, failed/untested states, evidence safeguards, historical links, and QR isolation. Five JavaScript suites cover lightbox interaction, product links, sitemaps, redirects, and existing integration contracts.

Browser checks: desktop 1440px; phone 390px; narrow phone 320px with no horizontal overflow. Certificate expansion, image loading, close control, and initial keyboard focus verified. Source-specific scientific corrections and full WordPress/theme testing remain separate.

Local implementation only. No live deployment or public repository publication authorized.
