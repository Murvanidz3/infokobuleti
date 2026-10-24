/**
 * Fades sections up 24px as they scroll into view. Sections already visible on load are left alone,
 * and nothing is hidden when the user prefers reduced motion or IntersectionObserver is missing.
 *
 * Positions come from the observer's first callback rather than getBoundingClientRect(), which would
 * force a synchronous layout of the whole page during startup.
 */
export function initReveal() {
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches || !('IntersectionObserver' in window)) {
        return;
    }

    const seen = new WeakSet();

    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                const element = entry.target;

                if (!seen.has(element)) {
                    seen.add(element);

                    // rootBounds is shrunk by the 40px rootMargin; add it back to get the viewport bottom.
                    const viewportBottom = entry.rootBounds ? entry.rootBounds.bottom + 40 : window.innerHeight;

                    if (entry.isIntersecting || entry.boundingClientRect.top < viewportBottom) {
                        observer.unobserve(element);
                    } else {
                        element.classList.add('reveal-pending');
                    }

                    return;
                }

                if (entry.isIntersecting) {
                    element.classList.remove('reveal-pending');
                    observer.unobserve(element);
                }
            });
        },
        { threshold: 0.06, rootMargin: '0px 0px -40px 0px' },
    );

    document.querySelectorAll('[data-reveal]').forEach((element) => observer.observe(element));
}
