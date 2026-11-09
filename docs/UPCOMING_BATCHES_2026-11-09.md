# Upcoming batches

Reviewed 2026-11-09. Candidate 0.7.29 builds on 729f3ff (0.7.28), verified installed on Staging and Live before packaging. Deployment pending.

Control owns FIFO. New `is_upcoming` metadata identifies passed, released, non-current lots with inventory. Uploading a newer report cannot choose the sales batch. On a FIFO switch Control clears the old current flag, recalculates waiting lots and marks the selected lot current with upcoming cleared. Existing read-back verification and unresolved-switch handling remain.

Plugin renders waiting approved reports under Upcoming Batches with Testing passed, separate from Previous Reports. Report URLs, product mappings, QR routes, original certificates and recent SEO/redirect fixes remain unchanged. Existing records with no upcoming metadata retain their previous behavior; Control must resync already-passed waiting lots after deployment.

Release order: plugin Staging verification, plugin Live, then Control verified build and deployment, then resync the already-passed BPC lot. Confirm BP1026232JP stays current and BP1026295JP is upcoming. Do not consume stock or force a FIFO switch for a live test.

Local verification: Control 60 test files / 1,370 tests passed. Plugin PHP syntax, carousel static and report-navigation contracts passed. Native WordPress CI and deployed website checks pending. No production records changed.
