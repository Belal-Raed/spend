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
 * The theme picker in Settings: "auto" follows the phone, "light" and "dark"
 * override it. The choice is kept on this device only.
 */
const themeChoices = document.querySelectorAll('input[name=theme]');

const applyTheme = (theme) => {
    if (theme === 'light' || theme === 'dark') {
        document.documentElement.dataset.theme = theme;
    } else {
        delete document.documentElement.dataset.theme;
    }

    document.querySelectorAll('meta[name=theme-color]').forEach((meta) => {
        const isDark = theme === 'dark' || (theme !== 'light' && meta.media.includes('dark'));
        meta.content = isDark ? '#000000' : '#f2f2f7';
    });
};

if (themeChoices.length) {
    let savedTheme = 'auto';

    try {
        savedTheme = localStorage.getItem('theme') ?? 'auto';
    } catch {}

    themeChoices.forEach((choice) => {
        choice.checked = choice.value === savedTheme;

        choice.addEventListener('change', () => {
            try {
                if (choice.value === 'auto') {
                    localStorage.removeItem('theme');
                } else {
                    localStorage.setItem('theme', choice.value);
                }
            } catch {}

            applyTheme(choice.value);
        });
    });
}

/**
 * The debts screen keeps the open tab in the address (#owed-to-me), so it
 * comes back after recording a payment or going back.
 */
const debtTabs = document.querySelectorAll('input[name=debts-tab]');

debtTabs.forEach((tab) => {
    const hash = '#' + tab.id.replace(/^tab-/, '');

    if (location.hash === hash) {
        tab.checked = true;
    }

    tab.addEventListener('change', () => {
        history.replaceState(null, '', tab === debtTabs[0] ? location.pathname : hash);
    });
});

/**
 * Filters apply as soon as a [data-auto-submit] field changes.
 */
document.addEventListener('change', (event) => {
    if (event.target.matches('[data-auto-submit]')) {
        event.target.form.requestSubmit();
    }
});
