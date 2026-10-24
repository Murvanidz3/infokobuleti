import { Livewire, Alpine } from '../../vendor/livewire/livewire/dist/livewire.esm';
import { setupUi } from './ui/setup';

// Entry for pages that render Livewire components (see App\Services\FrontendBundle).
setupUi(Alpine);

Livewire.start();
