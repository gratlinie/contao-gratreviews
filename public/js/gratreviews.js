(() => {
    const icon = (path) => `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="${path}"/></svg>`;

    document.querySelectorAll('[data-gr-list]').forEach((list) => {
        const { labelMore, labelLess, labelPrev, labelNext } = list.dataset;

        // Lange Texte kürzen, „Weiterlesen“ klappt sie auf
        const clamp = () => list.querySelectorAll('[data-gr-text]').forEach((text) => {
            if (text.classList.contains('is-open')) {
                return;
            }

            text.classList.add('is-clamped');
            const paragraph = text.querySelector('p');
            const overflowing = paragraph.scrollHeight > paragraph.clientHeight + 2;
            let toggle = text.querySelector('.gr-review__toggle');

            if (!overflowing) {
                text.classList.remove('is-clamped');
                toggle?.remove();

                return;
            }

            if (!toggle) {
                toggle = document.createElement('button');
                toggle.type = 'button';
                toggle.className = 'gr-review__toggle';
                toggle.setAttribute('aria-expanded', 'false');
                toggle.textContent = labelMore;
                toggle.addEventListener('click', () => {
                    const open = text.classList.toggle('is-open');
                    toggle.setAttribute('aria-expanded', String(open));
                    toggle.textContent = open ? labelLess : labelMore;
                });
                text.append(toggle);
            }
        });

        clamp();
        document.fonts?.ready.then(clamp);

        if (!list.classList.contains('gr-list--slider')) {
            return;
        }

        // Slider: Pfeile blättern um eine Seite, an den Enden deaktiviert
        const track = list.querySelector('.gr-list__items');
        const nav = document.createElement('div');
        nav.className = 'gr-list__nav';
        nav.innerHTML = `<button type="button" data-dir="-1" aria-label="${labelPrev}">${icon('m15 18-6-6 6-6')}</button><button type="button" data-dir="1" aria-label="${labelNext}">${icon('m9 18 6-6-6-6')}</button>`;
        track.after(nav);

        const [prev, next] = nav.querySelectorAll('button');
        const update = () => {
            prev.disabled = track.scrollLeft <= 1;
            next.disabled = track.scrollLeft + track.clientWidth >= track.scrollWidth - 1;
            nav.hidden = prev.disabled && next.disabled;
        };

        nav.addEventListener('click', (event) => {
            const button = event.target.closest('button');

            if (button) {
                track.scrollBy({ left: Number(button.dataset.dir) * track.clientWidth * 0.9 });
            }
        });

        track.addEventListener('scroll', update, { passive: true });
        new ResizeObserver(() => {
            update();
            clamp();
        }).observe(track);
        update();
    });
})();
