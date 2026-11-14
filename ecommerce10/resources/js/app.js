import { createIcons, icons } from "lucide";
import {
    Livewire,
    Alpine,
} from "../../vendor/livewire/livewire/dist/livewire.esm";

import "./nprogress";
import "./address.form";

/**
 * Render Lucide icons before revealing their placeholders.
 * The page-ready hook also covers body swaps performed by Boost navigation.
 */
function initializeIcons() {
    createIcons({ icons });
    document.body?.classList.add("icons-ready");
}

initializeIcons();
document.addEventListener("boost:page-ready", initializeIcons);

window.Alpine = Alpine;

// Boost can re-evaluate page scripts during navigation. Livewire registers
// Alpine plugins (including $persist) globally, so its runtime must start once.
if (!window.__livewireStarted) {
    Livewire.start();
    window.__livewireStarted = true;
}
