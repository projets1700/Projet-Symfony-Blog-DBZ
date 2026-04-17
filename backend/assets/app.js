/*
 * Welcome to your app's main JavaScript file!
 *
 * This file will be included onto the page via the importmap() Twig function,
 * which should already be in your base.html.twig.
 */
import './styles/app.css';

const cycleShenron = () => {
    document.body.classList.remove('shenron-awake');
    document.body.classList.add('shenron-summoning');

    window.setTimeout(() => {
        document.body.classList.add('shenron-awake');
    }, 1400);

    window.setTimeout(() => {
        document.body.classList.remove('shenron-summoning');
    }, 2600);

    window.setTimeout(() => {
        document.body.classList.remove('shenron-awake');
    }, 6200);
};

window.setTimeout(cycleShenron, 1600);
window.setInterval(cycleShenron, 13500);

const enableAutoRefresh = () => {
    const body = document.body;
    if (!body || body.dataset.autoRefresh !== 'true') {
        return;
    }

    const configuredInterval = Number.parseInt(body.dataset.autoRefreshInterval ?? '30000', 10);
    const refreshIntervalMs = Number.isNaN(configuredInterval) ? 30000 : configuredInterval;

    let userIsEditingForm = false;

    const formFieldSelector = 'input, textarea, select, [contenteditable="true"]';

    document.addEventListener('input', (event) => {
        if (event.target instanceof Element && event.target.matches(formFieldSelector)) {
            userIsEditingForm = true;
        }
    }, true);

    document.addEventListener('submit', () => {
        userIsEditingForm = false;
    }, true);

    window.setInterval(() => {
        if (document.visibilityState !== 'visible') {
            return;
        }

        if (userIsEditingForm) {
            return;
        }

        const activeElement = document.activeElement;
        if (activeElement instanceof Element && activeElement.matches(formFieldSelector)) {
            return;
        }

        window.location.reload();
    }, refreshIntervalMs);
};

enableAutoRefresh();

const enableSideNavigation = () => {
    const body = document.body;
    const panel = document.querySelector('[data-side-nav-backdrop]');
    const toggleButton = document.querySelector('[data-side-nav-toggle]');
    const closeButton = document.querySelector('[data-side-nav-close]');

    if (!(body instanceof HTMLBodyElement) || !(toggleButton instanceof HTMLButtonElement)) {
        return;
    }

    const closeMenu = () => {
        body.classList.remove('side-nav-open');
        toggleButton.setAttribute('aria-expanded', 'false');
        const sideNav = document.getElementById('side-navigation');
        if (sideNav) {
            sideNav.setAttribute('aria-hidden', 'true');
        }
    };

    const openMenu = () => {
        body.classList.add('side-nav-open');
        toggleButton.setAttribute('aria-expanded', 'true');
        const sideNav = document.getElementById('side-navigation');
        if (sideNav) {
            sideNav.setAttribute('aria-hidden', 'false');
        }
    };

    toggleButton.addEventListener('click', () => {
        if (body.classList.contains('side-nav-open')) {
            closeMenu();
            return;
        }

        openMenu();
    });

    if (closeButton instanceof HTMLButtonElement) {
        closeButton.addEventListener('click', closeMenu);
    }

    if (panel instanceof HTMLElement) {
        panel.addEventListener('click', closeMenu);
    }

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && body.classList.contains('side-nav-open')) {
            closeMenu();
        }
    });
};

enableSideNavigation();
