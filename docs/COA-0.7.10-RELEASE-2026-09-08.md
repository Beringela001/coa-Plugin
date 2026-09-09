# COA Archive 0.7.10 release

Paulo approved the overview preview and the two Cagrilintide text corrections before deployment. Scope: omit untested overview cards; green completed/reported categories; red failures always visible; Heavy metals and Endotoxins last; accurate measured-content history caption. No Control/Ops, orders, webhook, checkout or payment changes.

## Package and rollback

- Identical saved package installed on staging and Live: `dist/pepselect-coa-archive-0.7.10.zip`, 113 runtime/readme files.
- SHA-256: `df7aa58858ce5e11afdc11369969613a4faef53476d7e5339b1e68fe5cf6ac68`.
- Code rollback: saved `dist/pepselect-coa-archive-0.7.9.zip`, SHA-256 `21c16b5cdbdc7d42ce0c0c23a944bf6a5bfceb89cae6fca810021bb7cae28f0a`; ZIP integrity verified. Reinstall this package and clear caches for code rollback. No database restore is required for a code rollback.
- Packages exclude tests, screenshots, repository metadata and internal documentation. No data migration.

## Backups and deployment

Verified environment in MyKinsta before each operation. Manual backups were full and listed newest to oldest. Deleted only the oldest bottom entry each time:

- Staging: September 4, 1:50 PM, `Before Cart Recovery 0.6.0 staging - 2026-09-04`.
- Live: September 8, 1:00 PM, `Before COA mobile report 0.7.9 - 2026-09-08`.

Created and confirmed Restore available before installation:

- Staging: September 8, 10:15 PM, `Before COA overview 0.7.10 staging - 2026-09-08`.
- Live: September 8, 10:25 PM, `Before COA overview 0.7.10 LIVE - 2026-09-08`.

WordPress compared the uploaded COA 0.7.10 against installed 0.7.9 in each environment, then reported successful update. Live plugin list independently confirmed active 0.7.10. MyKinsta Live cache purge requested; initial normal public response remained 0.7.9 during propagation, then the normal URLs independently served the corrected release.

Automatic approval review rejected an ambiguous generic staging Install Now click. Verified the chosen ZIP and upload-specific submit control, then proceeded successfully. No unresolved approval block.

## Approved Live data correction

Exact record: Cagrilintide CG1026233GX, COA-2026-FJKMHM, WordPress post 1698. Before/after form comparison found only these three underlying text fields changed for the two approved display corrections:

| Field | Before | After |
| --- | --- | --- |
| Heavy metals summary | Arsenic, cadmium, chromium, mercury, and lead were not detected. | Not reported in this certificate. |
| Endotoxin result | Empty | Not reported in this certificate. |
| Endotoxin unit | EU/mL | Empty |

Both statuses remain Not Tested. Every other form value was preserved, including 99.94% purity, 10.3 mg measured content, dates, source links, workflow and approval. The separate date discrepancy was not changed. The original certificate was not changed.

Staging's Cagrilintide record is older, Verification in Progress, and result fields are disabled. No staging data was saved and its workflow was not bypassed. Data correction preview was verified locally; exact saved correction was verified on Live after approval.

## Verification

- Five focused PHP contracts pass: overview/caption rendering and state precedence, logo URL, evidence/current mapping, historical navigation, NAD correction. Five JavaScript suites pass: lightbox, product links, sitemaps, redirects, integration.
- Staging RT30 serves 0.7.10 assets with correct seven-card order, green measured content, and corrected history caption. Desktop and phone captures saved.
- Normal public Cagrilintide URL has five green overview cards, no gray untested cards; detailed Heavy Metals and Endotoxins rows retain Not Tested and approved corrected text.
- Actual failed historical RT20 PSRT2062926JP keeps Testing failed and red Identity Fail. Reported categories remain green in its overview. Missing measured content is omitted.
- Normal RT30 compound history shows 32.78 mg and Content measured during testing.
- Live phone widths 390 and 320 have no horizontal overflow. Desktop 1440 and phone390 captured. Exact Cagrilintide canonical is unchanged.
- Certificate disclosure, lightbox opening and close control work.
- RT10 customer note remains once inside the green Testing passed card; canonical unchanged.
- Archive and matching Cagrilintide product return200; legacy NAD typo URL returns301.

Captures are in `docs/coa-refinement/`: `staging-0710-desktop.png`, `staging-0710-mobile.png`, `live-0710-desktop.png`, `live-0710-mobile.png`, `live-0710-failed.png`.

Version and release evidence are recorded in `1994dec4244315e1f02902af209d44680be4f0b2`. Automatic approval review initially blocked public publication. Paulo subsequently approved proceeding in response to the question naming public `Beringela001/coa-Plugin`, branch `codex/coa-report-redesign`, and its code/release documentation/screenshots. The branch was then successfully pushed with upstream tracking. This release ledger is published with the source. No merge to main is part of this release. Deployment is complete and verified.
