export type Theme = 'auto' | 'light' | 'dark';

/**
 * The theme picked in Settings on this device. "auto" follows the phone.
 */
export function savedTheme(): Theme {
    try {
        const theme = localStorage.getItem('theme');

        return theme === 'light' || theme === 'dark' ? theme : 'auto';
    } catch {
        return 'auto';
    }
}

/**
 * Keeps the theme on this device and applies it straight away.
 */
export function saveTheme(theme: Theme): void {
    try {
        if (theme === 'auto') {
            localStorage.removeItem('theme');
        } else {
            localStorage.setItem('theme', theme);
        }
    } catch {}

    applyTheme(theme);
}

function applyTheme(theme: Theme): void {
    if (theme === 'auto') {
        delete document.documentElement.dataset.theme;
    } else {
        document.documentElement.dataset.theme = theme;
    }

    document.querySelectorAll<HTMLMetaElement>('meta[name=theme-color]').forEach((meta) => {
        const isDark = theme === 'dark' || (theme === 'auto' && meta.media.includes('dark'));
        meta.content = isDark ? '#000000' : '#f2f2f7';
    });
}
