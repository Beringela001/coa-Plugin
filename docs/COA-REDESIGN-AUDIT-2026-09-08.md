# COA evidence and redesign review — September 8, 2026

## Release state

Local review only. No staging/live deployment, publication, database update, indexing request, tracking installation, or original-document modification. Branch `codex/coa-report-redesign` starts from `a7b5d01`, the 0.7.8 COA source. Live frontend assets independently expose version 0.7.8; the September 5 release record corroborates this. The newly created worktree initially pointed at 0.6.0 commit `871ff77`; it was moved to an isolated branch based on the newer source before edits. Concurrent `codex/ops-coa-link` and catalog work were preserved.

The deliverables have two distinct purposes:

- `docs/coa-redesign/index.html` is an interactive design proposal, with source-checked past/current examples. Its corrected labels and dates are fixtures for review, not production overrides. Reviewer-only discrepancy controls are not part of the proposed public page.
- Plugin changes are a limited, reviewable foundation: exact-current/product navigation, disclosure of long report notes, removal of an invented fentanyl method/cutoff, neutral release wording, removal of the report's repeated aggregate-pass strip, and no “Verified” label manufactured from an approved release for labeled content. This is **not** a completed production implementation of the visual prototype or an archive-wide evidence migration.

## Scientific evidence findings

Original files downloaded read-only and inspected visually and by text extraction:

