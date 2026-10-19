document.addEventListener('DOMContentLoaded', function() {
            const addBtn = document.getElementById('add_item_btn');
            const removeSelectedBtn = document.getElementById('remove_item_btn');
            const validationMessage = document.getElementById('order_validation_message');
            const combobox = document.getElementById('product_combobox');
            const productComboboxToggle = document.querySelector('#product_combobox_wrapper .lrts-product-combobox-toggle');
            const productOptions = document.getElementById('product_options');
            const productComboboxWrapper = document.getElementById('product_combobox_wrapper');
            const quantity = document.getElementById('product_quantity');

            const mainListbox = document.getElementById('item_listbox');
            const emptyProductsMarkup = document.getElementById('empty_msg').outerHTML;
            const modalSummaryList = document.getElementById('modal_summary_list');
            const hiddenContainer = document.getElementById('hidden_inputs_container');
            const processReceiptButton = document.getElementById('process_receipt_btn');

            let itemIndex = 0;
            const productOptionItems = productOptions ? Array.from(productOptions.querySelectorAll('.lrts-product-combobox-option')) : [];
            const noProductResults = productOptions?.querySelector('.lrts-product-combobox-empty');
            let activeProductOption = null;
            let selectedProductOption = null;
            let selectedOrderItem = null;

            function parseOrderQuantity(value) {
                const normalized = String(value ?? '').trim();
                if (!/^\d+$/.test(normalized)) return null;
                const parsed = Number(normalized);
                return Number.isSafeInteger(parsed) && parsed >= 1 && parsed <= 9999 ? parsed : null;
            }

            function showOrderValidation(message = '') {
                if (!validationMessage) return;
                if (!message && mainListbox.querySelector('.lrts-order-line-quantity.is-invalid')) {
                    message = 'Each product quantity must be a whole number from 1 to 9,999.';
                }
                validationMessage.textContent = message;
                validationMessage.hidden = !message;
            }

            function selectOrderItem(item) {
                if (!item) return;
                mainListbox.querySelectorAll('.lrts-order-item').forEach((row) => {
                    const isSelected = row === item;
                    row.classList.toggle('is-selected', isSelected);
                    row.setAttribute('aria-selected', String(isSelected));
                });
                selectedOrderItem = item;
                if (removeSelectedBtn) removeSelectedBtn.disabled = false;
            }

            function updateOrderControls() {
                const rows = Array.from(mainListbox.querySelectorAll('.lrts-order-item'));
                const hasInvalidQuantity = rows.some((row) => parseOrderQuantity(row.querySelector('.lrts-order-line-quantity')?.value) === null);
                if (processReceiptButton) processReceiptButton.disabled = rows.length === 0 || hasInvalidQuantity;
                if (removeSelectedBtn) removeSelectedBtn.disabled = !selectedOrderItem;
                const output = document.getElementById('promotionEligibility');
                if (output && window.rtsPromotions) {
                    output.replaceChildren();
                    const totals = new Map();
                    rows.forEach(row => totals.set(row.dataset.productName, (totals.get(row.dataset.productName)||0) + Number(row.querySelector('.lrts-order-line-quantity')?.value||0)));
                    totals.forEach((quantity,name) => window.rtsPromotions.filter(p => p.product.toLowerCase()===name.toLowerCase()).forEach(p => {
                        const line = document.createElement('div');
                        line.textContent = `${name}: ${p.title} - ${quantity>=p.required ? 'purchase threshold met (subject to order date, receipt review and stock)' : (p.required-quantity)+' more to qualify'}`;
                        output.append(line);
                    }));
                }
            }

            function clearActiveProductOption() {
                if (activeProductOption) activeProductOption.classList.remove('is-active');
                activeProductOption = null;
                combobox?.removeAttribute('aria-activedescendant');
            }

            function filterProductOptions(query = combobox.value.trim()) {
                const normalizedQuery = query.toLocaleLowerCase();
                let visibleCount = 0;

                productOptionItems.forEach((option) => {
                    const matches = option.dataset.productName.toLocaleLowerCase().includes(normalizedQuery);
                    option.hidden = !matches;
                    if (matches) visibleCount++;
                });

                if (noProductResults) noProductResults.hidden = visibleCount > 0;
                clearActiveProductOption();
            }

            function openProductDropdown(showAll = false) {
                if (!productOptions || !combobox) return;
                productOptions.hidden = false;
                productComboboxWrapper.classList.add('is-open');
                combobox.setAttribute('aria-expanded', 'true');
                productComboboxToggle?.setAttribute('aria-expanded', 'true');
                filterProductOptions(showAll ? '' : combobox.value.trim());
                requestAnimationFrame(() => {
                    if (combobox.getAttribute('aria-expanded') === 'true') productOptions.classList.add('is-open');
                });
            }

            function closeProductDropdown() {
                if (!productOptions || !combobox) return;
                productOptions.classList.remove('is-open');
                productComboboxWrapper.classList.remove('is-open');
                combobox.setAttribute('aria-expanded', 'false');
                productComboboxToggle?.setAttribute('aria-expanded', 'false');
                clearActiveProductOption();
                productOptionItems.forEach((option) => option.setAttribute('aria-selected', option === selectedProductOption ? 'true' : 'false'));
                window.setTimeout(() => {
                    if (combobox.getAttribute('aria-expanded') !== 'true') productOptions.hidden = true;
                }, 160);
            }

            function chooseProductOption(option) {
                if (!option || !combobox) return;
                combobox.value = option.dataset.productName;
                selectedProductOption = option;
                productOptionItems.forEach((item) => item.setAttribute('aria-selected', item === option ? 'true' : 'false'));
                closeProductDropdown();
                combobox.focus();
            }

            function setActiveProductOption(option) {
                clearActiveProductOption();
                activeProductOption = option;
                if (!option) return;
                option.classList.add('is-active');
                productOptionItems.forEach((item) => item.setAttribute('aria-selected', item === option ? 'true' : 'false'));
                combobox.setAttribute('aria-activedescendant', option.id);
                option.scrollIntoView({ block: 'nearest' });
            }

            if (combobox && productOptions) {
                combobox.addEventListener('focus', () => {
                    openProductDropdown(true);
                    if (selectedProductOption) combobox.select();
                });
                combobox.addEventListener('click', () => {
                    if (combobox.getAttribute('aria-expanded') !== 'true') {
                        openProductDropdown(true);
                        if (selectedProductOption) combobox.select();
                    }
                });
                combobox.addEventListener('input', () => {
                    showOrderValidation();
                    selectedProductOption = null;
                    productOptionItems.forEach((option) => option.setAttribute('aria-selected', 'false'));
                    openProductDropdown();
                });
                combobox.addEventListener('keydown', (event) => {
                    if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                        event.preventDefault();
                        if (combobox.getAttribute('aria-expanded') !== 'true') openProductDropdown(true);
                        const availableOptions = productOptionItems.filter((option) => !option.hidden);
                        if (!availableOptions.length) return;
                        const currentIndex = availableOptions.indexOf(activeProductOption);
                        const nextIndex = event.key === 'ArrowDown'
                            ? (currentIndex + 1) % availableOptions.length
                            : (currentIndex <= 0 ? availableOptions.length - 1 : currentIndex - 1);
                        setActiveProductOption(availableOptions[nextIndex]);
                    } else if (event.key === 'Enter') {
                        event.preventDefault();
                        const exactMatch = productOptionItems.find((option) => option.dataset.productName.toLocaleLowerCase() === combobox.value.trim().toLocaleLowerCase());
                        if (combobox.getAttribute('aria-expanded') === 'true') chooseProductOption(activeProductOption || exactMatch);
                        else chooseProductOption(exactMatch);
                    } else if (event.key === 'Escape' && combobox.getAttribute('aria-expanded') === 'true') {
                        event.preventDefault();
                        closeProductDropdown();
                    }
                });

                productComboboxToggle?.addEventListener('click', () => {
                    const wasOpen = combobox.getAttribute('aria-expanded') === 'true';
                    combobox.focus({ preventScroll: true });
                    if (wasOpen) {
                        closeProductDropdown();
                    } else {
                        openProductDropdown(true);
                        if (selectedProductOption) combobox.select();
                    }
                });

                productComboboxWrapper.addEventListener('focusout', () => {
                    window.setTimeout(() => {
                        if (!productComboboxWrapper.contains(document.activeElement)) closeProductDropdown();
                    }, 0);
                });

                productOptionItems.forEach((option) => {
                    option.addEventListener('mousedown', (event) => event.preventDefault());
                    option.addEventListener('click', () => chooseProductOption(option));
                });

                document.addEventListener('pointerdown', (event) => {
                    if (!productComboboxWrapper.contains(event.target)) closeProductDropdown();
                });
            }

            quantity?.addEventListener('input', () => showOrderValidation());

            if (addBtn) {
                addBtn.addEventListener('click', function() {
                    const typedProductName = combobox.value.trim();
                    const selectedOption = productOptionItems.find((option) =>
                        option.dataset.productName.toLowerCase() === typedProductName.toLowerCase()
                    );
                    const productName = selectedOption?.dataset.productName ?? '';
                    const productImage = selectedOption?.dataset.productImage ?? '';
                    const productInitials = selectedOption?.dataset.productInitials ?? '?';
                    const qty = parseOrderQuantity(quantity.value);

                    if (!selectedOption) {
                        showOrderValidation('Choose a product from the list before adding it.');
                        combobox.focus();
                        return;
                    }
                    if (qty === null) {
                        showOrderValidation('Enter a whole-number amount from 1 to 9,999.');
                        quantity.focus();
                        return;
                    }
                    if (mainListbox.querySelectorAll('li.product-list-item').length >= 50) {
                        showOrderValidation('An order can contain no more than 50 product lines.');
                        return;
                    }
                    showOrderValidation();

                    const mainEmpty = document.getElementById('empty_msg');
                    if (mainEmpty) mainEmpty.remove();

                    const modalEmpty = document.getElementById('modal_empty_msg');
                    if (modalEmpty) modalEmpty.remove();

                    const uniqueId = 'item-row-' + itemIndex;

                    const mainLi = document.createElement('li');
                    mainLi.className = 'product-list-item lrts-order-item d-flex align-items-center justify-content-between p-2';
                    mainLi.id = uniqueId + '-main';
                    mainLi.dataset.itemId = uniqueId;
                    mainLi.dataset.productName = productName;
                    mainLi.setAttribute('role', 'option');
                    mainLi.setAttribute('aria-selected', 'false');
                    mainLi.tabIndex = 0;

                    const productInfo = document.createElement('div');
                    productInfo.className = 'd-flex align-items-center flex-grow-1';
                    const createProductPlaceholder = () => {
                        const svgNamespace = 'http://www.w3.org/2000/svg';
                        const svg = document.createElementNS(svgNamespace, 'svg');
                        svg.setAttribute('viewBox', '0 0 100 100');
                        svg.setAttribute('role', 'img');
                        svg.setAttribute('aria-label', productName);
                        svg.classList.add('lrts-product-placeholder', 'border', 'border-dark', 'me-3');
                        svg.style.cssText = 'width:50px;height:50px;flex:0 0 50px;';

                        const background = document.createElementNS(svgNamespace, 'rect');
                        background.setAttribute('x', '1');
                        background.setAttribute('y', '1');
                        background.setAttribute('width', '98');
                        background.setAttribute('height', '98');
                        background.setAttribute('fill', '#fff');
                        background.setAttribute('stroke', '#12284c');
                        background.setAttribute('stroke-width', '2');

                        const accent = document.createElementNS(svgNamespace, 'path');
                        accent.setAttribute('d', 'M8 8h20v6H14v14H8z');
                        accent.setAttribute('fill', '#ed1b24');

                        const text = document.createElementNS(svgNamespace, 'text');
                        text.setAttribute('x', '50');
                        text.setAttribute('y', '55');
                        text.setAttribute('text-anchor', 'middle');
                        text.setAttribute('dominant-baseline', 'middle');
                        text.setAttribute('fill', '#12284c');
                        text.setAttribute('font-family', 'Segoe UI, Arial, sans-serif');
                        text.setAttribute('font-size', productInitials.length > 1 ? '34' : '46');
                        text.setAttribute('font-weight', '800');
                        text.textContent = productInitials;

                        svg.append(background, accent, text);
                        return svg;
                    };
                    let productThumbnail;
                    if (productImage) {
                        productThumbnail = document.createElement('img');
                        productThumbnail.src = productImage;
                        productThumbnail.alt = productName;
                        productThumbnail.className = 'border border-dark me-3';
                        productThumbnail.style.cssText = 'width: 50px; height: 50px; object-fit: contain;';
                        productThumbnail.onerror = () => productThumbnail.replaceWith(createProductPlaceholder());
                    } else {
                        productThumbnail = createProductPlaceholder();
                    }
                    productInfo.appendChild(productThumbnail);
                    const name = document.createElement('span');
                    name.className = 'fw-bold small text-uppercase';
                    name.textContent = productName;
                    productInfo.appendChild(name);

                    const productActions = document.createElement('div');
                    productActions.className = 'lrts-order-line-actions d-flex align-items-center';
                    const quantityBox = document.createElement('label');
                    quantityBox.className = 'lrts-order-line-quantity-wrap text-center';
                    const quantityLabel = document.createElement('span');
                    quantityLabel.className = 'd-block';
                    quantityLabel.textContent = 'QTY';
                    const quantityValue = document.createElement('input');
                    quantityValue.className = 'lrts-order-line-quantity form-control';
                    quantityValue.type = 'number';
                    quantityValue.min = '1';
                    quantityValue.max = '9999';
                    quantityValue.step = '1';
                    quantityValue.inputMode = 'numeric';
                    quantityValue.value = String(qty);
                    quantityValue.setAttribute('aria-label', `Quantity for ${productName}`);
                    quantityBox.append(quantityLabel, quantityValue);
                    productActions.append(quantityBox);
                    mainLi.append(productInfo, productActions);
                    mainListbox.appendChild(mainLi);
                    selectOrderItem(mainLi);

                    const modalLi = document.createElement('li');
                    modalLi.id = uniqueId + '-modal';
                    modalLi.textContent = `\u2022 ${productName} - QTY ${qty}`;
                    modalSummaryList.appendChild(modalLi);

                    const hiddenWrapper = document.createElement('div');
                    hiddenWrapper.id = uniqueId + '-hidden';

                    const productNameInput = document.createElement('input');
                    productNameInput.type = 'hidden';
                    productNameInput.name = `items[${itemIndex}][product_name]`;
                    productNameInput.value = productName;
                    const productQuantityInput = document.createElement('input');
                    productQuantityInput.type = 'hidden';
                    productQuantityInput.name = `items[${itemIndex}][quantity]`;
                    productQuantityInput.value = qty;
                    hiddenWrapper.append(productNameInput, productQuantityInput);
                    hiddenContainer.appendChild(hiddenWrapper);

                    const syncLineQuantity = () => {
                        const lineQty = parseOrderQuantity(quantityValue.value);
                        const isValid = lineQty !== null;
                        quantityValue.classList.toggle('is-invalid', !isValid);
                        quantityValue.setAttribute('aria-invalid', String(!isValid));
                        if (isValid) {
                            productQuantityInput.value = String(lineQty);
                            modalLi.textContent = `\u2022 ${productName} - QTY ${lineQty}`;
                        }
                        const hasInvalidLine = mainListbox.querySelector('.lrts-order-line-quantity.is-invalid');
                        if (!isValid) showOrderValidation('Each product quantity must be a whole number from 1 to 9,999.');
                        else if (!hasInvalidLine) showOrderValidation();
                        updateOrderControls();
                    };
                    quantityValue.addEventListener('input', syncLineQuantity);
                    quantityValue.addEventListener('change', syncLineQuantity);
                    mainLi.addEventListener('click', (event) => {
                        if (!event.target.closest('.lrts-order-line-quantity-wrap')) selectOrderItem(mainLi);
                    });
                    mainLi.addEventListener('focusin', () => selectOrderItem(mainLi));
                    mainLi.addEventListener('keydown', (event) => {
                        if (event.target !== mainLi) return;
                        if (event.key === 'Enter' || event.key === ' ') {
                            event.preventDefault();
                            selectOrderItem(mainLi);
                        }
                    });
                    updateOrderControls();

                    itemIndex++;
                    combobox.value = '';
                    selectedProductOption = null;
                    productOptionItems.forEach((option) => option.setAttribute('aria-selected', 'false'));
                    closeProductDropdown();
                    quantity.value = 1;
                });
            }

            if (removeSelectedBtn) {
                removeSelectedBtn.addEventListener('click', () => {
                    if (!selectedOrderItem) return;
                    const targetId = selectedOrderItem.dataset.itemId;
                    document.getElementById(targetId + '-modal')?.remove();
                    document.getElementById(targetId + '-hidden')?.remove();
                    selectedOrderItem.remove();
                    selectedOrderItem = null;

                    if (mainListbox.querySelectorAll('.lrts-order-item').length === 0) {
                        mainListbox.innerHTML = emptyProductsMarkup;
                        modalSummaryList.innerHTML = '<li class="text-muted" id="modal_empty_msg">No items recorded.</li>';
                    }
                    showOrderValidation();
                    updateOrderControls();
                });
            }

            updateOrderControls();
        });