const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');

const root = path.resolve(__dirname, '..');
const source = fs.readFileSync(path.join(root, 'includes', 'class-seo-sitemaps.php'), 'utf8');
const plugin = fs.readFileSync(path.join(root, 'includes', 'class-plugin.php'), 'utf8');

assert.match(source, /wpseo_exclude_from_sitemap_by_post_ids/);
assert.match(source, /wpseo_xml_sitemap_post_url/);
assert.match(source, /wpseo_sitemap_post_type_first_links/);
assert.match(source, /is_compound_public/);
assert.match(source, /is_test_public/);
assert.match(source, /find_public_by_batch_slug/);
assert.match(source, /compound_url/);
assert.match(source, /test_url/);
assert.match(source, /archive_url/);
assert.doesNotMatch(source, /update_post_meta|wp_insert_post|wp_update_post|wc_update_product_stock/);
assert.match(plugin, /new SEO_Sitemaps/);
assert.match(plugin, /seo_sitemaps->register_hooks/);

console.log('COA_SEO_SITEMAPS_STATIC_TESTS=PASS');
