(function () {
	'use strict';
	const form = document.getElementById('ps-coa-design-form');
	if (!form || !window.PepSelectCoaDesign) return;
	form.inert = true;
	const config = window.PepSelectCoaDesign;
	const status = document.getElementById('ps-coa-design-status');
	const save = document.getElementById('ps-coa-design-save');
	const discard = document.getElementById('ps-coa-design-discard');
	const select = document.getElementById('ps-coa-preview-page');
	const frame = document.getElementById('ps-coa-design-preview');
	const viewport = frame.parentElement;
	function fitPreview() {
		const width = Number(frame.width);
		const scale = Math.min(1, viewport.clientWidth / width);
		frame.style.transform = 'scale(' + scale + ')';
		frame.style.left = Math.max(0, (viewport.clientWidth - width * scale) / 2) + 'px';
		frame.height = Math.max(600, Math.max(340, window.innerHeight - 300) / scale);
		viewport.style.height = Number(frame.height) * scale + 'px';
	}
	new ResizeObserver(fitPreview).observe(viewport);
	window.addEventListener('resize', fitPreview);
	let saved, fields, revision, dirty = false, timer, sequence = 0, busy = false;
	const control = key => document.getElementById('ps-coa-' + key);
	async function request(path, method, body) {
		const response = await fetch(config.endpoint + path, {
			method, credentials: 'same-origin', cache: 'no-store',
			headers: {'Content-Type': 'application/json', 'X-WP-Nonce': config.nonce},
			...(body ? {body: JSON.stringify(body)} : {})
		});
		const data = await response.json();
		if (!response.ok) throw new Error(data.message || 'Request failed. Reload and try again.');
		return data;
	}
	function values() {
		const result = {};
		Object.keys(fields).forEach(key => {
			if (fields[key].readonly) return;
			const input = control(key);
			result[key] = input.type === 'checkbox' ? (input.checked ? 1 : 0) : input.value;
		});
		return result;
	}
	function populate(settings) {
		Object.keys(fields).forEach(key => {
			const input = control(key);
			if (input.type === 'checkbox') input.checked = !!Number(settings[key]);
			else input.value = settings[key];
		});
	}
	function buttons() { save.disabled = busy || !dirty; discard.disabled = busy || !dirty; }
	async function preview() {
		const mine = ++sequence;
		const [view, id] = select.value.split(':');
		frame.setAttribute('aria-busy', 'true');
		try {
			const data = await request('/preview', 'POST', {settings: values(), view, id: Number(id)});
			if (mine !== sequence) return;
			frame.srcdoc = data.html;
			status.textContent = dirty ? 'Unsaved preview — click Save to publish.' : 'Preview matches saved settings.';
		} catch (error) {
			if (mine === sequence) status.textContent = 'Preview unavailable: ' + error.message;
		} finally { if (mine === sequence) frame.setAttribute('aria-busy', 'false'); }
	}
	function changed() {
		if (!saved || busy) return;
		dirty = Object.entries(values()).some(([key, value]) => String(value) !== String(saved[key]));
		buttons(); status.textContent = dirty ? 'Unsaved changes. Updating preview…' : 'Updating preview…';
		clearTimeout(timer); ++sequence; timer = setTimeout(preview, 300);
	}
	form.addEventListener('submit', event => event.preventDefault());
	form.addEventListener('input', event => { if (event.target.id !== 'ps-coa-setting-search') changed(); });
	form.addEventListener('change', event => { if (event.target.id !== 'ps-coa-setting-search') changed(); });
	document.getElementById('ps-coa-setting-search').addEventListener('input', event => {
		const term = event.target.value.trim().toLowerCase();
		form.querySelectorAll('.ps-coa-editor-field').forEach(field => { field.hidden = !!term && !field.textContent.toLowerCase().includes(term); });
		form.querySelectorAll('details').forEach((section, index) => {
			section.hidden = !section.querySelector('.ps-coa-editor-field:not([hidden])');
			section.open = !!term || index === 0;
		});
	});
	select.addEventListener('change', preview);
	document.querySelectorAll('[data-width]').forEach(button => button.addEventListener('click', () => {
		frame.style.width = button.dataset.width + 'px'; frame.width = button.dataset.width;
		fitPreview();
		document.querySelectorAll('[data-width]').forEach(item => item.setAttribute('aria-pressed', String(item === button)));
	}));
	discard.addEventListener('click', () => { populate(saved); dirty = false; buttons(); clearTimeout(timer); preview(); });
	save.addEventListener('click', async () => {
		if (!dirty || busy) return;
		busy = true; buttons(); form.inert = true; status.textContent = 'Saving…';
		clearTimeout(timer); ++sequence;
		try {
			const data = await request('', 'PATCH', {settings: values(), revision});
			saved = data.settings; revision = data.revision; populate(saved); dirty = false;
			status.textContent = 'Saved. Public COA settings updated.'; preview();
		} catch (error) { status.textContent = 'Not saved: ' + error.message; }
		finally { busy = false; form.inert = false; buttons(); }
	});
	window.addEventListener('beforeunload', event => { if (dirty) { event.preventDefault(); event.returnValue = ''; } });
	request('', 'GET').then(data => {
		saved = data.settings; fields = data.fields; revision = data.revision; populate(saved);
		select.replaceChildren(...data.choices.map(choice => {
			const option = document.createElement('option'); option.value = choice.view + ':' + choice.id; option.textContent = choice.label; return option;
		}));
		const report = data.choices.find(choice => choice.view === 'report');
		if (report) select.value = report.view + ':' + report.id;
		select.disabled = false; form.inert = false; preview();
	}).catch(error => { status.textContent = 'Editor unavailable: ' + error.message; form.inert = true; });
}());
