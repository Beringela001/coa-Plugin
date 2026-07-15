(function () {
	'use strict';

	var selector = '[data-ps-coa-product-carousel]';
	var elementorHookRegistered = false;

	function initialize(root) {
		if (!root || root.dataset.psCoaInitialized === 'true') { return; }
		var viewport = root.querySelector('.ps-coa-product-carousel__viewport');
		var cards = Array.prototype.slice.call(root.querySelectorAll('.ps-coa-product-carousel__card'));
		var previous = root.querySelector('.ps-coa-product-carousel__previous');
		var next = root.querySelector('.ps-coa-product-carousel__next');
		var track = root.querySelector('.ps-coa-product-carousel__track');
		if (!viewport || !track || !cards.length || !previous || !next) { return; }
		root.dataset.psCoaInitialized = 'true';

		var index = 0;
		var frame = 0;
		var resizeFrame = 0;
		var reducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

		function visibleCount() {
			var value = parseInt(window.getComputedStyle(root).getPropertyValue('--ps-coa-cards-per-view'), 10);
			return Math.max(1, isNaN(value) ? 1 : value);
		}

		function cardsPerViewForWidth(width) {
			if (width <= 767) { return 1; }
			if (width <= 1023) { return 2; }
			return 3;
		}

		function maxIndex() { return Math.max(0, cards.length - visibleCount()); }

		function updateControls() {
			var maximum = maxIndex();
			index = Math.max(0, Math.min(index, maximum));
			var isStatic = maximum === 0;
			root.classList.toggle('ps-coa-product-carousel--static', isStatic);
			previous.hidden = isStatic;
			next.hidden = isStatic;
			previous.disabled = isStatic || index === 0;
			next.disabled = isStatic || index === maximum;
			previous.setAttribute('aria-disabled', previous.disabled ? 'true' : 'false');
			next.setAttribute('aria-disabled', next.disabled ? 'true' : 'false');
		}

		function cardOffset(card) { return Math.max(0, card.offsetLeft - cards[0].offsetLeft); }

		function moveTo(nextIndex, animate) {
			index = Math.max(0, Math.min(nextIndex, maxIndex()));
			viewport.scrollTo({ left: cardOffset(cards[index]), behavior: animate && !reducedMotion ? 'smooth' : 'auto' });
			updateControls();
		}

		function syncFromScroll() {
			frame = 0;
			var nearest = 0;
			var distance = Infinity;
			cards.forEach(function (card, cardIndex) {
				var candidate = Math.abs(cardOffset(card) - viewport.scrollLeft);
				if (candidate < distance) { distance = candidate; nearest = cardIndex; }
			});
			index = Math.min(nearest, maxIndex());
			updateControls();
		}

		previous.addEventListener('click', function () { moveTo(index - 1, true); });
		next.addEventListener('click', function () { moveTo(index + 1, true); });
		viewport.addEventListener('scroll', function () {
			if (!frame) { frame = window.requestAnimationFrame(syncFromScroll); }
		}, { passive: true });
		viewport.addEventListener('focusin', function (event) {
			var focusedCard = event.target.closest && event.target.closest('.ps-coa-product-carousel__card');
			var focusedIndex = cards.indexOf(focusedCard);
			if (focusedIndex >= 0) { moveTo(Math.min(focusedIndex, maxIndex()), false); }
		});

		function clearStaleSizing() {
			['width', 'max-width', 'min-width', 'flex-basis', 'transform'].forEach(function (property) { track.style.removeProperty(property); });
			cards.forEach(function (card) {
				['width', 'max-width', 'min-width', 'flex-basis', 'transform'].forEach(function (property) { card.style.removeProperty(property); });
			});
		}

		function resize() {
			resizeFrame = 0;
			clearStaleSizing();
			var componentWidth = root.getBoundingClientRect().width;
			var viewportWidth = viewport.getBoundingClientRect().width;
			if (componentWidth <= 0 || viewportWidth <= 0) { updateControls(); return; }
			root.style.setProperty('--ps-coa-cards-per-view', cardsPerViewForWidth(componentWidth));
			moveTo(Math.min(index, maxIndex()), false);
		}

		function scheduleResize() {
			if (!resizeFrame) { resizeFrame = window.requestAnimationFrame(resize); }
		}
		if ('ResizeObserver' in window) {
			var observer = new ResizeObserver(scheduleResize);
			observer.observe(viewport);
		}
		window.addEventListener('resize', scheduleResize, { passive: true });
		window.addEventListener('orientationchange', scheduleResize, { passive: true });
		updateControls();
		scheduleResize();
	}

	function initializeWithin(scope) {
		if (!scope) { return; }
		if (scope.matches && scope.matches(selector)) { initialize(scope); }
		if (scope.querySelectorAll) { scope.querySelectorAll(selector).forEach(initialize); }
	}

	function initializeAll() { initializeWithin(document); }

	function registerElementorHook() {
		if (elementorHookRegistered || !window.elementorFrontend || !window.elementorFrontend.hooks) { return; }
		elementorHookRegistered = true;
		window.elementorFrontend.hooks.addAction('frontend/element_ready/global', function (scope) {
			initializeWithin(scope && scope[0] ? scope[0] : scope);
		});
	}
	if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', initializeAll, { once: true }); }
	else { initializeAll(); }
	registerElementorHook();
	window.addEventListener('elementor/frontend/init', registerElementorHook, { once: true });
}());
