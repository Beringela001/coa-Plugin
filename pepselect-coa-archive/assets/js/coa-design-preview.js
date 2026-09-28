// Keep preview interactions inside the sandbox. Never submit or navigate to a live page.
document.addEventListener('click', function (event) {
	if (event.target.closest('a')) event.preventDefault();
});
document.addEventListener('submit', function (event) { event.preventDefault(); });
