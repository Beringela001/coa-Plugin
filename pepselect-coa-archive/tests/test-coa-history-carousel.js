'use strict';

const assert = require('assert');

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
console.log('COA_HISTORY_CAROUSEL_INTERACTION_TESTS=PASS');
