const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');

class Element {
  constructor() { this.children = []; this.attributes = {}; this.events = {}; this.value = ''; this.hidden = false; this.classList = {add() {}, remove() {}}; }
  set innerHTML(value) { this.children = []; this.html = value; }
  get innerHTML() { return this.html || this.textContent || ''; }
  setAttribute(key, value) { this.attributes[key] = value; }
  getAttribute(key) { return this.attributes[key]; }
  removeAttribute(key) { delete this.attributes[key]; }
  appendChild(child) { this.children.push(child); }
  addEventListener(name, handler) { this.events[name] = handler; }
  contains(target) { return target === this || this.children.some(child => child.contains(target)); }
  querySelectorAll() { return this.children.flatMap(child => child.attributes.role === 'option' ? [child] : child.querySelectorAll()); }
  scrollIntoView() {}
}

function harness(file, coa = false) {
  const input = new Element(), form = new Element(), wrapper = new Element();
  wrapper.style = {};
  form.appendChild(input); form.appendChild(wrapper);
  form.dataset = {suggestEndpoint: '/suggest'};
  form.querySelector = selector => selector.includes('input') ? input : wrapper;
  const timers = new Map(), requests = [], navigations = [];
  let timerId = 0;
  const document = new Element();
  document.querySelector = () => form;
  document.getElementById = () => input;
  document.createElement = () => new Element();
  const window = {
    pepselectCompoundIndex: [{title: 'GHK-CU', strength: '50MG', url: '/ghk', in_stock: true}, {title: 'GLP-3 RT', strength: '10MG', keywords: 'Retatrutide', url: '/reta', in_stock: true}],
    PepSelectCoaSearch: {endpoint: '/coa'},
    location: {assign: url => navigations.push(url)},
    clearTimeout: id => timers.delete(id),
    setTimeout: handler => { timers.set(++timerId, handler); return timerId; },
  };
  vm.runInNewContext(fs.readFileSync(file, 'utf8'), {
    window, document, AbortController,
    fetch: (url, options) => new Promise(resolve => requests.push({url, options, resolve})),
  });
  const type = value => { input.value = value; input.events.input(); };
  const tick = () => { const pending = [...timers.values()]; timers.clear(); pending.forEach(fn => fn()); };
  const box = () => coa ? wrapper.children[0] : form.children.find(child => child.id === 'pepselect-header-search-suggestions');
  const reply = async (request, results) => {
    request.resolve({ok: true, json: async () => coa ? {results} : results});
    for (let i = 0; i < 5; i++) await Promise.resolve();
  };
  return {input, form, document, type, tick, box, reply, requests, navigations, timers};
}

async function verify(file, coa = false) {
  const h = harness(file, coa);
  const result = coa ? {kind: 'compound', name: 'GHK-CU', url: '/ghk'} : {title: 'Remote result', url: '/remote', in_stock: true};
  if (!coa) {
    h.type('ghk');
    assert.equal(h.box().hidden, false, 'common product names must render in the input event');
    assert.equal(h.requests.length, 0, 'local matches must not wait for the server');
    h.type('ghk-cu');
    h.input.events.keydown({key: 'ArrowDown', preventDefault() {}});
    h.input.events.keydown({key: 'Enter', preventDefault() {}});
    assert.equal(h.navigations[0], '/ghk', 'keyboard selection must open the product');
    h.type('reta');
    assert.equal(h.box().hidden, false, 'scientific names must match the public introduction immediately');
    assert.equal(h.requests.length, 0);
  }
  h.type('old'); h.tick();
  h.type('new'); h.tick();
  assert.equal(h.requests[0].options.signal.aborted, true, 'changing text must cancel previous work immediately');
  await h.reply(h.requests[1], [result]);
  await h.reply(h.requests[0], []);
  assert.equal(h.box().hidden, false, 'late old responses must not erase current results');
  h.type('');
  assert.equal(h.box().hidden, true);
  h.type('new');
  assert.equal(h.box().hidden, false, 'repeated queries should render from memory');
  assert.equal(h.requests.length, 2);
  h.type('pending'); h.tick();
  h.input.events.keydown({key: 'Escape', preventDefault() {}});
  await h.reply(h.requests[2], [result]);
  assert.equal(h.box().hidden, true, 'Escape must prevent late suggestions reopening');
  h.type('outside'); h.tick();
  h.document.events.click({target: new Element()});
  await h.reply(h.requests[3], [result]);
  assert.equal(h.box().hidden, true, 'outside taps must prevent late suggestions reopening');
  h.type('clear'); h.tick(); h.type('x');
  await h.reply(h.requests[4], [result]);
  assert.equal(h.box().hidden, true, 'clearing text must not display a stale query');
  assert.equal(h.input.getAttribute('aria-activedescendant'), undefined);
  console.log(`${coa ? 'COA' : 'Header'} search: immediate/cached results, cancellation, races and keyboard checks passed`);
}

(async () => {
  await verify(path.join(__dirname, '../assets/js/pepselect-coa-search.js'), true);
})().catch(error => { console.error(error); process.exitCode = 1; });
