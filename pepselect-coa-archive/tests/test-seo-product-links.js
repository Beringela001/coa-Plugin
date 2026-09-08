'use strict';

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const read = relative => fs.readFileSync(path.join(__dirname, '..', relative), 'utf8');

const card = read('templates/partials/compound-card.php');
const historyHero = read('templates/partials/history-hero.php');
const viewModel = read('includes/class-frontend-view-model.php');
const css = read('assets/css/pepselect-coa-frontend.css');
const plugin = read('pepselect-coa-archive.php');

for (const template of [card, historyHero]) {
	assert.ok(template.includes("$compound['woocommerce_product_url']"));
	assert.ok(template.includes("esc_url( $compound['woocommerce_product_url'] )"));
	assert.ok(template.includes('View compound details'));
}

assert.ok(viewModel.includes("'woocommerce_product_url' => $this->product_url( $product_id )"));
assert.match(viewModel, /'product' === \$post->post_type && 'publish' === \$post->post_status/);
assert.match(css, /\.ps-coa-compound-card__footer \{[^}]*display: flex;[^}]*flex-wrap: wrap;[^}]*justify-content: space-between;/s);
assert.ok(historyHero.includes('ps-coa-history-hero__description'));
assert.match(css, /\.ps-coa-history-hero__product-link \{[^}]*margin-top: \.75rem;/s);
const headerVersion = plugin.match(/Version:\s+(\d+\.\d+\.\d+)(?:\s|$)/);
const runtimeVersion = plugin.match(/PEPSELECT_COA_ARCHIVE_VERSION', '([^']+)'/);
assert.ok(headerVersion && runtimeVersion, 'Plugin version declarations missing');
assert.strictEqual(headerVersion[1], runtimeVersion[1], 'Plugin version declarations disagree');

console.log('SEO_PRODUCT_LINK_TESTS=PASS');
