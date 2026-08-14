const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');

const root = path.resolve(__dirname, '..');
const redirectSource = fs.readFileSync(path.join(root, 'includes', 'class-qr-redirects.php'), 'utf8');
const pluginSource = fs.readFileSync(path.join(root, 'includes', 'class-plugin.php'), 'utf8');

assert.match(redirectSource, /\/testing\/nad-500-mg\/progress-1269\//);
assert.match(redirectSource, /\/testing\/nad-500-mg\/nd50026205jp\//);
assert.match(redirectSource, /wp_safe_redirect\( \$destination, 301/);
assert.doesNotMatch(redirectSource, /preg_match|strpos|str_contains/);
assert.match(pluginSource, /new QR_Redirects\(\)/);
assert.match(pluginSource, /\$this->qr_redirects->register_hooks\(\)/);

console.log('NAD500 QR redirect is exact, permanent, and isolated.');
