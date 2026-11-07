(function () {
            const form = document.getElementById('createTicketForm');
            if (!form) return;

            const MAX_FILE_BYTES = 5 * 1024 * 1024;

            // Category chips
            const chips = form.querySelectorAll('.lrts-create-chip');
            const categoryInput = document.getElementById('lrtsCreateCategory');
            const categoryError = document.getElementById('lrtsCategoryError');

            const syncCategoryChips = () => {
                const selected = categoryInput.value || Array.from(chips).find(chip => chip.classList.contains('is-selected'))?.dataset.value;
                let tabStopSet = false;
                chips.forEach((chip) => {
                    const checked = chip.dataset.value === selected;
                    chip.classList.toggle('is-selected', checked);
                    chip.setAttribute('aria-checked', String(checked));
                    chip.tabIndex = checked || (!selected && !tabStopSet) ? 0 : -1;
                    tabStopSet ||= chip.tabIndex === 0;
                });
            };

            chips.forEach((chip) => {
                chip.addEventListener('click', () => {
                    chips.forEach((c) => c.classList.remove('is-selected'));
                    chip.classList.add('is-selected');
                    categoryInput.value = chip.getAttribute('data-value');
                    if (categoryError) categoryError.style.display = 'none';
                });
                chip.addEventListener('click', syncCategoryChips);
            });
            const categoryGroup = document.getElementById('lrtsCreateChips');
            categoryGroup?.addEventListener('keydown', (event) => {
                const keys = ['ArrowRight', 'ArrowDown', 'ArrowLeft', 'ArrowUp', 'Home', 'End'];
                if (!keys.includes(event.key)) return;
                event.preventDefault();
                const current = Array.from(chips).indexOf(document.activeElement);
                const forward = event.key === 'ArrowRight' || event.key === 'ArrowDown';
                const next = event.key === 'Home' ? 0 : event.key === 'End' ? chips.length - 1
                    : (current + (forward ? 1 : -1) + chips.length) % chips.length;
                chips[next].click();
                chips[next].focus();
            });

            // Character counters
            form.querySelectorAll('[data-counter-for]').forEach((counter) => {
                const inputId = counter.getAttribute('data-counter-for');
                const max = parseInt(counter.getAttribute('data-max'), 10) || 0;
                const input = document.getElementById(inputId);
                if (!input) return;

                const update = () => {
                    const len = input.value.length;
                    counter.textContent = len + ' / ' + max;
                    counter.classList.remove('is-warn', 'is-over');
                    if (len > max) {
                        counter.classList.add('is-over');
                    } else if (len > max * 0.9) {
                        counter.classList.add('is-warn');
                    }
                };
                input.addEventListener('input', update);
                update();
            });

            // Related order preview
            const receiptSelect = document.getElementById('lrtsCreateReceipt');
            const preview = document.getElementById('lrtsReceiptPreview');
            const previewNum = document.getElementById('lrtsReceiptNum');
            const previewDate = document.getElementById('lrtsReceiptDate');
            const previewStatus = document.getElementById('lrtsReceiptStatus');
            const receiptHint = document.getElementById('lrtsReceiptHint');

            if (receiptSelect) {
                receiptSelect.addEventListener('change', () => {
                    const opt = receiptSelect.options[receiptSelect.selectedIndex];
                    if (!opt.value) {
                        preview.style.display = 'none';
                        receiptHint.style.display = 'block';
                        return;
                    }
                    previewNum.textContent = '#' + opt.getAttribute('data-number');
                    previewDate.textContent = opt.getAttribute('data-date');
                    const status = opt.getAttribute('data-status');
                    previewStatus.textContent = status.toUpperCase();
                    previewStatus.className = 'lrts-create-receipt-status is-' + status;
                    preview.style.display = 'block';
                    receiptHint.style.display = 'none';
                });
            }

            // Attachment dropzone
            const fileInput = document.getElementById('lrtsCreateAttachment');
            const dropzone = document.getElementById('lrtsDropzone');
            const fileInfo = document.getElementById('lrtsFileInfo');
            const fileName = document.getElementById('lrtsFileName');
            const fileSize = fileInfo?.querySelector('.lrts-create-file-size');
            const fileRemove = document.getElementById('lrtsFileRemove');
            const fileError = document.getElementById('lrtsFileError');

            function showFileInfo(file) {
                fileName.textContent = file.name.toUpperCase();
                if (fileSize) fileSize.textContent = file.size < 1024 * 1024
                    ? (file.size / 1024).toFixed(1) + ' KB'
                    : (file.size / 1024 / 1024).toFixed(2) + ' MB';
                fileInfo.style.display = 'flex';
                dropzone.style.display = 'none';
                fileError.style.display = 'none';
                fileError.textContent = '';
            }

            function resetFile() {
                fileInput.value = '';
                fileInfo.style.display = 'none';
                dropzone.style.display = 'flex';
                fileError.style.display = 'none';
                fileError.textContent = '';
            }

            function validateAndShow(file) {
                if (!file) return;
                if (file.size > MAX_FILE_BYTES) {
                    const mb = (file.size / 1024 / 1024).toFixed(2);
                    fileError.textContent = 'File is too large (' + mb + ' MB). Maximum is 5 MB.';
                    fileError.style.display = 'block';
                    fileInput.value = '';
                    return;
                }
                showFileInfo(file);
            }

            if (fileInput) {
                fileInput.addEventListener('change', () => {
                    if (fileInput.files.length) validateAndShow(fileInput.files[0]);
                    else resetFile();
                });
            }

            if (fileRemove) {
                fileRemove.addEventListener('click', (e) => {
                    e.preventDefault();
                    resetFile();
                });
            }

            if (dropzone) {
                ['dragenter', 'dragover'].forEach((evt) => {
                    dropzone.addEventListener(evt, (e) => {
                        e.preventDefault();
                        dropzone.classList.add('is-dragover');
                    });
                });
                ['dragleave', 'drop'].forEach((evt) => {
                    dropzone.addEventListener(evt, (e) => {
                        e.preventDefault();
                        dropzone.classList.remove('is-dragover');
                    });
                });
                dropzone.addEventListener('drop', (e) => {
                    const files = e.dataTransfer.files;
                    if (files.length) {
                        fileInput.files = files;
                        validateAndShow(files[0]);
                    }
                });
            }

            // Submit validation
            form.addEventListener('submit', (e) => {
                if (!categoryInput.value) {
                    e.preventDefault();
                    if (categoryError) categoryError.style.display = 'block';
                    return;
                }

                if (fileInput.files.length && fileInput.files[0].size > MAX_FILE_BYTES) {
                    e.preventDefault();
                    fileError.textContent = 'Please remove or replace the oversized file before submitting.';
                    fileError.style.display = 'block';
                }
            });

            // Reset on modal close
            const modal = document.getElementById('createTicketModal');
            if (modal) {
                let opener = null;
                modal.addEventListener('show.bs.modal', (event) => {
                    opener = event.relatedTarget instanceof HTMLElement ? event.relatedTarget : document.activeElement;
                    syncCategoryChips();
                });
                modal.addEventListener('shown.bs.modal', () => {
                    document.getElementById('lrtsCreateSubject')?.focus();
                });
                modal.addEventListener('hidden.bs.modal', () => {
                    if (opener?.isConnected) opener.focus();
                    opener = null;
                });
                modal.addEventListener('shown.bs.modal', syncCategoryChips);
                form.addEventListener('submit', (event) => {
                    if (event.defaultPrevented) return;
                    const submit = form.querySelector('.lrts-create-btn-submit');
                    if (!submit) return;
                    submit.disabled = true;
                    submit.querySelector('.bi-send-fill').hidden = true;
                    submit.querySelector('.lrts-create-spinner').hidden = false;
                    submit.querySelector('.lrts-create-submit-label').textContent = 'Sending…';
                });
                modal.addEventListener('hidden.bs.modal', () => {
                    form.reset();
                    chips.forEach((c) => c.classList.remove('is-selected'));
                    categoryInput.value = '';
                    syncCategoryChips();
                    resetFile();
                    if (preview) preview.style.display = 'none';
                    if (receiptHint) receiptHint.style.display = 'block';
                    form.querySelectorAll('[data-counter-for]').forEach((c) => {
                        const max = c.getAttribute('data-max');
                        c.textContent = '0 / ' + max;
                        c.classList.remove('is-warn', 'is-over');
                    });
                });
            }
        })();
