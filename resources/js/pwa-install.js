const DISMISS_KEY = 'buchapro-pwa-install-dismissed';
const DISMISS_MS = 7 * 24 * 60 * 60 * 1000;
const MOBILE_FALLBACK_MS = 2000;

let deferredPrompt = window.__pwaDeferredPrompt ?? null;

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

function userAgent() {
    return window.navigator.userAgent || '';
}

function isIos() {
    const ua = userAgent();

    return /iPad|iPhone|iPod/.test(ua)
        || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
}

function isIosSafari() {
    const ua = userAgent();

    return isIos()
        && /Safari/.test(ua)
        && !/CriOS|FxiOS|EdgiOS|OPiOS/.test(ua);
}

function isAndroid() {
    return /Android/i.test(userAgent());
}

function isMobile() {
    return isIos()
        || isAndroid()
        || window.matchMedia('(max-width: 768px)').matches;
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

function setModeVisibility(el, mode) {
    el.querySelectorAll('[data-pwa-install-copy]').forEach((node) => {
        const modes = (node.getAttribute('data-pwa-install-copy') || '').split(/\s+/);
        const visible = modes.includes(mode);

        node.hidden = !visible;
        node.classList.toggle('hidden', !visible);
    });

    const installButton = el.querySelector('[data-pwa-install-action]');

    if (installButton) {
        const showInstall = mode === 'install';
        installButton.hidden = !showInstall;
        installButton.classList.toggle('hidden', !showInstall);
    }
}

function showBanner(mode) {
    const el = banner();

    if (!el || isDismissed() || isStandalone()) {
        return;
    }

    setModeVisibility(el, mode);
    el.hidden = false;
    el.classList.remove('hidden');
}

function resolveInitialMode() {
    if (deferredPrompt) {
        return 'install';
    }

    if (isIos()) {
        return isIosSafari() ? 'ios' : 'ios-other';
    }

    if (isAndroid() || isMobile()) {
        return 'android-manual';
    }

    return null;
}

function init() {
    if (isStandalone() || isDismissed()) {
        return;
    }

    const mode = resolveInitialMode();

    if (!mode) {
        return;
    }

    if (mode === 'install') {
        showBanner('install');

        return;
    }

    if (mode === 'ios' || mode === 'ios-other') {
        showBanner(mode);

        return;
    }

    window.setTimeout(() => {
        if (deferredPrompt) {
            showBanner('install');

            return;
        }

        if (!isDismissed() && !isStandalone() && isMobile()) {
            showBanner('android-manual');
        }
    }, MOBILE_FALLBACK_MS);
}

window.addEventListener('pwa-installable', () => {
    deferredPrompt = window.__pwaDeferredPrompt ?? deferredPrompt;
    showBanner('install');
});

window.addEventListener('appinstalled', () => {
    deferredPrompt = null;
    window.__pwaDeferredPrompt = null;
    hideBanner();
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
        window.__pwaDeferredPrompt = null;
        hideBanner();
    });
});

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
} else {
    init();
}
