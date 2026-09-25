# COA product images — 0.7.19

Scope: compound/archive/history images come from the linked product; batch reports use their uploaded vial photo, falling back to the product image. No inventory, test results, product links or photo attachments are changed.

Cause: Frontend_View_Model only read woocommerce_product_image_id, a saved snapshot captured by Product_Matching::sync. Ops can create/connect the compound before the WooCommerce product image is added. The current CJC product has CJCIPA10-F.jpg, while its archive card shows neutral-vial.svg.

The display now reads the current linked product thumbnail in both compound and batch fallback paths. Existing legacy archives without an available product retain their stored fallback. Removing a current product thumbnail does not resurrect its stale snapshot. The read path does not write metadata or contact an external service.

Ops audit: ensureCoaCompoundConnection delegates creation/linking to the plugin's product connection endpoint; coaMedia writes batch_vial_photo on the test record, not compound_image_id. No Ops code/image deployment is needed for this rendering correction.

Regression coverage: photo added after connection with empty snapshot; replacement product image with stale snapshot; batch-photo upload/removal; archive remains product-owned throughout; deliberate product-image removal. Existing legacy image tests retained.

Release state: PHP syntax checks passed. Full WordPress CI, staging, backup and Live verification pending. Work uses a clean detached checkout of the current COA release branch to preserve unrelated graph changes in the earlier checkout.
