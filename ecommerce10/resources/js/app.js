//
import '../../public/shadcn-html/dist/components/dialog/dialog.js';
import '../../public/shadcn-html/dist/components/sidebar/sidebar.js';
import '../../public/shadcn-html/dist/components/tooltip/tooltip.js';

import "./boost.js";
import { createIcons, icons } from 'lucide';
import { Livewire, Alpine } from '../../vendor/livewire/livewire/dist/livewire.esm';

function renderIcons() {
    createIcons({ icons });
}

renderIcons();
document.addEventListener("boost:page-ready", renderIcons);

window.Alpine = Alpine;

Livewire.start();
