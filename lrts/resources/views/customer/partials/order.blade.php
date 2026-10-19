                <!-- TAB 1: SUBMIT NEW ORDER -->
                <div class="tab-pane fade show active" id="order" role="tabpanel">
                    <div class="card rts-card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span>SELECT BOUGHT PRODUCTS</span>
                            <span class="small fw-normal d-none d-md-inline lrts-order-hint">Build your purchase list before submitting.</span>
                        </div>
                        <div class="card-body p-4">

                            <div class="lrts-order-controls">
                                <div class="lrts-product-combobox me-2" id="product_combobox_wrapper">
                                    <input type="text" id="product_combobox" class="form-select border-dark border-2 rounded-0 fw-bold small lrts-product-combobox-input" placeholder="Choose a product..." aria-label="Choose a bought product" role="combobox" aria-autocomplete="list" aria-haspopup="listbox" aria-expanded="false" aria-controls="product_options" autocomplete="off">
                                    <button type="button" class="lrts-product-combobox-toggle" aria-label="Toggle product list" aria-controls="product_options" aria-expanded="false"><i class="bi bi-chevron-down" aria-hidden="true"></i></button>
                                    <div id="product_options" class="lrts-product-combobox-options" role="listbox" aria-label="Products" hidden>
                                        @foreach ($products as $product)
                                            @php $po = app(\App\Services\ProductImageResolver::class)->resolve($product); @endphp
                                            <button type="button" id="product-option-{{ $loop->index }}" class="lrts-product-combobox-option" role="option" aria-selected="false"
                                                    data-product-name="{{ $product->name }}"
                                                    data-product-image="{{ $po['src'] ?? '' }}"
                                                    data-product-initials="{{ $po['initials'] }}">
                                                @if ($po['src'])
                                                    <img src="{{ $po['src'] }}" alt="" loading="lazy" onerror="this.hidden=true;this.nextElementSibling.hidden=false;">
                                                    <x-product-placeholder :label="$po['label']" :initials="$po['initials']" :size="42" :hidden="true" />
                                                @else
                                                    <x-product-placeholder :label="$po['label']" :initials="$po['initials']" :size="42" />
                                                @endif
                                                <span>{{ $product->name }}</span>
                                            </button>
                                        @endforeach
                                        <div class="lrts-product-combobox-empty" hidden>No matching products found.</div>
                                    </div>
                                </div>
                                <label for="product_quantity" class="lrts-order-amount-label">AMOUNT</label>
                                <input type="number" id="product_quantity" class="form-control border-dark border-2 rounded-0 fw-bold text-center lrts-order-quantity" min="1" max="9999" step="1" value="1" aria-label="Amount to add">
                                <button type="button" id="add_item_btn" class="btn rts-btn-primary lrts-order-action" aria-label="Add selected product">ADD</button>
                                <button type="button" id="remove_item_btn" class="btn rts-btn-primary lrts-order-action" aria-label="Remove selected product" disabled>REMOVE</button>
                            </div>
                            <div id="promotionEligibility" class="small text-muted mb-2" aria-live="polite"></div>
                            <div id="order_validation_message" class="lrts-order-validation" role="alert" aria-live="polite" hidden></div>

                            <div class="mb-4 position-relative lrts-order-item-list">
                                <ul id="item_listbox" class="list-unstyled mb-0" role="listbox" aria-label="Selected products">
                                    <li class="lrts-product-empty" id="empty_msg">
                                        <span class="lrts-product-empty-icon" aria-hidden="true"><i class="bi bi-cart3"></i></span>
                                        <strong>No products selected yet</strong>
                                        <p>Choose a product above and click Add to build your purchase list.</p>
                                    </li>
                                </ul>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mt-2">
                                <button type="button" class="btn rts-btn-primary px-4 small" onclick="document.getElementById('promos-tab').click()">PROMOS &amp; REWARDS</button>
                                <button type="button" id="process_receipt_btn" class="btn rts-btn-accent px-4 small" data-bs-toggle="modal" data-bs-target="#processReceiptModal">PROCESS RECEIPT</button>
                            </div>

                        </div>
                    </div>
                </div>

