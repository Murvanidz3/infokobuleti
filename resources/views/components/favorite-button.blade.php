@props(['saved' => false, 'item' => null])

{{-- Saved through the delegated listener in resources/js/ui/favorites.js; the heart fill follows aria-pressed. --}}
<button type="button"
        data-favorite="{{ $item }}"
        aria-pressed="{{ $saved ? 'true' : 'false' }}"
        aria-label="{{ __('ui.save') }}"
        {{ $attributes->class(['group/favorite hit-44 z-10 grid size-[38px] place-items-center rounded-full bg-white/90 text-coral backdrop-blur-[8px] transition-transform duration-200 hover:scale-108']) }}>
    <x-icon name="heart" :size="18" class="group-aria-pressed/favorite:fill-current" />
</button>
