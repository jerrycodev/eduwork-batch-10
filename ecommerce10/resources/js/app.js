import { createIcons, icons } from "lucide";
import {
    Livewire,
    Alpine,
} from "../../vendor/livewire/livewire/dist/livewire.esm";

import "./nprogress";
import { registerAddressRegionForm } from './address.form';
import { registerStorefrontComponents } from './storefront';
import { registerAdminComponents } from './admin';
import Precognition from "laravel-precognition-alpine";

/**
 * Render Lucide placeholders in the current document, including Alpine
 * templates. Remove the marker after rendering so later passes are idempotent.
 */
function renderIcons(root = document) {
    if (!root?.querySelectorAll) return;

    try {
        createIcons({ icons, root, inTemplates: true });
        root.querySelectorAll('svg[data-lucide]').forEach((icon) => {
            icon.removeAttribute('data-lucide');
        });
    } catch (error) {
        console.error('[Lucide] Failed to render icons:', error);
    } finally {
        document.body?.classList.add('icons-ready');
    }
}

function initializeIcons() {
    renderIcons(document);
}

initializeIcons();
document.addEventListener('boost:page-ready', initializeIcons);

// Alpine x-if and other client-side updates can insert fresh icon placeholders
// after the initial page render. Only process newly-added subtrees.
const iconObserver = new MutationObserver((mutations) => {
    for (const mutation of mutations) {
        for (const node of mutation.addedNodes) {
            if (!(node instanceof Element)) continue;

            if (node.matches('[data-lucide]')) {
                renderIcons(node.parentElement ?? document);
            } else if (node.querySelector?.('[data-lucide]')) {
                renderIcons(node);
            }
        }
    }
});

if (document.body) {
    iconObserver.observe(document.body, { childList: true, subtree: true });
}

window.Alpine = Alpine;
if (!window.__storefrontAlpineRegistered) {
    Alpine.plugin(Precognition);
    registerAddressRegionForm(Alpine);
    registerStorefrontComponents(Alpine);
    registerAdminComponents(Alpine);
    window.__storefrontAlpineRegistered = true;
}

// Boost can re-evaluate page scripts during navigation. Livewire registers
// Alpine plugins (including $persist) globally, so its runtime must start once.
if (!window.__livewireStarted) {
    Livewire.start();
    window.__livewireStarted = true;
}

// Realtime notifications are opt-in; do not load Echo or open a socket by default.
if (document.querySelector('[data-realtime-notifications]')) {
    import('./notification-realtime');
}
