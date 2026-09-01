const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');

const root = path.resolve(__dirname, '..');
const read = file => fs.readFileSync(path.join(root, file), 'utf8');

const validation = read('includes/class-coa-test-validation.php');
const router = read('includes/class-frontend-router.php');
const plugin = read('includes/class-plugin.php');
const view = read('includes/class-frontend-view-model.php');
const table = read('templates/partials/full-qc-results-table.php');

assert.ok(validation.includes("preg_replace( '/<([0-9]+)>/'"), 'scientific numeric references are protected');
assert.ok(validation.includes('sanitize_text_field( $protected )'), 'ordinary HTML is still sanitized');
for (const obsoleteGate of [
  'Original COA PDF is required before saving an Approved completed report.',
  'At least one Certificate Page Image is required before saving an Approved completed report.',
  'Lab Report URL is required before saving an Approved completed report.',
  'Release Decision Note is required before saving a Failed completed report.',
  'Batch Vial Photo is required before saving a Completed test.'
]) assert.ok(!validation.includes(obsoleteGate), `removed publication gate: ${obsoleteGate}`);

assert.ok(plugin.includes("add_filter( 'post_type_link'"), 'wp-admin view links are filtered');
assert.ok(router.includes("wp_safe_redirect( $context['canonical'], 301 )"), 'raw COA URLs redirect to the canonical report');
assert.ok(table.includes('Laboratory evidence'), 'results table uses conditional evidence');
assert.ok(!table.includes('&mdash;'), 'results table no longer fabricates empty dash cells');
assert.ok(view.includes('stripos( $endotoxin_result, $endotoxin_unit )'), 'endotoxin units are not duplicated');

console.log('COA 0.7.7 integration checks passed');
