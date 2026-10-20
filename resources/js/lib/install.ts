import { useSyncExternalStore } from 'react';

/** The event Chrome fires once it can install the app. It is not in the DOM types yet. */
type InstallPromptEvent = Event & {
    prompt: () => Promise<void>;
    userChoice: Promise<{ outcome: 'accepted' | 'dismissed' }>;
};

/**
 * How the app gets onto the home screen on this phone:
 * - prompt: the browser shows its own install dialog (Chrome on Android).
 * - ios: the user adds it from Safari's share menu, which no page can open.
 * - manual: the user adds it from the browser's own menu.
 */
export type InstallMethod = 'prompt' | 'ios' | 'manual';

let installPrompt: InstallPromptEvent | null = null;
let wasInstalled = false;
const listeners = new Set<() => void>();

function notify(): void {
    listeners.forEach((listener) => listener());
}

function subscribe(listener: () => void): () => void {
    listeners.add(listener);

    return () => listeners.delete(listener);
}

// Chrome can offer the install before any page has rendered, so the offer is kept from the
// moment the app loads. Taking it also stops Chrome's own install bar, since ours replaces it.
window.addEventListener('beforeinstallprompt', (event) => {
    event.preventDefault();
    installPrompt = event as InstallPromptEvent;
    notify();
});

window.addEventListener('appinstalled', () => {
    wasInstalled = true;
    installPrompt = null;
    notify();
});

/** Opened from the home screen icon rather than in a browser tab. */
function isRunningInstalled(): boolean {
    return window.matchMedia('(display-mode: standalone)').matches || (navigator as Navigator & { standalone?: boolean }).standalone === true;
}

/** iPhones and iPads, including iPads that report themselves as a Mac. */
function isAppleMobile(): boolean {
    return /iPhone|iPad|iPod/.test(navigator.userAgent) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
}

function isPhone(): boolean {
    return isAppleMobile() || /Android/i.test(navigator.userAgent);
}

/**
 * How the user can install the app, or null when there is nothing to offer:
 * the app is already installed, or this is not a phone.
 */
export function useInstall(): { method: InstallMethod | null; install: () => Promise<void> } {
    const prompt = useSyncExternalStore(subscribe, () => installPrompt);
    const installed = useSyncExternalStore(subscribe, () => wasInstalled);

    let method: InstallMethod | null = null;

    if (!installed && !isRunningInstalled() && isPhone()) {
        method = prompt ? 'prompt' : isAppleMobile() ? 'ios' : 'manual';
    }

    async function install(): Promise<void> {
        if (!installPrompt) {
            return;
        }

        // The browser's dialog can be shown only once; a dismissed offer falls back to the manual steps.
        const offer = installPrompt;
        installPrompt = null;
        notify();
        await offer.prompt();
        const { outcome } = await offer.userChoice;
        wasInstalled = outcome === 'accepted';
        notify();
    }

    return { method, install };
}
