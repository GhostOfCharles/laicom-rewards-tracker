(() => {
    document.querySelectorAll('[data-auth-password-toggle]').forEach((toggle) => {
        const input = document.getElementById(toggle.getAttribute('aria-controls'));
        if (!input) return;

        toggle.addEventListener('click', () => {
            const showing = input.type === 'password';
            input.type = showing ? 'text' : 'password';
            toggle.querySelector('[data-password-label]').textContent = showing ? 'Hide' : 'Show';
            toggle.setAttribute('aria-label', `${showing ? 'Hide' : 'Show'} ${toggle.dataset.passwordName}`);
            toggle.setAttribute('aria-pressed', String(showing));
        });
    });

    const forms = [...document.querySelectorAll('[data-auth-form]')];
    forms.forEach((form) => {
        const button = form.querySelector('[data-auth-submit]');
        if (!button) return;
        const label = button.querySelector('[data-auth-submit-label]');
        button.dataset.idleLabel = label.textContent;

        form.addEventListener('submit', (event) => {
            // Respect other validation/provider handlers that cancel submission.
            queueMicrotask(() => {
                if (event.defaultPrevented) return;
                form.setAttribute('aria-busy', 'true');
                button.disabled = true;
                label.textContent = button.dataset.loadingLabel;
                button.querySelector('.auth-submit-spinner').hidden = false;
            });
        });
    });

    // Restore the form when returning from a submission through browser history.
    window.addEventListener('pageshow', () => {
        forms.forEach((form) => {
            form.removeAttribute('aria-busy');
            const button = form.querySelector('[data-auth-submit]');
            if (!button) return;
            button.disabled = false;
            button.querySelector('[data-auth-submit-label]').textContent = button.dataset.idleLabel;
            button.querySelector('.auth-submit-spinner').hidden = true;
        });
    });
})();
