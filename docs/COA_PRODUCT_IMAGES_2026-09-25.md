# COA product images — 0.7.19

Scope: compound/archive/history images come from the linked product; batch reports use their uploaded vial photo, falling back to the product image. No inventory, test results, product links or photo attachments are changed.

Cause: Frontend_View_Model only read woocommerce_product_image_id, a saved snapshot captured by Product_Matching::sync. Ops can create/connect the compound before the WooCommerce product image is added. The current CJC product has CJCIPA10-F.jpg, while its archive card shows neutral-vial.svg.

The display now reads the current linked product thumbnail in both compound and batch fallback paths. Existing legacy archives without an available product retain their stored fallback. Removing a current product thumbnail does not resurrect its stale snapshot. The read path does not write metadata or contact an external service.

Ops audit: ensureCoaCompoundConnection delegates creation/linking to the plugin's product connection endpoint; coaMedia writes batch_vial_photo on the test record, not compound_image_id. No Ops code/image deployment is needed for this rendering correction.

Regression coverage: photo added after connection with empty snapshot; replacement product image with stale snapshot; batch-photo upload/removal; archive remains product-owned throughout; deliberate product-image removal. Existing legacy image tests retained.

Release state: PHP syntax checks passed. Full WordPress CI, staging, backup and Live verification pending. Work uses a clean detached checkout of the current COA release branch to preserve unrelated graph changes in the earlier checkout.

Source 2b76222490078c1c66b471e4c7415675dd078ec6 pushed to codex/coa-report-redesign. Actions #37: https://github.com/Beringela001/coa-Plugin/actions/runs/36162721641. PHP 8.2 passed (335 tests, 1723 assertions, five existing optional-dependency skips); PHP 8.1 still running at handoff. Product Matching confirms CJC product 2318 / CJCIPA10 is connected; no link repair or business-record write was performed.

Package: dist/pepselect-coa-archive-0.7.19.zip, 120 files; SHA256 57500d7c74b75b40cd0bed330ae89649d9161ef78d1e90b6b9d1e754a8709f89. Current Live version remains 0.7.18. MyKinsta is signed out; owner asked to sign in before backup/staging/deployment. Do not install until remaining CI and backup/recovery checks pass. Root Graphify AST update completed. No Ops release is needed.

## Deployed September 25, 2026

Actions #37 completed successfully on both PHP 8.1 and 8.2. The identical hash-verified package replaced 0.7.18 on staging and then Live; both WordPress installers confirmed success. Staging archive product images and the GHK-CU batch-specific uploaded photo were verified separately.

Owner explicitly authorized permanent removal of only the September 21, 9:19 PM manual backup, “Before payment follow-up 0.1.7 LIVE Sep21.” Kinsta confirmed deletion; the four newer backups remained. Fresh Live backup “Before COA 0.7.19 product image fix LIVE Sep25” is listed September 25, 5:41 PM with Restore to enabled, verified before live installation. Rollback source package for 0.7.18 is retained in dist; no schema/data migration was made.

Live verification: the CJC archive card and compound history use CJCIPA10-F.jpg; pending report progress-2326 uses that product image as its fallback. Existing KPV KP1026246JP still uses KP1026246JP-cleaned.webp as its actual batch vial photo. Loaded-image checks passed on both detail pages. Kinsta reports all caches cleared; existing /product/, /testing/, /shop/ purge groups remain unchanged. Verification used normal signed-in browser URLs, not cache-busting URLs. A 390px viewport request was not honored (actual innerWidth 958), so mobile-specific verification is not claimed. No inventory, scientific results, product links, or batch photo records changed.
