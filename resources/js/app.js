/**
 * Once the top of the page scrolls under the navigation bar, the bar turns
 * translucent and shows the page title in small, as on iOS.
 */
const navBar = document.querySelector('[data-nav-bar]');
const scrollSentinel = document.querySelector('[data-scroll-sentinel]');

if (navBar && scrollSentinel) {
    new IntersectionObserver(
        ([entry]) => navBar.toggleAttribute('data-scrolled', !entry.isIntersecting),
        { rootMargin: `-${navBar.offsetHeight}px 0px 0px 0px` },
    ).observe(scrollSentinel);
}

/**
 * Action sheets: a [data-sheet-open="id"] button opens the sheet with that id,
 * and tapping the dim area or a [data-sheet-close] button closes it.
 */
document.addEventListener('click', (event) => {
    const opener = event.target.closest('[data-sheet-open]');

    if (opener) {
        document.getElementById(opener.dataset.sheetOpen)?.showModal();

        return;
    }

    if (event.target.closest('[data-sheet-close]')) {
        event.target.closest('dialog')?.close();

        return;
    }

    if (event.target instanceof HTMLDialogElement) {
        event.target.close();
    }
});

/**
 * Filters apply as soon as a [data-auto-submit] field changes.
 */
document.addEventListener('change', (event) => {
    if (event.target.matches('[data-auto-submit]')) {
        event.target.form.requestSubmit();
    }
});
