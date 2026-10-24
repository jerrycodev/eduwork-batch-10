//
import '../../public/shadcn-html/dist/components/dialog/dialog.js';
import '../../public/shadcn-html/dist/components/sidebar/sidebar.js';
import '../../public/shadcn-html/dist/components/tooltip/tooltip.js';

import { createIcons, icons } from 'lucide';
import Alpine from 'alpinejs';

// Caution, this will import all the icons and bundle them.
createIcons({ icons });

window.Alpine = Alpine;

Alpine.start();