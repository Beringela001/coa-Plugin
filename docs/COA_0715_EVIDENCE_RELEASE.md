# COA 0.7.15 — saved evidence and untested rows

Scope: latest passed KPV KP1026246JP displayed no method/cutoff despite Ops PDF import. WordPress record 1898 was inspected read-only: saved method Immunoassay, specification Immunoassay, 50 ng/mL cutoff, result Not detected. Public rendering discarded stored evidence through Report_Evidence::fentanyl(status).

Fix: pass stored method, specification and result into evidence presentation; never infer a method/cutoff from status alone. Only performed pass/fail screens expose details. Explicit Not Tested categories are omitted from shared detailed desktop/mobile rows, even with stale/default values. Units alone no longer form endotoxin results. Failed categories stay visible. Original PDFs and stored results unchanged.

Source bf08874, test expectation follow-up f349b0e. Run #25 exposed two superseded contracts (blank saved evidence, retain Not Tested rows) and a new test expecting unescaped rather than existing escaped model text; corrected without relaxing behavior checks. Run #26 35039507768 passed PHP 8.1 and 8.2. Standalone evidence contracts passed. Graphify AST update completed.

Package: dist/pepselect-coa-archive-0.7.15.zip from f349b0e. SHA256 1f95f3a0a25300b133ce5b4e2535c0225d107b1cf7e25c46875acaa73fcfe33d.

Fresh LIVE backup: September 15, 8:20 PM, Before COA 0.7.15 saved evidence and untested rows. Owner explicitly approved permanent replacement of only September 12, 8:17 AM Before Reddit paid purchases 0.3.0 LIVE; four newer copies retained.

Installation pending. Current live plugin verified 0.7.13; 0.7.15 includes prior green-tested 0.7.14 pending-vial-count fix. Browser automated file selection previously stalled repeatedly despite enabled file permissions, so owner-assisted package selection is needed. Plugin upload tab prepared. After install verify exact KPV public report: saved method/cutoff visible, heavy metals/endotoxins Not Tested rows absent, sterility/purity/content unchanged, desktop/mobile; clear caches. No Control image rebuild or deployment needed.
