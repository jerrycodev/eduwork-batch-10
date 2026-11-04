//
import '../../public/shadcn-html/dist/components/dialog/dialog.js';
import '../../public/shadcn-html/dist/components/sidebar/sidebar.js';
import '../../public/shadcn-html/dist/components/tooltip/tooltip.js';

import { createIcons, icons } from 'lucide';
import { Livewire, Alpine } from '../../vendor/livewire/livewire/dist/livewire.esm';

createIcons({ icons });

window.Alpine = Alpine;

Livewire.start();