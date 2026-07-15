'use strict';

const assert = require('assert');
const fs = require('fs');
const path = require('path');

class Element {
	constructor() { this.dataset = {}; this.listeners = {}; this.hidden = false; this.disabled = false; this.attrs = {}; this.textContent = ''; }
	addEventListener(type, callback) { (this.listeners[type] ||= []).push(callback); }
	emit(type, event = {}) { (this.listeners[type] || []).forEach(callback => callback(Object.assign({ key: '', preventDefault() {} }, event))); }
	setAttribute(name, value) { this.attrs[name] = value; }
}

const slides = [new Element(), new Element(), new Element()];
const previous = new Element(); const next = new Element(); const status = new Element(); const root = new Element();
root.querySelectorAll = selector => selector === '[data-ps-history-slide]' ? slides : [];
root.querySelector = selector => ({ '[data-ps-history-previous]': previous, '[data-ps-history-next]': next, '[data-ps-history-status]': status }[selector] || null);

global.document = { readyState: 'complete', querySelectorAll: selector => selector === '[data-ps-history-carousel]' ? [root] : [] };
require('../assets/js/pepselect-coa-history-carousel.js');

assert.strictEqual(slides[0].hidden, false);
assert.strictEqual(slides[1].hidden, true);
assert.strictEqual(previous.disabled, true);
assert.strictEqual(next.disabled, false);
assert.strictEqual(status.textContent, 'Report 1 of 3');

next.emit('click');
assert.strictEqual(slides[0].hidden, true);
assert.strictEqual(slides[1].hidden, false);
assert.strictEqual(status.textContent, 'Report 2 of 3');

root.emit('keydown', { key: 'ArrowRight' });
assert.strictEqual(slides[2].hidden, false);
assert.strictEqual(next.disabled, true);
root.emit('keydown', { key: 'ArrowLeft' });
assert.strictEqual(slides[1].hidden, false);

assert.strictEqual(root.dataset.psHistoryReady, 'true');
assert.strictEqual(next.listeners.click.length, 1);

const read = relative => fs.readFileSync(path.join(__dirname, '..', relative), 'utf8');
const css = read('assets/css/pepselect-coa-frontend.css');
const carousel = read('templates/partials/history-previous-carousel.php');
const card = read('templates/partials/history-previous-card.php');
const script = read('assets/js/pepselect-coa-history-carousel.js');
const router = read('includes/class-frontend-router.php');

assert.match(css, /\.ps-coa-history-carousel__control \{[^}]*font-size: 1\.5rem;[^}]*height: 48px;[^}]*top: calc\(50% - 24px\);[^}]*width: 48px;/s);
assert.match(css, /\.ps-coa-history-previous__results li strong \{[^}]*font-size: \.58rem;/s);
assert.match(css, /\.ps-coa-history-previous__results li small \{[^}]*font-size: \.56rem;[^}]*line-height: 1\.35;[^}]*overflow: hidden;[^}]*text-overflow: ellipsis;[^}]*white-space: nowrap;/s);
assert.strictEqual((carousel.match(/data-ps-history-previous/g) || []).length, 1);
assert.strictEqual((carousel.match(/data-ps-history-next/g) || []).length, 1);
['ps-coa-history-previous__identity', 'ps-coa-history-previous__results', 'ps-coa-history-previous__actions'].forEach(className => assert.strictEqual((card.match(new RegExp(className, 'g')) || []).length, 1));
assert.match(css, /\.ps-coa button:focus-visible/);
assert.doesNotMatch(script, /setInterval/);
assert.match(router, /array_slice\( \$previous_all, 0, 10 \)/);
console.log('COA_HISTORY_CAROUSEL_INTERACTION_TESTS=PASS');
