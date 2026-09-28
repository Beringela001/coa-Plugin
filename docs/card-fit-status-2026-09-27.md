# COA Archive 0.7.20: card fit and shared status wording

Product incoming cards now use the same Design Settings stage label and description as compound history, including the accessible link label. Stored workflow stages, status selection and public field restrictions are unchanged.

Archive stock images use the full existing media area with contain fitting and a larger responsive source. History stock images fill the existing compact box, trimming landscape background margins; the mobile box remains 168px square. Exact batch evidence photos and lightbox behavior are unchanged.

Verified proposed CSS in a local browser on the public archive and Cagrilintide history at 1366px and 390px: complete vials visible, no horizontal overflow. Carousel static checks and PHP syntax checks pass. The WordPress regression case now checks saved waiting-stage text against compound history; WordPress integration tests were not run locally.

Initial source candidate 0.7.9 was based on an older checkout. During the authorized staging deployment, the installer identified installed 0.7.19 before replacement. The outdated upload was cancelled. Deployed-release branch `origin/codex/coa-report-redesign` at `2b58980` was merged into this branch, preserving all newer image, report and other functionality. The revised release is 0.7.20, paired with theme 0.25.0-beta.109. Rollback is 0.7.19 or the named staging backup; no database migration is involved.

## Staging deployment verified September 28

Installed 0.7.20 from source `584a511` after backup `Before card fit theme109 COA079 STG Sep28` completed (listed Sep27 11:49 PM). ZIP SHA-256: `84d8536b2172970d3e92cc6fccbee3403f9c75a39f3143b565511266391cdd76`. WordPress confirmed upgrade from 0.7.19 and Kinsta confirmed all caches cleared. The 0.7.9 package was never installed.

1366px and 390px browser checks passed for archive/history images and page overflow. Product and history incoming cards share saved `Verification in Progress` / `Testing is underway.` text. No waiting-vendor staging record exists, so that specific state was not exercised by changing business data. Existing batch-photo and PDF links remain available. Logged-out page uses the 0.7.20 stylesheet and retains its access gate. No subscriptions, orders or data migrations were executed. Full WordPress integration/CI results are not claimed. Live remains unchanged.
