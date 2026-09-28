# Customer note visibility

Prepared a separate white callout inside the existing status panel for custom public/report notes. Includes an information icon, Important batch note heading, blue left border, 16px semibold body text and paragraph spacing. Standard status copy remains unchanged. Passed/failed status and saved notice wording remain unchanged; duplicate public/report notes are still deduplicated.

Preview: `coa-refinement/note-review.html`. The full RT10 public notice is used verbatim; other surrounding metadata and the representative vial image are local fixture values, not a revised batch record. No customer email or personal information is included in artifacts.

Desktop1440 and phone390 visually checked. Phone390/320 computed note text is16px/weight600, with no horizontal overflow. Report refinement contracts cover labeled callout, standard-copy exclusion, failure status preservation and deduplication.

Deployed as 0.7.11 to staging, then Live on September 9, 2026 after the user's approval. Both plugin lists independently confirmed 0.7.11. The ordinary public RT10 URL serves the new callout and stylesheet version 0.7.11. Live desktop1440 and phone390 screenshots are saved as `coa-refinement/note-live-desktop.png` and `coa-refinement/note-live-mobile.png`; phone320 also retains16px/weight600 without horizontal overflow. RT30 standard copy has no custom-note callout. RT20 retains Testing failed and its separate release-decision explanation; no saved report data was changed.

Package: `dist/pepselect-coa-archive-0.7.11.zip`, 113 files, SHA256 `e4ba9fa9e75f461869f0e3c4a6e7af55c47fb92d8a77e63c409ca8683bebb319`.

Fresh Live backup completed at September9 11:27PM: `Before COA note 0.7.11 LIVE - 2026-09-09`. Five slots were full; only the oldest manual backup, September8 8:16PM `Before Live heatmap activation - 2026-09-08`, was deleted under existing authorization. Live caches cleared after installation.

Rollback package retained and hash verified: `dist/pepselect-coa-archive-0.7.10.zip`, SHA256 `df7aa58858ce5e11afdc11369969613a4faef53476d7e5339b1e68fe5cf6ac68`. Roll back plugin files only if needed; avoid restoring the entire database over newer orders. No order, tracking, or Control/Ops changes were included.

Source commit `4061187`; deployment record/version commit `6b9b6c9`. Live deployment is complete. Automatic approval review initially blocked GitHub publication. Paulo then explicitly granted full local and GitHub authority in direct response to the question naming public `Beringela001/coa-Plugin`, branch `codex/coa-report-redesign`, and source changes, release notes and screenshots of public report pages. Publication succeeded after that approval. The earlier block is resolved; no alternate publication method was used. No merge to main or additional Live deployment is part of this publication.