- [ILS original PDF, ND_R30_060326](https://pepselect.com/wp-content/uploads/2026/07/pep-select-ND-R30-060326-1OuLv5-1.pdf), two pages.
- [Freedom laboratory PDF, RT3026233GX](https://coas.freedomdiagnosticstesting.com/PepS2608270076.pdf), one page; [site-hosted original link](https://pepselect.com/wp-content/uploads/2026/08/PepS2608270076.pdf) retained.

Downloaded-file SHA-256: ILS `60d38117164b10ace953e4e4719f0c2f66b8a133f46ef530b9c1e665143332fc`; Freedom `18e08fb8bceaffa4d3dadbcdec9d62901c5bbeebc9ad2071c2e2a3f2af2768c2`. Originals remain local audit inputs, not rewritten artifacts. Historical photo comes from the existing public `IMG_4817-768x1024.webp`; the current photo is the existing local `RT3026233GX-cleaned.png`. No vial imagery was generated or edited in this task.

| Field | Existing public display | Original evidence | Review decision |
|---|---|---|---|
| Past batch date | June 24, 2026 labeled test/report/analysis | ILS analysis and issue June 25, 2026 | Show source-specific date in proposal; saved date unchanged |
| Current batch date | August 27 labeled test/report/analysis | Freedom Received August 27; Reported August 31 | Proposal uses Reported August 31; no distinct analysis date inferred |
| Past endotoxin | 0.05 EU/mL; Pass | NMT 0.05 EU/mL; Reported; specification Report Result | Preserve NMT qualifier and Reported; never synthesize Pass |
| Current endotoxin | Method and ≤0.05 EU/mL in result string; Pass | Two replicate results Pass; **assay sensitivity** ≤0.05 EU/mL | Separate sensitivity from measured concentration; do not infer a quantitative result |
| Past microbial test | Sterility; No Growth | Heading Sterility Testing (PCR), row Sterility (PCR), No Growth, PASS | Preserve ILS terminology with PCR visibly attached |
| Current microbial test | Sterility; Microbial Analysis (PCR) - No Detectable Microbial DNA | Microbial Analysis (PCR); No Detectable Microbial DNA; summary Pass | Preserve Freedom terminology; do not replace with an equivalent-sounding sterility claim |
| Current fentanyl method/cutoff | Immunoassay; 50 ng/mL | No Fentanyl Detected; no method or cutoff stated | Remove source-code guess for all labs. Original ILS PDF does state immunoassay/50 ng/mL, but it cannot be copied into Freedom records |
| Net content and sample count | “Average Net Content”; past 1 vial/current 3 | ILS Net Peptide Content 30.45 mg, Report Only/N/A; Freedom Net Content 32.78 mg; neither PDF substantiates averaging or sample count | Proposal shows net content separately from purity. Retain saved averaging/count fields pending provenance; parent has asked Paulo for additional evidence |
| Purity | Past 97.62%; current 99.86%, both Pass | ILS ≥95% specification/PASS and RP-HPLC area normalization at 214 nm. Freedom HPLC-UV 99.86%; no numerical specification or explicit purity-row Pass | Proposal distinguishes explicit laboratory Pass from a value reported; no common specification fabricated |
| Heavy metals | Generic common category | ILS five analytes includes chromium with numeric limits; Freedom four analytes excludes chromium and gives no numeric limits | Keep each analyte panel and limits distinct |
| Past certificate gallery | Three images labeled certificate pages | Downloaded original PDF has two pages; third displayed image filename ends `ND_R30_060326_Summary-1` | Likely added summary by filename, not proven authorship. Review original vs supplemental labeling; do not silently delete |

The current Freedom PDF also varies its method wording between the HPLC-UV analytical row, an HPLC/LC-MS method line, and LCMS/MS in its footer. The proposal preserves the analytical label and explains this source discrepancy rather than inventing a normalized method.

The generic schema cannot prove whether a method is absent from a PDF merely because it is not stored. Consequently the runtime fix suppresses fabricated method/cutoff text and keeps original-report access. The preview says “not stated in this report” only where this specific PDF was inspected. A source-specific normalized evidence model remains a separate required step.

## Public archive inventory and coverage

All 19 batch/progress links exposed by the live 14-compound archive were visited directly. Twelve name ILS, six name Freedom Diagnostics Testing, and one is a waiting-on-vendor record with no public laboratory. No public Janoshik or other-lab record appeared in this inventory. The schema's support for Janoshik/Other does not establish their use. Private/draft records were not inventoried.

| Batch / progress record | Public laboratory | Original PDF reconciliation in this task |
|---|---|---|
| BP1026232JP | ILS | Not completed |
| CG1026233GX | ILS | Not completed |
| PSGKCU5071926GX | Freedom | Not completed |
| GT60026232JP | ILS | Not completed |
| MC1026233GX | ILS | Not completed |
| PSPT14162926JP | ILS | Not completed |
| RT2026205JP | Freedom | Not completed |
| SS1026233GX | ILS | Not completed |
| TB1026233GX | ILS | Not completed |
| TB10-6926 | ILS | Not completed |
| PSTES1071926GX | Freedom | Not completed |
| RT3026233GX | Freedom | Completed, discrepancies above |
| ND_R30_060326 | ILS | Completed, discrepancies above |
| RT2026233GX | Freedom | Not completed |
| PSRT2062926JP | ILS | Not completed |
| KPV progress-1898 | No lab shown | Incoming record, not a completed report |
| KP1026232JP | ILS | Not completed; prior task had reviewed failed purity, not substituted for a new audit |
| ND50026205JS | Freedom | Not completed |
| PSNAD562926JP | ILS | Not completed |

Public templates expose identity, purity, net content, heavy metals, microbial/sterility, endotoxin, and fentanyl categories, including failed and pending states. Category names and stored pass flags are not proof of the underlying method or exact analyte panel. Only the two fully reconciled originals support the method comparisons above. Safe runtime safeguards are lab-independent because they remove inference; they do not declare all labs' methods equivalent.

## Current versus past discovery evidence

Direct live browser inspection:

- [Past ND_R30_060326](https://pepselect.com/testing/retatrutide-30mg/nd_r30_060326/) says Past, has its own canonical URL and index/follow metadata, and links to RT3026233GX as Next. No main-content product link existed.
- [Current RT3026233GX](https://pepselect.com/testing/retatrutide-30mg/rt3026233gx/) says Current, has its own canonical URL and index/follow metadata.
- [History](https://pepselect.com/testing/retatrutide-30mg/) identifies the current batch and links to [product glp3-r30](https://pepselect.com/product/glp3-r30/). Product page body identifies product ID 865 and links back to that exact current batch. Public mapping is observed in both directions; no product title/SKU fallback was used.
- Live archive currently lists RT3026233GX before the old report. Earlier web-tool retrieval returned crawl snapshots from different dates with older current-state data. These snapshots do not demonstrate a present cache defect.

Parent task owns Search Console and sitemap inspection. Its verified findings supplied here: old URL indexed, last crawled August 14 by smartphone, self-canonical selected by Google; current exact URL unknown/not indexed with no crawl. Sitemap index successful, last read September 7; COA sitemap successful, last read September 8, 19 URLs, both exact batch URLs present. XML lastmod old August 26, current September 1. Sitemap lastmod is a modification timestamp, not proof of first publication.

This establishes a Google discovery/indexing gap. It does not identify why ChatGPT selected the historical page. Discovery/processing/reporting delay is a hypothesis. No missing-sitemap-code defect, canonical mismatch, live cache cause, or need to redirect one certificate to another was established. Preserve both historical and current URLs.

## Implementation and verification

The router uses public repository candidates, explicit current flag, exact compound ID, approved/complete state, and absence of failed result categories. Multiple current candidates suppress the current destination. Product links require a resolved published Product ID URL and exactly one public compound relationship; duplicates or missing relationships suppress that link. No “in stock” claim is made. History remains the fallback. No changes to slugs, canonical rules, SEO schema, product inventory, prices, checkout, permissions, saved report facts, or PDF files.

Local runtime tests: PHP evidence/current selection contract; actual navigation template render contract for past/current/unresolved/escaping states; neighboring NAD QR regression contract; PHP lint on changed PHP files; preview fixture/navigation tests; whitespace diff check. All passed. Browser checks: desktop 1440 and phone 390, images loaded, no horizontal overflow, past-to-current button selected the correct preview, product destination exactly glp3-r30, native result disclosures opened and exposed sensitivity separately.

Additional neighboring JavaScript checks passed: 0.7.7 integration contracts, QR redirects, SEO product links, and SEO sitemaps (four files). The product-link test initially failed because it hard-coded release 0.7.3 even though the untouched plugin header/runtime were already 0.7.8. Its assertion now verifies that declared versions exist and agree; product-link assertions remain intact. This was a test repair, not a plugin version change.

Final proposal uses approved Plus Jakarta Sans interface typography and IBM Plex Mono figures/batch identifiers, white route cards, neutral slate historical status, and 6px inner-card corners. Google Fonts' official repository supplied the local font binaries and included OFL licenses. Loaded font faces and rendered computed styles were verified. No remote font runtime dependency or live font change was introduced. See `docs/coa-redesign/fonts/README.md`.

Full WordPress integration tests were not run: this worktree has no provisioned WordPress test database/bootstrap. The existing `PRE-EXISTING-TEST-FAILURES.md` documents historical harness and assertion failures; it is not claimed as a current test run. No staging/commerce/end-to-end release verification occurred. Prototype screenshots are of the local proposal, not the WordPress runtime.

Graphify: no graph existed in the COA root. The parent website graph lacked this plugin's implementation ownership. A fresh local structural graph was generated after changes (no LLM): 1,544 nodes / 2,437 edges on the first build, refreshed at final verification. Graph coverage is structural, not a scientific evidence source.

## Proposed event hooks and questions

Passive `data-coa-action` selectors: `current_report`, `product`, `laboratory`, `pdf`. The existing tracking task owns taxonomy, consent, staff exclusions, dispatch and deduplication. Suggested public context: compound ID, report ID, batch, current/past/release state; never email, order identity, lab access codes or user-entered free text. No analytics sender or second system was installed here.

Approval is needed for the exact visual proposal and separately for any source-based saved-data correction. Parent is consolidating questions:

1. Provide supplementary lab/import evidence for reported sample counts and averages; otherwise keep these as recorded values without claiming the PDF proves them.
2. Confirm date corrections, ILS endotoxin qualifier/status, and original-versus-supplemental page labeling through a reviewed, auditable correction process.
3. Approve the visual direction before completing its WordPress template implementation and staging review.

Next release gate: complete per-record evidence mappings for any newly changed lab claims, retain original fields and sources, then verify approved desktop/mobile templates on staging with real records. No broad “all tests passed” normalization or silent historical rewrite is authorized.
