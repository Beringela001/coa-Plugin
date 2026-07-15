(function () {
	'use strict';

	function initCarousel(root) {
		if (!root || root.dataset.psHistoryReady === 'true') return;
		var slides = Array.prototype.slice.call(root.querySelectorAll('[data-ps-history-slide]'));
		var previous = root.querySelector('[data-ps-history-previous]');
		var next = root.querySelector('[data-ps-history-next]');
		var status = root.querySelector('[data-ps-history-status]');
		if (!slides.length || !previous || !next) return;
		root.dataset.psHistoryReady = 'true';
		var index = 0;

		function show(newIndex) {
			index = Math.max(0, Math.min(slides.length - 1, newIndex));
			slides.forEach(function (slide, slideIndex) {
				slide.hidden = slideIndex !== index;
				slide.setAttribute('aria-hidden', slideIndex === index ? 'false' : 'true');
			});
			previous.disabled = index === 0;
			next.disabled = index === slides.length - 1;
			if (status) status.textContent = 'Report ' + (index + 1) + ' of ' + slides.length;
		}

		previous.addEventListener('click', function () { show(index - 1); });
		next.addEventListener('click', function () { show(index + 1); });
		root.addEventListener('keydown', function (event) {
			if (event.key === 'ArrowLeft') { event.preventDefault(); show(index - 1); }
			if (event.key === 'ArrowRight') { event.preventDefault(); show(index + 1); }
		});
		show(0);
	}

	function init() {
		document.querySelectorAll('[data-ps-history-carousel]').forEach(initCarousel);
	}

	if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init, { once: true });
	else init();
}());
