'use strict';

const assert = require('assert');
const lightbox = require('../assets/js/pepselect-coa-lightbox.js');

assert.strictEqual(lightbox.wrapIndex(0, 3), 0);
assert.strictEqual(lightbox.wrapIndex(3, 3), 0);
assert.strictEqual(lightbox.wrapIndex(-1, 3), 2);
assert.strictEqual(lightbox.wrapIndex(8, 3), 2);
assert.strictEqual(lightbox.wrapIndex(4, 0), 0);
assert.strictEqual(typeof lightbox.createGallery, 'function');
assert.strictEqual(typeof lightbox.init, 'function');

console.log('COA lightbox static tests passed.');
