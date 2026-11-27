{{-- Apply the saved rail state before paint. Alpine's sidebar store reads the same key. --}}
<script>
    (function () {
        var open = true;

        try {
            var stored = localStorage.getItem('filament:sidebar:desktop_open');

            if (stored !== null) {
                open = JSON.parse(stored);
            }
        } catch (e) {
            open = true;
        }

        if (open) {
            document.documentElement.classList.add('ik-sidebar-desktop-open');
        }
    })();
</script>
