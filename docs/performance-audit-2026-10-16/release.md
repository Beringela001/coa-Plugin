# COA search release — October 16, 2026

Paulo authorized staging and Live deployment of the prepared search fixes. COA Archive `0.7.21` package is built at `dist/pepselect-coa-archive-0.7.21.zip`; SHA-256 `a1757bc2785d4e2d533848ea344746694af769da02a88e2b13351135a515277e`.

Archive integrity and paths passed. All 174 baseline files are retained; only the six approved runtime files differ from the retained 0.7.20 package. Search behavior tests passed again. Live WordPress's native plugin list confirmed active COA Archive 0.7.20 before deployment.

Status: deployed and verified on staging and Live on October 16, 2026. Staging backup **Before search speed and COA mobile STG Oct16**, **8:49 a.m. EDT**. Live backup **Before search speed and COA mobile LIVE Oct16**, **8:55 a.m. EDT**. Live capacity was full; verified and removed only the bottom/oldest October 8, 10:27 p.m. manual backup, retaining the four newer entries. Both native WordPress replacements succeeded, and the Live plugin list confirms active **0.7.21**.

Staging real endpoint: batch RT2026205JP suggestions and pointer selection opened the exact report. Live real endpoint: RT1026258JP selection opened the exact current report; the archive still showed 19 compounds. Exact label/placeholder verified in anonymous HTML and browser. Live field measured 50px at 390px / 3× density, without horizontal overflow. Cart and checkout remained rendered without WooCommerce errors; no orders, payments or cart inputs were changed. Kinsta records the Live cache-clear action at 8:57 a.m. EDT; updated public markup and versioned assets were verified. Staging's pre-existing disabled caching was left unchanged.

The full paired theme/plugin sequence is recorded in `pep-select-website/docs/performance-audit-2026-10-16/release.md`. Retain 0.7.20 for file rollback; preserve records and commerce data, and restore the prior placeholder setting only if migrated.
