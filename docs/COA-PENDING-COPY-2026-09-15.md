# COA 0.7.17 — consistent pending wording

Incoming history cards and the alternate incoming partial now use `public_status_label` as their heading, without a redundant status pill. The alternate partial keeps actual batch identifiers as metadata. Archive recent-batch pills, batchless breadcrumbs and previous/next links use the same configured public label. Internal workflow values, product stock pills, product-carousel stage copy, scientific outcomes and customer notes are unchanged. Existing saved wording is not rewritten.

Implementation: `6307bad`; fixture correction: `73e04cd`. Regression checks all four pending stages through actual compound-history and archive preview rendering. Initial CI failed because submitted/in-testing test fixtures lacked required public evidence; fixtures now provide expected date, laboratory and the required in-testing batch number. Production visibility rules were not changed. GitHub run `35047001160` passes both PHP 8.1 and 8.2. Syntax check: 124 PHP files passed. Graph refreshed.

Package: `dist/pepselect-coa-archive-0.7.17.zip`, 117 runtime files, SHA256 `6a7683a6051f211f407591153cda2d2f6dfef2bd7be73ccd09ef2fa2bc1daf95`. Rollback is the retained, previously verified 0.7.16 package.

Staging upgraded 0.7.16 to 0.7.17. Unsaved custom heading preview rendered as history h3 with zero redundant pills, and matched archive batch-pill text. Desktop/mobile preview modes checked; draft discarded without changing saved settings.

Under existing backup/deployment authorization, rotated only the oldest manual backup (September 15, 2:17 AM, Before Insights 0.3.0 relaxed customer tracking LIVE - Sep15). Retained four newer backups. Fresh backup “Before COA 0.7.17 incoming copy LIVE - Sep15” completed at 10:13 PM, Restore to available, before Live update.

Live active plugin verified 0.7.17; Kinsta cache clear requested. Normal public Retatrutide 10mg history now shows “We have a new batch coming to us.” as incoming heading, zero incoming status pills. Normal `/testing/` archive has the same text in its pending batch pill. Original Reta 10 customer disclosure verified unchanged. No Live copy settings, report data, commerce or Ops records were edited.
