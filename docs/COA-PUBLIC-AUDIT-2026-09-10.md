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
- Candidate package `dist/pepselect-coa-archive-0.7.12.zip`:114 files; SHA256 `0b10a046585ec9c28d2beaa7bc7f85966aa74446876cf4d2d8bd055c8c657f28`.
- Not deployed. Live baseline remains0.7.11. Connected browser currently exposes only the in-app browser; WordPress redirects to sign-in and no authenticated MyKinsta/Chrome session is available. User was asked to reconnect/sign in while preparation continued. Staging verification, fresh Live backup, installation, cache purge and public post-deployment checks remain required.
- Prior saved0.7.11 package is the code rollback candidate. Reverify its hash before deployment. No checkout, order, payment, tracking, Control or Ops behavior changes.
