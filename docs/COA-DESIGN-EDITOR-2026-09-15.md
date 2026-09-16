# COA Design & Copy visual editor

Scope: COA report and archive presentation only. Existing 0.7.15 evidence rules remain intact. No Ops deployment, workflow transition, product stock-pill change, inventory write, report-data edit or PDF change.

## Audit

The old page linked to static examples. It changed public-status labels while the report hero used a hardcoded operational heading. Approved/failed headings and descriptions were hardcoded too. The editor now previews actual public templates with unsaved settings in a sandboxed frame at desktop/mobile widths. The report hero has one editable heading and description, no redundant outcome pill. Pending labels use existing saved values. New passed/failed heading fields preserve the existing default wording. Existing custom settings remain stored.

History eyebrow and latest/incoming/previous headings now use their existing settings. Old report introductions, history title suffix and pending-lab action are retained but disabled because the current templates do not expose them. The failed-only archive toggle is disabled with an explanation: failed-only compounds always remain visible under the existing transparency rule. Product carousel text is active but explicitly labeled as outside the report preview. Product stock pills are not controlled here.

Shared colors/corners affect the components already using those variables. The report outcome has dedicated color controls retaining its current defaults. Explicit heading fonts/weights now apply to the report hero; inherited typography retains its original serif design. Decorative details with fixed colors/corners are identified in helper text rather than represented as universally configurable.

Preview uses the same PHP templates and public visibility checks as the website. It omits the theme header/footer. Inherited fonts use system typography in the isolated preview; explicit fonts preview exactly. Links and forms cannot navigate/submit, while certificate-viewer and history-carousel interactions remain available. Important batch notes retain their separate callout and replace the standard description with the existing note link. Preview does not create draft records or store draft settings.

## Integration contract (future Ops use)

Namespace `/wp-json/pepselect-coa/v1`:

| Method | Route | Body / result |
|---|---|---|
| GET | `/design` | Schema version 1, fields with defaults/types/constraints/help/readonly, normalized saved settings, revision, available public preview choices |
| POST | `/design/preview` | `{settings: {key: value}, view: "report"\|"archive"\|"compound", id: number}` → `{html, canonical, saved: false}` |
| PATCH | `/design` | `{settings: {key: value}, revision: "..."}` → normalized saved settings and new revision |

All routes require authenticated `manage_ps_coas`. WordPress cookie clients send `X-WP-Nonce`; future authenticated clients must use an existing authorized WordPress authentication mechanism. No new credentials or access are provisioned. Responses are private/no-store. Preview is a POST to prevent drafts entering URLs/cache keys. Do not expose its HTML outside a sandbox.

Unknown, nonscalar, oversized or inactive fields return 400. Missing public reports return 404. A stale save revision returns 409, requiring a reload. PATCH merges supplied fields with existing settings, sanitizes via the shared schema and writes only `pepselect_coa_design_settings`; the existing archive-cache invalidation hook runs on a changed save. Blank copy uses defaults. Neither preview nor sanitization changes the request's saved cache; preview restores state in a finally block, including on render failure. Existing inactive values survive unrelated saves.

The operational `COA_Workflow` stage and product-carousel incoming copy remain independent of this presentation service. No changes were made to the external product stock pills (Restocking soon/In testing/none after release).

## Release state

Source implementation `636a4d4`; test-isolation/contract correction `7d11b50`. GitHub run 28, `35044050754`, passes PHP 8.1/8.2 (331 tests). Initial run 27 found two old hardcoded-copy expectations, a failed-note fixture using the wrong field (failed reports intentionally use release-decision notes), and leaked REST test-server state; these were corrected without changing production scientific or privacy behavior. Local syntax checks passed for 124 PHP files. Existing report refinement/evidence contracts passed. The older standalone Node product-carousel source-string check fails on the unchanged shortcode signature; the WordPress product-carousel regressions pass in CI.

Package `dist/pepselect-coa-archive-0.7.16.zip`: 117 runtime files, SHA256 `a7ea6eb6251496d00542a2281aa1c79c2a96bb48df621417f3ba6d3082643fc8`. Code-only rollback generated from `cd71dd2`: `dist/pepselect-coa-archive-0.7.15-rollback.zip`, SHA256 `314a47c3cf9fb5674b802c0ba0c696e9760404df9748307b1041759ca60bdd28`. Do not restore a full database over newer orders for a code rollback.

Staging upgraded from 0.7.12 to 0.7.16 on September 15. Real public reports load in the editor. Draft in-testing heading/description appeared in the preview while the anonymous public page retained saved text. Explicit Save persisted the new copy across a fresh editor load and public-page reload. Original staging copy was then restored and verified. Report-font draft produced the real report font variable; discarded without saving. Anonymous `/design` returned 401. Reta 10 note remained unchanged below the hero; the hero contained no outcome pill. Mobile preview: image/status cards both 158.5 × 201.125 px, vial left. Certificate page opened in the sandboxed viewer. No live copy or report records edited.

Live deployment pending fresh backup. MyKinsta has five manual backups; oldest verified September 12, 8:31 AM, “Before Reddit test reference 0.3.1 LIVE.” Four newer September 15 backups remain. Exact oldest-backup deletion approval requested before replacing it with a fresh pre-0.7.16 backup. Do not claim Live deployment until installed version and public/editor checks pass.
