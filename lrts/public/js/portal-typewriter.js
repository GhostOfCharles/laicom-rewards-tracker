(() => {
    const heading = document.querySelector('.lrts-customer-login-page #auth-title');
    if (!heading) return;

    const phrase = heading.textContent;
    const motion = window.matchMedia('(prefers-reduced-motion: reduce)');
    let timer;

    const stop = () => window.clearTimeout(timer);
    const start = () => {
        stop();
        heading.classList.remove('portal-typewriter');
        heading.textContent = phrase;
        if (motion.matches || !heading.isConnected) return;

        // Keep the original text's dimensions and a stable accessible name.
        heading.setAttribute('aria-label', phrase);
        heading.classList.add('portal-typewriter');
        const reserve = document.createElement('span');
        reserve.className = 'portal-typewriter-reserve';
        reserve.setAttribute('aria-hidden', 'true');
        reserve.textContent = phrase;
        const display = document.createElement('span');
        display.className = 'portal-typewriter-display';
        display.setAttribute('aria-hidden', 'true');
        const text = document.createElement('span');
        text.className = 'portal-typewriter-text';
        display.append(text);
        heading.replaceChildren(reserve, display);

        let length = 0;
        let deleting = false;
        const tick = () => {
            if (!heading.isConnected) return;
            length += deleting ? -1 : 1;
            text.textContent = phrase.slice(0, length);
            // Move only the caret's transform, keeping layout geometry stable.
            const range = document.createRange();
            range.selectNodeContents(text);
            const lines = [...range.getClientRects()];
            const last = lines.at(-1);
            const bounds = display.getBoundingClientRect();
            display.style.setProperty('--portal-caret-offset', `${last ? last.right - bounds.left - bounds.width / 2 : 0}px`);
            display.style.setProperty('--portal-caret-line', `${last ? last.top - lines[0].top : 0}px`);
            let delay = deleting ? 45 : 80;
            if (length === phrase.length) {
                deleting = true;
                delay = 1500;
            } else if (length === 0) {
                deleting = false;
                delay = 400;
            }
            timer = window.setTimeout(tick, delay);
        };
        timer = window.setTimeout(tick, 80);
    };

    motion.addEventListener('change', start);
    window.addEventListener('pageshow', start);
    window.addEventListener('pagehide', (event) => {
        stop();
        if (!event.persisted) {
            motion.removeEventListener('change', start);
            window.removeEventListener('pageshow', start);
        }
    });
    start();
})();
