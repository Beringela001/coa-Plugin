'use strict';

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const read = relative => fs.readFileSync(path.join(__dirname, '..', relative), 'utf8');

const template = read('templates/archive-testing.php');
const hero = read('templates/partials/archive-hero.php');
const card = read('templates/partials/compound-card.php');
const css = read('assets/css/pepselect-coa-frontend.css');
const compounds = read('includes/class-compound-repository.php');
const tests = read('includes/class-coa-test-repository.php');
const router = read('includes/class-frontend-router.php');
const view = read('includes/class-frontend-view-model.php');
const main = read('pepselect-coa-archive.php');

['ps-coa-archive--catalog-layout', 'partials/archive-hero.php', 'Documented Compounds', 'Certificate archive', 'Showing %1$s of %2$s compounds', 'No matching compounds', 'Clear search'].forEach(needle => assert.match(template, new RegExp(needle.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'))));
['Every batch. Every peptide.', 'Independently verified.', 'Independent labs', 'Batch-level COAs', 'Published unedited', 'role="search"', 'name="coa_search"'].forEach(needle => assert.ok(hero.includes(needle)));
['ps-coa-compound-card__media', 'wp_get_attachment_image', 'ps-coa-compound-card__body', 'ps-coa-strength', 'ps-coa-assurance', 'ps-coa-incoming-count', 'ps-coa-compact-facts', 'ps-coa-batch-preview', "array_slice( $compound['recent_batches'], 0, 3 )", 'ps-coa-compound-card__footer'].forEach(needle => assert.ok(card.includes(needle)));

assert.match(compounds, /strength_value/);
assert.match(compounds, /strength_unit/);
assert.match(compounds, /public_batch_matches/);
assert.match(tests, /compound_ids_matching_public_batch/);
assert.match(tests, /array\( 'in-testing', 'complete' \)/);
assert.match(router, /available_total/);
assert.match(router, /archive_index/);
['batch-vial-photo', 'featured-image', 'compound-image', 'local-placeholder'].forEach(source => assert.ok(view.includes(source)));
assert.match(view, /archive_image_source/);

assert.match(css, /\.ps-coa-archive--catalog-layout \.ps-coa-compound-grid \{[^}]*align-items: stretch;[^}]*repeat\(3, minmax\(0, 1fr\)\)/s);
assert.match(css, /@media \(max-width: 1024px\)[\s\S]*?\.ps-coa-archive--catalog-layout \.ps-coa-compound-grid \{ grid-template-columns: repeat\(2, minmax\(0, 1fr\)\); \}/);
assert.match(css, /@media \(max-width: 640px\)[\s\S]*?\.ps-coa-archive--catalog-layout \.ps-coa-compound-grid \{ align-items: start; grid-template-columns: 1fr; \}/);
assert.match(css, /\.ps-coa-history-carousel__control \{[^}]*aspect-ratio: 1 \/ 1;[^}]*flex: 0 0 48px;[^}]*height: 48px !important;[^}]*min-width: 48px;[^}]*width: 48px !important;/s);
assert.match(main, /Version:\s+0\.4\.0-beta\.22/);

const implementation = [compounds, tests, router, view].join('\n').toLowerCase();
assert.doesNotMatch(implementation, /woocommerce_single_product|qrcode/);
console.log('COA_CATALOG_STATIC_TESTS=PASS');
