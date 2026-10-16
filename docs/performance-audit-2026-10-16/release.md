# COA search release — October 16, 2026

Paulo authorized staging and Live deployment of the prepared search fixes. COA Archive `0.7.21` package is built at `dist/pepselect-coa-archive-0.7.21.zip`; SHA-256 `a1757bc2785d4e2d533848ea344746694af769da02a88e2b13351135a515277e`.

Archive integrity and paths passed. All 174 baseline files are retained; only the six approved runtime files differ from the retained 0.7.20 package. Search behavior tests passed again. Live WordPress's native plugin list confirmed active COA Archive 0.7.20 before deployment.

Status: awaiting restored MyKinsta and staging WordPress sign-ins. No backup or installation performed in this release attempt. Stage and test the exact package before Live, with named backups on both environments and verified oldest-bottom manual-backup rotation only when capacity requires it. Deployment authorization persists after sign-in is restored.

The full paired theme/plugin sequence is recorded in `pep-select-website/docs/performance-audit-2026-10-16/release.md`. Retain 0.7.20 for file rollback; preserve records and commerce data, and restore the prior placeholder setting only if migrated.
