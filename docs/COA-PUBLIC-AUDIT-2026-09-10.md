# Public COA presentation audit and 0.7.12 candidate

## Scope and findings

Reviewed all 19 batch-report URLs linked from the public testing archive at desktop1440 and phone390. Scope is report presentation, hero image loading, public notes and responsive overflow; this is not a scientific revalidation of laboratory results.

- KPV KP1026232JP repeats identical wording in public notes and the separately rendered release-decision field. The prior deduplication covered only public/report notes.
- Long notices expand the outcome column, stretching the entire desktop hero and changing the phone layout through the existing note selector.
- Reta10 RT2026205JP loaded its batch photo in both viewport checks. All four advertised image variants (225,600,768,1086px) decoded successfully when opened directly. The compound-history image also loaded. Missing-image behavior is not reproduced and no replacement image or saved media data was changed.
- All19 report hero images loaded on desktop. Phone pass initially observed PT141 before its image finished; a targeted repeat confirmed complete/loaded. Reta10 and KPV images both loaded. All19 phone reports measured document width383 within viewport390.
- Other long-note examples include historical TB500, historical Reta30 and NAD500. Release-only failures include historical Reta20 and NAD500; the new card handles these too.

Raw public-page observations: `coa-refinement/public-audit-2026-09-10.json`. Its first phone snapshot includes the transient PT141 observation; successful follow-up is recorded above. No private admin/customer data is included.

## Implemented alternative

The hero retains identity, vial and status. When a notice exists, a short underlined link leads to one separate Important batch note card immediately below the hero. The card is always expanded, has a blue border,18px separation and16px semibold body text. Passed/failed colors and saved wording are preserved.

The note partial combines release-decision text for failed records with public/report notes, comparing decoded visible wording and normalized whitespace. Equivalent entries appear once; distinct disclosures remain. No database content is deleted. Ordinary reports keep their standard short status copy and omit the notice card.

Production-template previews: `coa-refinement/audit-reta.html` and `coa-refinement/audit-kpv.html`. Reta uses observed public identity values and batch image; KPV deliberately omits unknown lab/date/reference values in this layout fixture. Neither preview is a new certificate or a saved batch record.

## Verification and release state

- Focused PHP report contracts pass: placement after hero, KPV-style cross-field duplication, distinct disclosures, preserved failure state, existing measured values/status rules.
- Existing JavaScript integration and certificate-lightbox suites pass.
- Desktop1440 and phone390 visual previews inspected; phone320 KPV retains16px text and no horizontal overflow. Note link reaches the unique card anchor.
- Screenshots: `coa-refinement/audit-reta-desktop.png`, `audit-reta-mobile.png`, `audit-kpv-mobile.png`.
- Mobile follow-up: equal-width columns with vial left/status right; both cards stretch to the same row height. Verified at390px (153.5px wide each,201.125px high on Reta) and320px (123.5px wide each,290.9375px high on KPV). Neither viewport overflows horizontally. Removed obsolete inside-hero note layout overrides. Desktop rules are unchanged.
- Candidate package `dist/pepselect-coa-archive-0.7.12.zip` rebuilt after the mobile follow-up:114 files; SHA256 `7c0dc88a84608d95ffd540b7b8429da927076965f026e3e1b41eb78fa0ec4139`.
- Deployed to staging then Live September10 after the user restored authenticated Chrome access and confirmed ready. Both installed-plugin lists independently show0.7.12. Only the saved package with the hash above was uploaded.
- Live manual backups were full, newest first. Deleted only the oldest bottom entry, September8 10:25PM `Before COA overview 0.7.10 LIVE - 2026-09-08`, under existing authorization. Created `Before COA layout 0.7.12 LIVE - 2026-09-10` September10 11:14PM; Restore available confirmed before Live installation. Live caches cleared after installation.
- Ordinary public URLs serve stylesheet0.7.12. KPV retains Testing failed and its notice occurs once outside the hero. Reta10 retains Testing passed and its image loads. At390px both cards measure158.5px wide and equal height (Reta201.125px; KPV245.75px), with vial on the left. At320px no horizontal overflow and note text16px. RT30 without a note retains standard copy and no notice card. Reta10 canonical and matching product route unchanged. Certificate disclosure, viewer opening and close verified.
- Saved live captures: `coa-refinement/live-0712-mobile.png` and `live-0712-desktop.png`. Earlier image-loading complaint remains unreproduced; no batch image replacement was performed.
- Prior saved0.7.11 rollback hash reverified: `e4ba9fa9e75f461869f0e3c4a6e7af55c47fb92d8a77e63c409ca8683bebb319`. Roll back plugin files only if needed; avoid a full database restore over newer orders. No checkout, order, payment, tracking, Control or Ops behavior changes.
