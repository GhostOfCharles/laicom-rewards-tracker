(() => {
    const active = new Map();
    // Accessible fallback names for legacy controls whose visual labels lack a for attribute.
    document.querySelectorAll('input:not([type="hidden"]),select,textarea').forEach(field => {
        if (!field.getAttribute('aria-label') && !field.getAttribute('aria-labelledby') && !field.labels?.length && field.name) {
            field.setAttribute('aria-label', field.name.replace(/[_\[\]]+/g, ' ').trim());
        }
    });
    document.addEventListener('submit', event => {
        const form = event.target, button = event.submitter;
        if (form.method.toLowerCase() !== 'post' || !button || button.name) return;
        if (active.has(form)) { event.preventDefault(); return; }
        // Wait until every submit handler has finished validation and confirmation.
        setTimeout(() => {
            if (event.defaultPrevented) return;
            active.set(form, {button, html:button.innerHTML, minWidth:button.style.minWidth});
            button.style.minWidth = button.getBoundingClientRect().width + 'px';
            button.disabled = true; button.textContent = 'Submitting...'; form.setAttribute('aria-busy', 'true');
        }, 0);
    });
    window.addEventListener('pageshow', () => {
        active.forEach(({button,html,minWidth},form) => {button.disabled=false;button.innerHTML=html;button.style.minWidth=minWidth;form.removeAttribute('aria-busy');});
        active.clear();
    });
})();
