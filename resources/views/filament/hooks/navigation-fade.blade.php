{{-- Restarts the content fade after wire:navigate. The layout node is kept, so the CSS animation would otherwise run only once. --}}
<script data-navigate-once>
    document.addEventListener('livewire:navigated', () => {
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            return;
        }

        const main = document.querySelector('.fi-main');

        if (! main) {
            return;
        }

        main.style.animation = 'none';
        void main.offsetWidth;
        main.style.animation = '';
    });
</script>
