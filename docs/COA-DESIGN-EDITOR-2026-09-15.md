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

Implementation and verification in progress. Not deployed. Package identity, CI results and staging/live checks must be recorded before claiming release completion.
