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

class Style {
	constructor() { this.values = {}; this.overflow = ''; this.paddingRight = ''; }
	setProperty(name, value) { this.values[name] = value; }
	getPropertyValue(name) { return this.values[name] || ''; }
}

class Element {
	constructor(doc) { this.ownerDocument = doc; this.attrs = {}; this.listeners = {}; this.hidden = false; this.disabled = false; this.classList = new Classes(); this.style = new Style(); this.children = []; this.parentNode = null; this.clientWidth = 0; }
	addEventListener(type, callback) { (this.listeners[type] ||= []).push(callback); }
	emit(type, target = this) { (this.listeners[type] || []).forEach(callback => callback({ currentTarget: this, target, key: '', shiftKey: false, preventDefault() {} })); }
	setAttribute(name, value) { this.attrs[name] = value; }
	getAttribute(name) { return this.attrs[name] || ''; }
	removeAttribute(name) { delete this.attrs[name]; }
	focus() { this.ownerDocument.activeElement = this; }
	appendChild(child) { if (child.parentNode && child.parentNode.children) { child.parentNode.children = child.parentNode.children.filter(item => item !== child); } child.parentNode = this; if (!this.children.includes(child)) { this.children.push(child); } return child; }
}

const doc = { listeners: {}, activeElement: null };
doc.body = new Element(doc);
doc.documentElement = new Element(doc); doc.documentElement.clientWidth = 1180;
const view = { scrollX: 7, scrollY: 1640, pageXOffset: 7, pageYOffset: 1640, innerWidth: 1200, scrollCalls: [], scrollTo(x, y) { this.scrollX = x; this.scrollY = y; this.scrollCalls.push([x, y]); }, getComputedStyle(element) { return { paddingRight: '4px', getPropertyValue(name) { return element.computedVariables ? element.computedVariables[name] || '' : ''; } }; } };
doc.defaultView = view;
doc.addEventListener = (type, callback) => { (doc.listeners[type] ||= []).push(callback); };
doc.key = (key, shiftKey = false) => (doc.listeners.keydown || []).forEach(callback => callback({ key, shiftKey, preventDefault() {} }));

const overlay = new Element(doc); overlay.hidden = true;
const image = new Element(doc); const count = new Element(doc); const close = new Element(doc); const previous = new Element(doc); const next = new Element(doc);
const nodes = { '[data-ps-coa-image]': image, '[data-ps-coa-count]': count, '[data-ps-coa-close]': close, '[data-ps-coa-prev]': previous, '[data-ps-coa-next]': next };
overlay.querySelector = selector => nodes[selector];
const report = new Element(doc); report.computedVariables = { '--ps-coa-lightbox-bg': 'rgba(1, 2, 3, .94)' }; report.querySelector = selector => '[data-ps-coa-lightbox]' === selector ? overlay : null; report.appendChild(overlay);
const first = new Element(doc); first.setAttribute('data-ps-coa-full', 'page-1.jpg'); first.setAttribute('data-ps-coa-alt', 'Page one');
const second = new Element(doc); second.setAttribute('data-ps-coa-full', 'page-2.jpg'); second.setAttribute('data-ps-coa-alt', 'Page two');
const gallery = { closest: () => report, querySelectorAll: () => [first, second] };

const controller = lightbox.createGallery(gallery);
assert.ok(controller);
assert.strictEqual(lightbox.createGallery(gallery), controller, 'duplicate initialization returns the existing controller');
assert.strictEqual(first.listeners.click.length, 1, 'only one click handler is registered');
assert.strictEqual(overlay.parentNode, doc.body, 'the single overlay is moved under document.body');
assert.strictEqual(doc.body.children.filter(child => child === overlay).length, 1, 'only one body-rooted overlay exists');
assert.strictEqual(overlay.style.getPropertyValue('--ps-coa-lightbox-bg'), 'rgba(1, 2, 3, .94)', 'approved design variable survives DOM relocation');

second.emit('click');
assert.strictEqual(overlay.hidden, false);
assert.strictEqual(image.src, 'page-2.jpg');
assert.strictEqual(image.alt, 'Page two');
assert.strictEqual(count.textContent, 'Page 2 of 2');
assert.strictEqual(next.disabled, true);
assert.strictEqual(previous.disabled, false);
assert.strictEqual(doc.activeElement, close);
assert.ok(doc.body.classList.contains('ps-coa-lightbox-open'));
assert.strictEqual(doc.body.style.overflow, 'hidden');
assert.strictEqual(doc.documentElement.style.overflow, 'hidden');
assert.strictEqual(doc.body.style.paddingRight, '24px', 'scrollbar width is compensated');

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
assert.strictEqual(doc.body.style.overflow, '', 'body overflow is restored');
assert.strictEqual(doc.documentElement.style.overflow, '', 'root overflow is restored');
assert.strictEqual(doc.body.style.paddingRight, '', 'temporary scrollbar compensation is removed');
assert.deepStrictEqual(view.scrollCalls.pop(), [7, 1640], 'exact pre-open scroll position is restored');

first.emit('click');
overlay.emit('click', overlay);
assert.strictEqual(overlay.hidden, true, 'clicking the backdrop closes the viewer');

console.log('COA_LIGHTBOX_INTERACTION_TESTS=PASS');
