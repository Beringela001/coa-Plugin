# COA Archive 0.7.9: card fit and shared status wording

Product incoming cards now use the same Design Settings stage label and description as compound history, including the accessible link label. Stored workflow stages, status selection and public field restrictions are unchanged.

Archive stock images use the full existing media area with contain fitting and a larger responsive source. History stock images fill the existing compact box, trimming landscape background margins; the mobile box remains 168px square. Exact batch evidence photos and lightbox behavior are unchanged.

Verified proposed CSS in a local browser on the public archive and Cagrilintide history at 1366px and 390px: complete vials visible, no horizontal overflow. Carousel static checks and PHP syntax checks pass. The WordPress regression case now checks saved waiting-stage text against compound history; WordPress integration tests were not run locally.

Source-only release, paired with website theme 0.25.0-beta.109. No package or deployment performed. Deploy to staging first when authorized and verify saved custom stage wording. Rollback is the prior plugin package; no database migration is involved.
