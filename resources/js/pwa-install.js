const DISMISS_KEY = 'buchapro-pwa-install-dismissed';
const DISMISS_MS = 7 * 24 * 60 * 60 * 1000;

function isDismissed() {
    const raw = localStorage.getItem(DISMISS_KEY);

    if (!raw) {
        return false;
    }

    const dismissedAt = Number.parseInt(raw, 10);

    return Number.isFinite(dismissedAt) && Date.now() - dismissedAt < DISMISS_MS;
}

function dismissPrompt() {
    localStorage.setItem(DISMISS_KEY, String(Date.now()));
    hideBanner();
}

function isStandalone() {
    return window.matchMedia('(display-mode: standalone)').matches
        || window.navigator.standalone === true;
}

function isIosInstallable() {
    const ua = window.navigator.userAgent;
    const ios = /iPad|iPhone|iPod/.test(ua)
        || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
    const safari = /Safari/.test(ua) && !/CriOS|FxiOS|EdgiOS|OPiOS/.test(ua);

    return ios && safari;
}

function banner() {
    return document.getElementById('pwa-install-banner');
}

function hideBanner() {
    const el = banner();

    if (!el) {
        return;
    }

    el.hidden = true;
    el.classList.add('hidden');
}

function showBanner(mode) {
    const el = banner();

    if (!el || isDismissed() || isStandalone()) {
        return;
    }

    const defaultCopy = el.querySelector('[data-pwa-install-default]');
    const iosCopy = el.querySelector('[data-pwa-install-ios]');
    const installButton = el.querySelector('[data-pwa-install-action]');

    if (defaultCopy) {
        defaultCopy.hidden = mode === 'ios';
        defaultCopy.classList.toggle('hidden', mode === 'ios');
    }

    if (iosCopy) {
        iosCopy.hidden = mode !== 'ios';
        iosCopy.classList.toggle('hidden', mode !== 'ios');
    }

    if (installButton) {
        installButton.hidden = mode === 'ios';
        installButton.classList.toggle('hidden', mode === 'ios');
    }

    el.hidden = false;
    el.classList.remove('hidden');
}

let deferredPrompt = null;

window.addEventListener('beforeinstallprompt', (event) => {
    event.preventDefault();
    deferredPrompt = event;
    showBanner('install');
});

window.addEventListener('appinstalled', () => {
    deferredPrompt = null;
    hideBanner();
});

window.addEventListener('load', () => {
    if (deferredPrompt || isStandalone() || isDismissed()) {
        return;
    }

    if (isIosInstallable()) {
        showBanner('ios');
    }
});

document.addEventListener('click', (event) => {
    if (event.target.closest('[data-pwa-install-dismiss]')) {
        dismissPrompt();

        return;
    }

    if (!event.target.closest('[data-pwa-install-action]') || !deferredPrompt) {
        return;
    }

    deferredPrompt.prompt();
    deferredPrompt.userChoice.finally(() => {
        deferredPrompt = null;
        hideBanner();
    });
});
