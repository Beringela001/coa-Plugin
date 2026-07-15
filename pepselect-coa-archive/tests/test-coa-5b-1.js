'use strict';
const assert = require('assert');
const fs = require('fs');
const path = require('path');
const root = path.join(__dirname, '..');
const read = file => fs.readFileSync(path.join(root, file), 'utf8');
const admin = read('includes/class-product-matching-admin.php');
const adminCss = read('assets/css/pepselect-coa-admin-product-matching.css');
const fields = read('includes/class-compound-fields.php');
const validation = read('includes/class-coa-test-validation.php');
const service = read('includes/class-coa-test-service.php');
const plugin = read('includes/class-plugin.php');
const importer = read('includes/class-coa-test-importer.php');

assert.ok(admin.includes('ps-coa-product-facts ps-coa-product-facts--sidebar'));
assert.ok(admin.includes('ps-coa-product-facts__status'));
assert.ok(admin.includes('ps-coa-product-sidebar-actions'));
assert.match(adminCss, /\.ps-coa-product-facts--sidebar \{[^}]*repeat\(2, minmax\(0, 1fr\)\)[^}]*min-width: 0[^}]*width: 100%/);
assert.match(adminCss, /\.ps-coa-product-facts--sidebar \.ps-coa-product-facts__status \{[^}]*grid-column: 1 \/ -1[^}]*overflow-wrap: anywhere[^}]*width: 100%/);
assert.match(adminCss, /\.ps-coa-product-facts--sidebar \.ps-coa-match-status \{[^}]*white-space: normal/);
assert.match(adminCss, /\.ps-coa-product-sidebar-actions \.button[^}]*display: block[^}]*width: 100%/);
assert.ok(adminCss.includes('.ps-coa-product-facts.ps-coa-product-facts--sidebar { grid-template-columns: repeat(2, minmax(0, 1fr)); }'));

assert.ok(fields.includes('Controls whether this compound is eligible to appear publicly in the COA archive and Vetting History pages. Turning it off does not delete its tests or reports.'));
assert.ok(fields.includes('Gives this compound priority placement in supported archive or promotional sections. Featured does not control whether the compound is publicly visible.'));
assert.ok(validation.includes("array( 'submitted-to-lab', 'in-testing', 'complete' )"));
assert.ok(!validation.includes("array( 'waiting-on-vendor', 'submitted-to-lab', 'in-testing', 'complete' )"));
assert.ok(validation.includes("'batch_number' === $name && in_array( $stage, array( 'in-testing', 'complete' )"));
assert.ok(validation.includes("'batch_vial_photo' === $name && ! $raw && in_array( $stage, array( 'in-testing', 'complete' )"));
assert.ok(validation.includes("'batch_identity_photos' === $name && ! $this->valid_images"));

assert.ok(service.includes('private function synchronize_title'));
assert.ok(service.includes("in_array( $stage, array( 'in-testing', 'complete' ), true )"));
assert.ok(service.includes("__( '%1$s — Batch %2$s'"));
assert.ok(service.includes("if ( $title === $post->post_title ) { return; }"));
assert.ok(service.includes("$update['post_name'] = $post->post_name"));
assert.ok(service.includes('public function after_compound_save'));
assert.ok(plugin.includes("add_action( 'acf/save_post', array( $this->coa_test_service, 'after_save' ), 30 )"));
assert.ok(plugin.includes("add_action( 'acf/save_post', array( $this->coa_test_service, 'after_compound_save' ), 35 )"));
assert.ok(importer.includes("'compound_id', 'batch_number', 'internal_batch_id', 'workflow_stage'"));
assert.ok(importer.includes("$map = array( 'coa_status' => 'field_ps_coa_test_status' )"));
assert.ok(importer.includes('Nothing is saved or published until you use the normal WordPress controls.'));

['woocommerce_after_single_product_summary', 'woocommerce_single_product_summary', 'wp_unique_post_slug'].forEach(value => {
	assert.ok(!admin.includes(value) && !service.includes(value), 'forbidden behavior: ' + value);
});
console.log('COA_5B_1_STATIC_TESTS=PASS');
