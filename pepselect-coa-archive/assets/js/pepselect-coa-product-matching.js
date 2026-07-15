(function () {
	'use strict';
	var config = window.PepSelectCOAProductMatching || {};

	function text(value) { return null == value || '' === value ? '—' : String(value); }
	function element(tag, className, content) { var node = document.createElement(tag); if (className) { node.className = className; } if (undefined !== content) { node.textContent = content; } return node; }

	function initSearch(root) {
		var query = root.querySelector('[data-ps-coa-product-query]');
		var button = root.querySelector('[data-ps-coa-product-search]');
		var results = root.querySelector('[data-ps-coa-product-results]');
		var input = root.querySelector('[data-ps-coa-product-id]');
		var selection = root.querySelector('[data-ps-coa-product-selection]');
		if (!query || !button || !results || !input || !selection) { return; }

		function selectProduct(product) {
			input.value = product.id;
			selection.replaceChildren();
			var title = element('strong', '', product.title);
			var detail = element('span', '', 'SKU ' + text(product.sku) + ' · Product ID ' + product.id + ' · ' + text(product.status) + (product.strength && product.strength.value ? ' · ' + product.strength.value + ' ' + product.strength.unit : ''));
			selection.append(title, detail);
			results.replaceChildren();
		}

		function render(products) {
			results.replaceChildren();
			if (!products.length) { results.append(element('p', 'description', config.noResults || 'No matching products found.')); return; }
			var list = element('ul', 'ps-coa-product-results');
			products.forEach(function (product) {
				var item = element('li'); var choose = element('button', 'button-link'); choose.type = 'button';
				choose.append(element('strong', '', product.title), element('span', '', 'SKU ' + text(product.sku) + ' · Product ID ' + product.id + ' · ' + text(product.status) + (product.strength && product.strength.value ? ' · ' + product.strength.value + ' ' + product.strength.unit : '')));
				choose.addEventListener('click', function () { selectProduct(product); }); item.append(choose); list.append(item);
			}); results.append(list);
		}

		function search() {
			var value = query.value.trim(); if (!value) { query.focus(); return; }
			button.disabled = true; results.replaceChildren(element('p', 'description', config.searching || 'Searching…'));
			var body = new URLSearchParams({ action: 'pepselect_coa_search_products', nonce: config.nonce || '', query: value });
			fetch(config.ajaxUrl, { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' }, body: body.toString() })
				.then(function (response) { return response.json(); })
				.then(function (payload) { render(payload && payload.success && payload.data ? payload.data.products || [] : []); })
				.catch(function () { render([]); })
				.finally(function () { button.disabled = false; });
		}
		button.addEventListener('click', search);
		query.addEventListener('keydown', function (event) { if ('Enter' === event.key) { event.preventDefault(); search(); } });
	}

	document.querySelectorAll('[data-ps-coa-product-matching]').forEach(initSearch);

	document.querySelectorAll('[data-ps-coa-connect-existing]').forEach(function (button) {
		button.addEventListener('click', function () {
			var select = button.parentNode.querySelector('[data-ps-coa-compound-choice]');
			if (!select || !select.value) { select && select.focus(); return; }
			var url = new URL(button.getAttribute('data-action-base'), window.location.href); url.searchParams.set('compound_id', select.value); window.location.assign(url.toString());
		});
	});

	var selectAll = document.querySelector('[data-ps-coa-select-all]');
	if (selectAll) { selectAll.addEventListener('change', function () { document.querySelectorAll('[data-ps-coa-product-checkbox]').forEach(function (checkbox) { checkbox.checked = selectAll.checked; }); }); }
	document.querySelectorAll('[data-ps-coa-bulk-form]').forEach(function (form) {
		form.addEventListener('submit', function (event) {
			var action = form.querySelector('[name="bulk_action"]'); var selected = form.querySelectorAll('[data-ps-coa-product-checkbox]:checked');
			if (!action || !action.value || !selected.length || !window.confirm((config.bulkConfirm || 'Continue?') + '\n\n' + selected.length + ' selected product(s).')) { event.preventDefault(); }
		});
	});
}());
