(function () {
            const sectionButtons = document.querySelectorAll('[data-promos-section]');
            const sectionPanes   = document.querySelectorAll('[data-promos-pane]');

            if (!sectionButtons.length || !sectionPanes.length) return;

            sectionButtons.forEach((btn) => {
                btn.addEventListener('click', () => {
                    const target = btn.getAttribute('data-promos-section');

                    sectionButtons.forEach((b) => b.classList.toggle('is-active', b === btn));

                    sectionPanes.forEach((pane) => {
                        pane.classList.toggle('is-active', pane.getAttribute('data-promos-pane') === target);
                    });
                });
            });
        })();