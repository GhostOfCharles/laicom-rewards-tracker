(function () {
            document.querySelectorAll('.lrts-ticket-modal-attach-input').forEach((input) => {
                input.addEventListener('change', () => {
                    const nameSpan = document.querySelector('.lrts-ticket-modal-attach-name[data-for="' + input.id + '"]');
                    if (!nameSpan) return;
                    if (!input.files.length) {
                        nameSpan.textContent = 'NO FILE CHOSEN';
                        nameSpan.classList.remove('has-file', 'is-error');
                        return;
                    }
                    const file = input.files[0];
                    if (file.size > 5 * 1024 * 1024) {
                        nameSpan.textContent = 'FILE TOO LARGE - MAX 5MB';
                        nameSpan.classList.add('is-error');
                        nameSpan.classList.remove('has-file');
                        input.value = '';
                        return;
                    }
                    nameSpan.textContent = file.name.toUpperCase();
                    nameSpan.classList.add('has-file');
                    nameSpan.classList.remove('is-error');
                });
            });
        })();