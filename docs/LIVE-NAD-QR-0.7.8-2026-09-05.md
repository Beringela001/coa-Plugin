# COA Archive 0.7.8 Live release — 2026-09-05

Owner requested repair of the printed NAD QR typo. Released 0.7.8 on Live after the named backup `Before NAD QR and order photo fixes COA 0.7.8 OE 0.4.2 - 2026-09-05` completed.

Both `/testing/nad-500-mg/nd50026205jp` slash variants and `/testing/nad-500-mg/progress-1269/` now return 301 directly to `/testing/nad-500-mg/nd50026205js/`. The destination returned 200; a similar unrelated path returned 404. No COA data was changed.

PHP runtime redirect contract, existing JavaScript redirect contract, syntax validation, and diff whitespace checks passed. Existing numeric Retatrutide redirect remains covered. WordPress reported successful replacement of 0.7.7, and all Kinsta caches were cleared.

Companion Order Experience 0.4.2 resolves the approved historical batch typo for the exact photo, display batch, and report link. The owner's order page and its mobile NAD card were verified against the public COA photograph.

Artifact: `dist/pepselect-coa-archive-0.7.8.zip`; SHA-256 `92DAF664CC08EF4CB06F612BC66698A4417B2084068077700308F3174FD46F4B`.

Rollback: reinstall the prior 0.7.7 package and clear caches. Full-backup restoration must account for orders received since backup creation.
