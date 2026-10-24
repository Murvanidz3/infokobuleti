import Alpine from 'alpinejs';
import { setupUi } from './ui/setup';

// Entry for pages without Livewire components (see App\Services\FrontendBundle): plain Alpine,
// without the Livewire runtime and the plugins it bundles. Alpine plugins needed on these pages
// (collapse, focus, intersect, ...) have to be imported and registered here.
setupUi(Alpine);

window.Alpine = Alpine;
Alpine.start();
