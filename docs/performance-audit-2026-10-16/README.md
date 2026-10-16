# COA mobile search correction — October 16, 2026

Target: COA Archive `0.7.21`. Source verified locally; not installed on staging or Live. No package built.

The mobile search controls change to a flex column while the input retained an 18rem row-width basis. This made the field approximately 288px tall. Reset its mobile basis to auto, retain a 50px archive input and 16px entered text, and adjust the placeholder size. The visible label is **Looking for a COA**; the placeholder is **Type the batch number or compound here**. The versioned migration replaces known legacy defaults while preserving custom values for inspection during release.

Suggestions now cancel obsolete work as soon as text changes, ignore stale responses, clear old keyboard selection, remain dismissed after Escape/outside taps, cache up to 30 successful nonempty terms per page, and use 100ms debounce for uncached requests. The input references its listbox. Selection uses click, supporting touch and pointer activation. Normal form submission and public record permissions are preserved.

Verification: the standalone `pepselect-coa-archive/tests/test-search-responsiveness.cjs` passes request-ordering, cache, clearing and dismissal checks. Changed PHP and JavaScript pass syntax checks. In anonymous local previews using public markup and the changed CSS/JS, the field is 50px tall at 320/390px without horizontal overflow, and stays 50px in a row at 768px. The full placeholder is visible at 390px; native single-line clipping can still occur at the narrowest sizes. Preview response data is a fixture, not an integration test. Scoped Graphify extraction is included here.

Before deployment, verify the real endpoint, batch/compound results, touchscreen selection, empty/failure states, customized placeholder settings, and neighboring archive/report pages in staging. Deployment is a separate authorized milestone. Build only into the repository-root `dist/` folder and print the package SHA-256 when an installation package is requested. Rollback restores `0.7.20` files, relevant caches and the previous placeholder setting if migrated, without changing COA records or commerce data.

The corresponding website/theme audit is `docs/performance-audit-2026-10-16/README.md` in `pep-select-website`.
