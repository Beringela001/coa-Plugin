const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');

const root = path.resolve(__dirname, '..');
const redirectSource = fs.readFileSync(path.join(root, 'includes', 'class-qr-redirects.php'), 'utf8');
const pluginSource = fs.readFileSync(path.join(root, 'includes', 'class-plugin.php'), 'utf8');
const upgradeSource = fs.readFileSync(path.join(root, 'includes', 'class-upgrade.php'), 'utf8');

assert.match(redirectSource, /\/testing\/nad-500-mg\/progress-1269\//);
assert.match(redirectSource, /\/testing\/nad-500-mg\/nd50026205jp\//);
assert.match(redirectSource, /\/testing\/961\//);
assert.match(redirectSource, /\/testing\/retatrutide-10mg\//);
assert.match(redirectSource, /\/testing\/961\/rt2026205jp\//);
assert.match(redirectSource, /\/testing\/retatrutide-10mg\/rt2026205jp\//);
assert.match(redirectSource, /wp_safe_redirect\( \$destination, 301/);
assert.doesNotMatch(redirectSource, /preg_match|strpos|str_contains/);
assert.match(pluginSource, /new QR_Redirects\(\)/);
assert.match(pluginSource, /\$this->qr_redirects->register_hooks\(\)/);
assert.match(upgradeSource, /get_page_by_path\( '961', OBJECT, Post_Types::COMPOUND \)/);
assert.match(upgradeSource, /'retatrutide', 'retatrutide 10mg', 'retatrutide 10 mg'/);
assert.match(upgradeSource, /10\.0 !== \$strength_value/);
assert.match(upgradeSource, /'mg' !== \$strength_unit/);
assert.match(upgradeSource, /get_page_by_path\( 'retatrutide-10mg', OBJECT, Post_Types::COMPOUND \)/);
assert.match(upgradeSource, /'post_name' => 'retatrutide-10mg'/);

console.log('COA legacy redirects are exact, permanent, and isolated.');
