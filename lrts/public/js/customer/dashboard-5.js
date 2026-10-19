(function () {
            const form = document.getElementById('createTicketForm');
            if (!form) return;

            const MAX_FILE_BYTES = 5 * 1024 * 1024;

            // Category chips
            const chips = form.querySelectorAll('.lrts-create-chip');
            const categoryInput = document.getElementById('lrtsCreateCategory');
            const categoryError = document.getElementById('lrtsCategoryError');

            chips.forEach((chip) => {
                chip.addEventListener('click', () => {
                    chips.forEach((c) => c.classList.remove('is-selected'));
                    chip.classList.add('is-selected');
                    categoryInput.value = chip.getAttribute('data-value');
                    if (categoryError) categoryError.style.display = 'none';
                });
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
                    } else if (len > max * 0.85) {
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
            const fileRemove = document.getElementById('lrtsFileRemove');
            const fileError = document.getElementById('lrtsFileError');

            function showFileInfo(file) {
                fileName.textContent = file.name.toUpperCase();
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
                modal.addEventListener('hidden.bs.modal', () => {
                    form.reset();
                    chips.forEach((c) => c.classList.remove('is-selected'));
                    categoryInput.value = '';
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