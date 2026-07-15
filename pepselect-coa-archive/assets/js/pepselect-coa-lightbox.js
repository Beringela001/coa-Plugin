(function (root, factory) {
	'use strict';
	var api = factory();
	if (typeof module === 'object' && module.exports) { module.exports = api; }
	else { root.PepSelectCOALightbox = api; }
}(typeof window !== 'undefined' ? window : this, function () {
	'use strict';

	function wrapIndex(index, length) {
		if (!length) { return 0; }
		return ((index % length) + length) % length;
	}

	function clampIndex(index, length) {
		if (!length) { return 0; }
		return Math.max(0, Math.min(index, length - 1));
	}

	function createGallery(gallery) {
		if (!gallery) { return null; }
		var report = gallery.closest ? gallery.closest('.ps-coa-report') : gallery.parentNode;
		var lightbox = report && report.querySelector ? report.querySelector('[data-ps-coa-lightbox]') : null;
		var triggers = Array.prototype.slice.call(gallery.querySelectorAll('[data-ps-coa-full]'));
		if (!triggers.length || !lightbox) { return null; }
		if (lightbox.__pepSelectCOALightboxController) { return lightbox.__pepSelectCOALightboxController; }

		var doc = lightbox.ownerDocument || document;
		var image = lightbox.querySelector('[data-ps-coa-image]');
		var count = lightbox.querySelector('[data-ps-coa-count]');
		var closeButton = lightbox.querySelector('[data-ps-coa-close]');
		var previousButton = lightbox.querySelector('[data-ps-coa-prev]');
		var nextButton = lightbox.querySelector('[data-ps-coa-next]');
		if (!image || !count || !closeButton || !previousButton || !nextButton) { return null; }
		var index = 0;
		var launchingTrigger = null;

		function render(nextIndex) {
			index = clampIndex(nextIndex, triggers.length);
			var current = triggers[index];
			image.src = current.getAttribute('data-ps-coa-full');
			image.alt = current.getAttribute('data-ps-coa-alt') || '';
			count.textContent = 'Page ' + (index + 1) + ' of ' + triggers.length;
			previousButton.disabled = index === 0;
			nextButton.disabled = index === triggers.length - 1;
		}

		function open(event) {
			launchingTrigger = event.currentTarget;
			render(triggers.indexOf(launchingTrigger));
			lightbox.hidden = false;
			doc.body.classList.add('ps-coa-lightbox-open');
			closeButton.focus();
		}

		function close() {
			if (lightbox.hidden) { return; }
			lightbox.hidden = true;
			image.removeAttribute('src');
			doc.body.classList.remove('ps-coa-lightbox-open');
			if (launchingTrigger) { launchingTrigger.focus(); }
		}

		function previous() { if (!previousButton.disabled) { render(index - 1); } }
		function next() { if (!nextButton.disabled) { render(index + 1); } }
		function focusableControls() { return [closeButton, previousButton, nextButton].filter(function (element) { return !element.hidden && !element.disabled; }); }

		function onKeydown(event) {
			if (lightbox.hidden) { return; }
			if (event.key === 'Escape') { event.preventDefault(); close(); }
			else if (event.key === 'ArrowLeft') { event.preventDefault(); previous(); }
			else if (event.key === 'ArrowRight') { event.preventDefault(); next(); }
			else if (event.key === 'Tab') {
				var controls = focusableControls();
				var first = controls[0];
				var last = controls[controls.length - 1];
				if (!controls.includes(doc.activeElement)) { event.preventDefault(); (event.shiftKey ? last : first).focus(); }
				else if (event.shiftKey && doc.activeElement === first) { event.preventDefault(); last.focus(); }
				else if (!event.shiftKey && doc.activeElement === last) { event.preventDefault(); first.focus(); }
			}
		}

		triggers.forEach(function (button) { button.addEventListener('click', open); });
		closeButton.addEventListener('click', close);
		previousButton.addEventListener('click', previous);
		nextButton.addEventListener('click', next);
		lightbox.addEventListener('click', function (event) { if (event.target === lightbox) { close(); } });
		doc.addEventListener('keydown', onKeydown);
		if (triggers.length < 2) { previousButton.hidden = true; nextButton.hidden = true; }

		var controller = { open: open, close: close, render: render, previous: previous, next: next };
		lightbox.__pepSelectCOALightboxController = controller;
		return controller;
	}

	function init(documentObject) {
		var doc = documentObject || document;
		return Array.prototype.map.call(doc.querySelectorAll('[data-ps-coa-certificate-gallery]'), createGallery);
	}

	if (typeof document !== 'undefined') {
		if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', function () { init(document); }, { once: true }); }
		else { init(document); }
	}

	return { wrapIndex: wrapIndex, clampIndex: clampIndex, createGallery: createGallery, init: init };
}));
