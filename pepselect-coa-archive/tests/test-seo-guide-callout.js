'use strict';

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const read = relative => fs.readFileSync(path.join(__dirname, '..', relative), 'utf8');

const archive = read('templates/archive-testing.php');
const css = read('assets/css/pepselect-coa-frontend.css');

assert.ok(archive.includes('ps-coa-guide-callout'));
assert.ok(archive.includes('What should you look for in a COA?'));
assert.ok(archive.includes('Read the COA Guide'));
assert.ok(archive.includes("home_url( '/guides/how-to-review-research-peptide-documentation/' )"));
assert.match(archive, /aria-labelledby="ps-coa-guide-callout-title"/);
assert.match(css, /\.ps-coa-guide-callout \{[^}]*grid-template-columns: 48px minmax\(0, 1fr\) auto;/s);
assert.match(css, /@media \(max-width: 640px\)[\s\S]*?\.ps-coa-guide-callout \{[^}]*grid-template-columns: 40px minmax\(0, 1fr\);/s);
assert.match(css, /\.ps-coa-guide-callout__link:focus-visible/);

console.log('SEO_GUIDE_CALLOUT_TESTS=PASS');
