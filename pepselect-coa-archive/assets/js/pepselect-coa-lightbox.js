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

	function createGallery(gallery) {
		var triggers = Array.prototype.slice.call(gallery.querySelectorAll('[data-ps-coa-full]'));
		var lightbox = gallery.parentNode.querySelector('[data-ps-coa-lightbox]');
		if (!triggers.length || !lightbox) { return null; }
		var image = lightbox.querySelector('[data-ps-coa-image]');
		var count = lightbox.querySelector('[data-ps-coa-count]');
		var closeButton = lightbox.querySelector('[data-ps-coa-close]');
		var previousButton = lightbox.querySelector('[data-ps-coa-prev]');
		var nextButton = lightbox.querySelector('[data-ps-coa-next]');
		var index = 0;
		var trigger = null;

		function render(nextIndex) {
			index = wrapIndex(nextIndex, triggers.length);
			var current = triggers[index];
			image.src = current.getAttribute('data-ps-coa-full');
			image.alt = current.getAttribute('data-ps-coa-alt') || '';
			count.textContent = 'Page ' + (index + 1) + ' of ' + triggers.length;
		}

		function open(event) {
			trigger = event.currentTarget;
			render(triggers.indexOf(trigger));
			lightbox.hidden = false;
			document.body.classList.add('ps-coa-lightbox-open');
			closeButton.focus();
		}

		function close() {
			lightbox.hidden = true;
			image.removeAttribute('src');
			document.body.classList.remove('ps-coa-lightbox-open');
			if (trigger) { trigger.focus(); }
		}

		function focusableControls() {
			return [closeButton, previousButton, nextButton].filter(function (element) { return !element.hidden && !element.disabled; });
		}

		function onKeydown(event) {
			if (lightbox.hidden) { return; }
			if (event.key === 'Escape') { event.preventDefault(); close(); }
			else if (event.key === 'ArrowLeft') { event.preventDefault(); render(index - 1); }
			else if (event.key === 'ArrowRight') { event.preventDefault(); render(index + 1); }
			else if (event.key === 'Tab') {
				var controls = focusableControls();
				var first = controls[0];
				var last = controls[controls.length - 1];
				if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
				else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
			}
		}

		triggers.forEach(function (button) { button.addEventListener('click', open); });
		closeButton.addEventListener('click', close);
		previousButton.addEventListener('click', function () { render(index - 1); });
		nextButton.addEventListener('click', function () { render(index + 1); });
		lightbox.addEventListener('click', function (event) { if (event.target === lightbox) { close(); } });
		document.addEventListener('keydown', onKeydown);
		if (triggers.length < 2) { previousButton.hidden = true; nextButton.hidden = true; }

		return { open: open, close: close, render: render };
	}

	function init(documentObject) {
		var doc = documentObject || document;
		return Array.prototype.map.call(doc.querySelectorAll('[data-ps-coa-gallery]'), createGallery);
	}

	if (typeof document !== 'undefined') {
		if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', function () { init(document); }); }
		else { init(document); }
	}

	return { wrapIndex: wrapIndex, createGallery: createGallery, init: init };
}));
