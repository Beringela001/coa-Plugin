'use strict';

const assert = require('assert');
const lightbox = require('../assets/js/pepselect-coa-lightbox.js');

assert.strictEqual(lightbox.wrapIndex(3, 3), 0);
assert.strictEqual(lightbox.wrapIndex(-1, 3), 2);
assert.strictEqual(lightbox.clampIndex(-1, 3), 0);
assert.strictEqual(lightbox.clampIndex(8, 3), 2);
assert.strictEqual(lightbox.clampIndex(1, 3), 1);

class Classes {
	constructor() { this.values = new Set(); }
	add(value) { this.values.add(value); }
	remove(value) { this.values.delete(value); }
	contains(value) { return this.values.has(value); }
}

class Element {
	constructor(doc) { this.ownerDocument = doc; this.attrs = {}; this.listeners = {}; this.hidden = false; this.disabled = false; this.classList = new Classes(); }
	addEventListener(type, callback) { (this.listeners[type] ||= []).push(callback); }
	emit(type, target = this) { (this.listeners[type] || []).forEach(callback => callback({ currentTarget: this, target, key: '', shiftKey: false, preventDefault() {} })); }
	setAttribute(name, value) { this.attrs[name] = value; }
	getAttribute(name) { return this.attrs[name] || ''; }
	removeAttribute(name) { delete this.attrs[name]; }
	focus() { this.ownerDocument.activeElement = this; }
}

const doc = { listeners: {}, activeElement: null };
doc.body = new Element(doc);
doc.addEventListener = (type, callback) => { (doc.listeners[type] ||= []).push(callback); };
doc.key = (key, shiftKey = false) => (doc.listeners.keydown || []).forEach(callback => callback({ key, shiftKey, preventDefault() {} }));

const overlay = new Element(doc); overlay.hidden = true;
const image = new Element(doc); const count = new Element(doc); const close = new Element(doc); const previous = new Element(doc); const next = new Element(doc);
const nodes = { '[data-ps-coa-image]': image, '[data-ps-coa-count]': count, '[data-ps-coa-close]': close, '[data-ps-coa-prev]': previous, '[data-ps-coa-next]': next };
overlay.querySelector = selector => nodes[selector];
const report = { querySelector: selector => '[data-ps-coa-lightbox]' === selector ? overlay : null };
const first = new Element(doc); first.setAttribute('data-ps-coa-full', 'page-1.jpg'); first.setAttribute('data-ps-coa-alt', 'Page one');
const second = new Element(doc); second.setAttribute('data-ps-coa-full', 'page-2.jpg'); second.setAttribute('data-ps-coa-alt', 'Page two');
const gallery = { closest: () => report, querySelectorAll: () => [first, second] };

const controller = lightbox.createGallery(gallery);
assert.ok(controller);
assert.strictEqual(lightbox.createGallery(gallery), controller, 'duplicate initialization returns the existing controller');
assert.strictEqual(first.listeners.click.length, 1, 'only one click handler is registered');

second.emit('click');
assert.strictEqual(overlay.hidden, false);
assert.strictEqual(image.src, 'page-2.jpg');
assert.strictEqual(image.alt, 'Page two');
assert.strictEqual(count.textContent, 'Page 2 of 2');
assert.strictEqual(next.disabled, true);
assert.strictEqual(previous.disabled, false);
assert.strictEqual(doc.activeElement, close);
assert.ok(doc.body.classList.contains('ps-coa-lightbox-open'));

controller.previous();
assert.strictEqual(count.textContent, 'Page 1 of 2');
assert.strictEqual(previous.disabled, true);
doc.key('ArrowRight');
assert.strictEqual(count.textContent, 'Page 2 of 2');

overlay.emit('click', image);
assert.strictEqual(overlay.hidden, false, 'clicking the image does not close the viewer');
doc.key('Escape');
assert.strictEqual(overlay.hidden, true);
assert.strictEqual(doc.activeElement, second, 'focus returns to the launching preview');
assert.ok(!doc.body.classList.contains('ps-coa-lightbox-open'));

first.emit('click');
overlay.emit('click', overlay);
assert.strictEqual(overlay.hidden, true, 'clicking the backdrop closes the viewer');

console.log('COA_LIGHTBOX_INTERACTION_TESTS=PASS');
