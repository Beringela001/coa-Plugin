'use strict';
const assert = require('assert');
const fs = require('fs');
const path = require('path');
const root = path.join(__dirname, '..');
const read = file => fs.readFileSync(path.join(root, file), 'utf8');
const css = read('assets/css/pepselect-coa-frontend.css');
const card = read('templates/partials/compound-card.php');
const tests = read('includes/class-coa-test-repository.php');
const compounds = read('includes/class-compound-repository.php');
const router = read('includes/class-frontend-router.php');
const cache = read('includes/class-archive-cache.php');

assert.match(css, /\.ps-coa-archive--catalog-layout \.ps-coa-compound-grid \{[^}]*align-items: stretch/);
assert.match(css, /\.ps-coa-archive--catalog-layout \.ps-coa-compound-card \{[^}]*align-self: stretch[^}]*box-sizing: border-box[^}]*height: 100%[^}]*width: 100%/);
assert.match(css, /\.ps-coa-archive--catalog-layout \.ps-coa-compound-card__body \{[^}]*display: flex[^}]*flex: 1 1 auto[^}]*flex-direction: column[^}]*min-height: 0[^}]*min-width: 0/);
assert.match(css, /\.ps-coa-archive--catalog-layout \.ps-coa-compound-card__footer \{ margin-top: auto; \}/);
assert.match(css, /@media \(max-width: 1024px\)[\s\S]*?repeat\(2, minmax\(0, 1fr\)\)/);
assert.match(css, /@media \(max-width: 640px\)[\s\S]*?\.ps-coa-archive--catalog-layout \.ps-coa-compound-grid \{ align-items: start; grid-template-columns: 1fr; \}[\s\S]*?\.ps-coa-archive--catalog-layout \.ps-coa-compound-card \{ align-self: start; height: auto; \}/);
assert.ok(!css.includes('.ps-coa-archive--catalog-layout .ps-coa-compound-card { height: 700px'));

['ps-coa-compound-card__media', 'ps-coa-compound-card__body', 'ps-coa-assurance', 'ps-coa-compact-facts', 'ps-coa-batch-preview', 'ps-coa-compound-card__footer'].forEach(value => assert.ok(card.includes(value)));
assert.ok(card.includes("array_slice( $compound['recent_batches'], 0, 3 )"));
assert.ok(!card.toLowerCase().includes('placeholder batch'));

assert.ok(tests.includes('public function archive_index'));
const archiveIndexSource = tests.slice(tests.indexOf('public function archive_index'), tests.indexOf('public function compound_ids_matching_public_batch'));
assert.strictEqual((archiveIndexSource.match(/\$this->eligible_ids\(\)/g) || []).length, 1);
assert.ok(tests.includes("'priority' => 6"));
assert.ok(tests.includes("'approved' === $outcome && 'complete' === $stage && absint( get_post_meta( $test_id, 'is_current', true ) )"));
assert.ok(tests.includes("2 + COA_Workflow::priority( $stage )"));
assert.ok(router.includes("$index = $this->tests->archive_index"));
assert.ok(!router.includes('compound_ids_matching_public_batch( $search'));
assert.ok(compounds.includes('public workflow priority, display order, display name, then post ID'));
assert.ok(compounds.includes("$left->ID <=> $right->ID"));
assert.ok(!/private function compare_compounds[\s\S]*?is_featured/.test(compounds));
assert.ok(!/private function compare_compounds[\s\S]*?get_posts\(/.test(compounds));
assert.ok(cache.includes('$priority_scope'));
assert.ok(cache.includes("add_action( 'save_post_' . Post_Types::COMPOUND"));
assert.ok(cache.includes("add_action( 'save_post_' . Post_Types::COA_TEST"));
assert.ok(cache.includes("add_action( 'acf/save_post'"));
assert.ok(cache.includes("add_action( 'before_delete_post'"));

const active = [tests, compounds, router, card].join('\n').toLowerCase();
['woocommerce_single_product_summary', 'woocommerce_after_single_product_summary', 'qrcode'].forEach(value => assert.ok(!active.includes(value)));
console.log('COA_5B_2_STATIC_TESTS=PASS');
