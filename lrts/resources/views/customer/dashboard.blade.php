<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LRTS - Customer Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="{{ asset('css/laicom.css') }}">
    <style>
        body { background-color: #f8f9fa; }
        .navbar-custom { background-color: #12284c; }
        .card-custom { border: 2px solid #12284c; border-radius: 8px; }
        .card-header-custom { background-color: #12284c; color: white; font-weight: bold; letter-spacing: 1px; }
        .nav-tabs .nav-link { color: #12284c; font-weight: 700; border: 2px solid transparent; border-bottom: none; }
        .nav-tabs .nav-link.active { color: #12284c; border-color: #12284c #12284c #f8f9fa; background-color: #f8f9fa; }
        .nav-tabs { border-bottom: 2px solid #12284c; }

        /* Wireframe Specific Styling */
        .product-list-item { border-bottom: 2px solid #12284c; }
        .product-list-item:last-child { border-bottom: none; }
        .qty-box { border: 2px solid #12284c; padding: 2px 8px; font-weight: bold; min-width: 40px; text-align: center; }
    </style>
</head>
<body class="laicom-page">

    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark laicom-navbar mb-4 py-3 shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold d-flex align-items-center" href="#order">
                <img src="{{ asset('images/laicom-logo.png') }}" alt="Laicom" height="30" class="me-2"> LRTS CUSTOMER
            </a>
            <div class="d-flex align-items-center text-white">
                <span class="me-3 fw-semibold small d-none d-md-inline">WELCOME, {{ strtoupper(auth()->user()->name) }}</span>
                <div class="dropdown me-3">
                    <button type="button" class="btn btn-sm btn-outline-light rounded-0 laicom-bell" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Open notifications">
                        <i class="bi bi-bell-fill fs-6"></i>
                        @if ($pendingReceiptCount + $availableRewards->count() > 0)<span class="badge rounded-pill bg-danger">{{ $pendingReceiptCount + $availableRewards->count() }}</span>@endif
                    </button>
                    <div class="dropdown-menu dropdown-menu-end shadow border border-dark rounded-0 p-0" style="min-width: 290px;">
                        <div class="px-3 py-2 fw-bold text-white" style="background-color: #12284c;">NOTIFICATIONS</div>
                        @if ($pendingReceiptCount > 0)<div class="px-3 py-2 small border-bottom"><i class="bi bi-hourglass-split text-warning me-2"></i>{{ $pendingReceiptCount }} receipt(s) are processing.</div>@endif
                        @if ($availableRewards->count() > 0)<div class="px-3 py-2 small border-bottom"><i class="bi bi-gift-fill text-success me-2"></i>{{ $availableRewards->count() }} reward(s) are ready to claim.</div>@endif
                        @if ($pendingReceiptCount === 0 && $availableRewards->isEmpty())<div class="px-3 py-3 small text-muted">You are all caught up.</div>@endif
                        <button type="button" class="dropdown-item small fw-bold text-center py-2" onclick="document.getElementById('tracker-tab').click()">VIEW REWARD TRACKER</button>
                    </div>
                </div>
                <form action="{{ route('logout') }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-light rounded-5 px-3 fw-bold">LOGOUT</button>
                </form>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container mb-5">

        @if (session('success'))
            <div class="alert alert-success py-2 small fw-bold mb-4 border-dark">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger py-2 small mb-4 border-dark">
                <ul class="mb-0 list-unstyled fw-bold">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Nav Tabs -->
        <ul class="nav nav-tabs mb-4" id="customerTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="order-tab" data-bs-toggle="tab" data-bs-target="#order" type="button" role="tab">SUBMIT NEW ORDER</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tracker-tab" data-bs-toggle="tab" data-bs-target="#tracker" type="button" role="tab">MY REWARD TRACKER</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="promos-tab" data-bs-toggle="tab" data-bs-target="#promos" type="button" role="tab">PROMOS &amp; CLAIMS</button>
            </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content" id="customerTabsContent">

            <!-- TAB 1: SUBMIT NEW ORDER (Matches image_40a057.png) -->
            <div class="tab-pane fade show active" id="order" role="tabpanel">
                <div class="row justify-content-center">
                    <div class="col-md-8 col-lg-6">
                        <div class="card laicom-card">
                            <div class="card-header bg-white border-bottom border-dark text-center py-3">
                                <h6 class="mb-0 fw-bold">SELECT BOUGHT PRODUCTS <span class="small fw-normal text-muted ms-2">Build your purchase list before submitting.</span></h6>
                            </div>
                            <div class="card-body p-4">

                                <!-- Product Selection Array -->
                                <div class="d-flex align-items-center mb-3">
                                    <select id="product_combobox" class="form-select border-dark border-2 rounded-0 me-2 fw-bold small">
                                        <option selected disabled>Choose a product...</option>
                                        @foreach ($products as $product)
                                            <option value="{{ $product->name }}" data-image="{{ $product->image_path ? asset('storage/' . $product->image_path) : '' }}">{{ $product->name }}</option>
                                        @endforeach
                                    </select>
                                    <input type="number" id="product_quantity" class="form-control border-dark border-2 rounded-0 me-2 fw-bold text-center" min="1" value="1" style="width: 70px;">
                                    <button type="button" id="add_item_btn" class="btn laicom-btn-primary px-3 py-1" aria-label="Add selected product"><i class="bi bi-plus-lg"></i></button>
                                </div>

                                <!-- Dynamic Visual Listbox -->
                                <div class="border border-dark border-2 bg-white mb-4 position-relative" style="height: 280px; overflow-y: auto;">
                                    <ul id="item_listbox" class="list-unstyled mb-0">
                                        <li class="p-4 text-muted text-center fw-bold" id="empty_msg">NO PRODUCTS SELECTED YET.</li>
                                    </ul>
                                </div>

                                <!-- Action Buttons -->
                                <div class="d-flex justify-content-between align-items-center mt-2">
                                    <button type="button" class="btn laicom-btn-primary px-4 small" onclick="document.getElementById('promos-tab').click()">PROMOS &amp; REWARDS</button>
                                    <button type="button" id="process_receipt_btn" class="btn laicom-btn-accent px-4 small" data-bs-toggle="modal" data-bs-target="#processReceiptModal">PROCESS RECEIPT</button>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 2: MY REWARD TRACKER -->
            <div class="tab-pane fade" id="tracker" role="tabpanel">
                <div class="card card-custom">
                    <div class="card-header card-header-custom">SUBMITTED ORDERS</div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 align-middle text-center">
                                <thead class="table-light border-bottom border-dark">
                                    <tr>
                                        <th class="py-3">ORDER NO.</th>
                                        <th class="py-3">DATE SUBMITTED</th>
                                        <th class="py-3">STATUS</th>
                                        <th class="py-3">DETAILS</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($receipts as $receipt)
                                    <tr>
                                        <td class="fw-bold">{{ $receipt->salesman_order_number }}</td>
                                        <td class="small">{{ \Carbon\Carbon::parse($receipt->submitted_at)->format('M d, Y') }}</td>
                                        <td>
                                            @if ($receipt->status === 'pending')
                                                <span class="badge bg-warning text-dark border border-dark w-75 py-2">PROCESSING</span>
                                            @elseif ($receipt->status === 'approved')
                                                <span class="badge bg-success border border-dark w-75 py-2">APPROVED</span>
                                            @else
                                                <span class="badge bg-danger border border-dark w-75 py-2">REJECTED</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="d-flex justify-content-center gap-1">
                                                <button type="button" class="btn btn-sm btn-outline-dark fw-bold" data-bs-toggle="modal" data-bs-target="#viewOrderModal{{ $receipt->id }}">VIEW PRODUCTS</button>
                                                @if ($receipt->earnedRewards->isNotEmpty())
                                                    <button type="button" class="btn btn-sm btn-outline-dark fw-bold" data-bs-toggle="modal" data-bs-target="#viewRewardsModal{{ $receipt->id }}">REWARDS</button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="4" class="py-4 text-muted fw-bold">NO ORDERS SUBMITTED YET</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 3: PROMOS & CLAIMS -->
            <div class="tab-pane fade" id="promos" role="tabpanel">
                <div class="row">
                    <!-- Active Promotions Left Side -->
                    <div class="col-md-6 mb-4">
                        <div class="card card-custom h-100">
                            <div class="card-header card-header-custom bg-success border-success">ACTIVE PROMOTIONS</div>
                            <div class="card-body">
                                <p class="small text-muted fw-bold mb-4">Purchase the required items to automatically earn these premium rewards!</p>
                                <ul class="list-group list-group-flush border-top border-dark">
                                    @forelse ($promotions as $promo)
                                        <li class="list-group-item py-3">
                                            <h6 class="fw-bold text-dark mb-1">{{ $promo->title }}</h6>
                                            <div class="d-flex align-items-center gap-2 small fw-bold text-secondary">
                                                <div class="border border-dark bg-light" style="width: 48px; height: 48px; background-size: cover; background-position: center; @php($buyImage = $products->firstWhere('name', $promo->buy_product_name)?->image_path) @if ($buyImage) background-image: url('{{ asset('storage/' . $buyImage) }}'); @endif"></div>
                                                <div><span class="d-block">BUY {{ $promo->required_quantity }} × {{ $promo->buy_product_name }}</span><i class="bi bi-arrow-right fs-5"></i></div>
                                                <div class="border border-dark bg-light" style="width: 48px; height: 48px; background-size: cover; background-position: center; @if ($promo->premiumProduct?->image_path) background-image: url('{{ asset('storage/' . $promo->premiumProduct->image_path) }}'); @endif"></div>
                                                <div><span class="d-block">GET {{ $promo->reward_quantity }} × {{ $promo->premiumProduct->name ?? 'Premium Reward' }}</span><span class="text-success">{{ $promo->premiumProduct?->stock ?? 0 }} left</span></div>
                                            </div>
                                        </li>
                                    @empty
                                        <li class="list-group-item text-muted text-center py-4 fw-bold">No active promotions at this time.</li>
                                    @endforelse
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Available Claims Right Side -->
                    <div class="col-md-6 mb-4">
                        <div class="card card-custom h-100">
                            <div class="card-header card-header-custom">MY CLAIMABLE REWARDS</div>
                            <div class="card-body">
                                <p class="small text-muted fw-bold mb-4">These rewards have been approved and are ready for you to claim physically.</p>

                                @if ($availableRewards->count() > 0)
                                    <div class="table-responsive border border-dark">
                                        <table class="table mb-0 text-center align-middle small">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>PREMIUM ITEM</th>
                                                    <th>QTY</th>
                                                    <th>ACTION</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($availableRewards as $reward)
                                                <tr>
                                                    <td class="fw-bold text-start">{{ $reward->premiumProduct->name ?? 'Unknown' }}</td>
                                                    <td class="fw-bold fs-6">{{ $reward->reward_quantity }}</td>
                                                    <td>
                                                        <form action="{{ route('customer.rewards.claim', $reward) }}" method="POST" onsubmit="return confirm('Mark this reward as claimed?');">
                                                            @csrf
                                                            <button type="submit" class="btn btn-sm btn-dark fw-bold px-3">CLAIM</button>
                                                        </form>
                                                    </td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <div class="border border-dark p-4 text-center bg-light">
                                        <i class="bi bi-box-seam text-muted" style="font-size: 2rem;"></i>
                                        <p class="mt-2 mb-0 fw-bold text-muted">YOU HAVE NO UNCLAIMED REWARDS</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div> <!-- End Tab Content -->
    </div> <!-- End Container -->

    <!-- ============================================= -->
    <!-- PROCESS RECEIPT SUBMISSION MODAL (Matches image_40a0bb.png) -->
    <!-- ============================================= -->
    <div class="modal fade" id="processReceiptModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <!-- Form wraps the entire modal content -->
            <form id="orderForm" action="{{ route('customer.submit_order') }}" method="POST" class="w-100">
                @csrf
                <div class="modal-content border-dark border-3 rounded-0 bg-light shadow">
                    <div class="modal-header border-bottom border-dark bg-white py-2">
                        <h5 class="modal-title fw-bold">CONFIRM RECEIPT SUBMISSION</h5>
                    </div>
                    <div class="modal-body bg-white p-4">

                        <p class="small fw-bold mb-3 text-uppercase">PLEASE ENTER THE ORDER NUMBER PROVIDED BY YOUR SALESMAN TO LOG YOUR PURCHASE AND CHECK ELIGIBLE REWARDS.</p>

                        <div class="mb-4">
                            <label class="form-label fw-bold small mb-1">SALESMAN ORDER NUMBER:</label>
                            <input type="text" name="salesman_order_number" class="form-control border-dark border-2 rounded-0 fw-bold" placeholder="E.G. ORD-2026-00481" required>
                        </div>

                        <label class="form-label fw-bold small mb-1">SUMMARY OF LOGGED ITEMS:</label>
                        <div class="border border-dark border-2 overflow-auto bg-white p-2" style="height: 120px;">
                            <ul id="modal_summary_list" class="list-unstyled small fw-bold mb-0">
                                <li class="text-muted" id="modal_empty_msg">No items recorded.</li>
                            </ul>
                        </div>

                        <!-- Hidden array inputs will be injected here by JS -->
                        <div id="hidden_inputs_container"></div>

                    </div>
                    <div class="modal-footer border-top-0 bg-white justify-content-end p-3">
                        <button type="button" class="btn btn-outline-dark fw-bold rounded-0 px-4 border-2" data-bs-dismiss="modal">CANCEL</button>
                        <button type="submit" class="btn btn-dark fw-bold rounded-0 px-4 border-2">SUBMIT</button>
                    </div>
                </div>
            </form>
        </div>
    </div>


    <!-- Dynamic View Order Modals -->
    @foreach ($receipts as $receipt)
    <div class="modal fade" id="viewOrderModal{{ $receipt->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content border-dark border-2 rounded-0">
                <div class="modal-header border-bottom border-dark text-white" style="background-color: #12284c;">
                    <h6 class="modal-title fw-bold">ORDER DETAILS: {{ $receipt->salesman_order_number }}</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-start">
                    <h6 class="fw-bold border-bottom border-dark pb-1 mb-2">LOGGED PURCHASES:</h6>
                    <ul class="list-unstyled small fw-bold mb-4">
                        @forelse ($receipt->items as $item)
                            <li>• {{ $item->quantity }}X {{ $item->product_name }}</li>
                        @empty
                            <li class="text-muted">No items recorded.</li>
                        @endforelse
                    </ul>

                    <h6 class="fw-bold border-bottom border-dark pb-1 mb-2">EARNED REWARDS:</h6>
                    @if ($receipt->status === 'pending')
                        <div class="alert alert-warning py-2 small fw-bold mb-0">Rewards will be calculated upon admin approval.</div>
                    @elseif ($receipt->status === 'rejected')
                        <div class="alert alert-danger py-2 small fw-bold mb-0">Order rejected. No rewards issued.</div>
                    @else
                        @if ($receipt->earnedRewards->count() > 0)
                            <ul class="list-unstyled fw-bold mb-0">
                                @foreach ($receipt->earnedRewards as $reward)
                                    <li class="text-success mb-2">
                                        <i class="bi bi-gift-fill me-2"></i> {{ $reward->reward_quantity }}X {{ $reward->premiumProduct->name ?? 'Premium Item' }}
                                        @if ($reward->claim_status === 'claimed')
                                            <span class="badge bg-secondary ms-2">CLAIMED</span>
                                        @else
                                            <span class="badge bg-success ms-2">AVAILABLE</span>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <p class="small text-muted fw-bold mb-0">No promotions applied to this order.</p>
                        @endif
                    @endif
                </div>
                <div class="modal-footer border-top-0 justify-content-end">
                    <button type="button" class="btn btn-outline-dark rounded-0 fw-bold" data-bs-dismiss="modal">CLOSE</button>
                </div>
            </div>
        </div>
    </div>
    @endforeach

    @foreach ($receipts as $receipt)
        <div class="modal fade" id="viewRewardsModal{{ $receipt->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog"><div class="modal-content border border-dark border-2 rounded-0">
                <div class="modal-header border-bottom border-dark text-white" style="background-color: #12284c;"><h6 class="modal-title fw-bold">CLAIMABLE REWARDS: {{ $receipt->salesman_order_number }}</h6><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">@forelse ($receipt->earnedRewards as $reward)<div class="d-flex justify-content-between align-items-center border-bottom py-2"><span class="fw-bold">{{ $reward->premiumProduct->name ?? 'Premium item' }}</span><span>{{ $reward->reward_quantity }} × <span class="badge {{ $reward->claim_status === 'claimed' ? 'bg-secondary' : 'bg-success' }}">{{ $reward->claim_status === 'claimed' ? 'CLAIMED' : 'AVAILABLE' }}</span></span></div>@empty<p class="small text-muted mb-0">No claimable rewards are available for this receipt.</p>@endforelse</div>
                <div class="modal-footer border-top-0"><button type="button" class="btn btn-outline-dark rounded-0" data-bs-dismiss="modal">CLOSE</button></div>
            </div></div>
        </div>
    @endforeach

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Synchronized Dual-Listbox Logic -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const addBtn = document.getElementById('add_item_btn');
            const combobox = document.getElementById('product_combobox');
            const quantity = document.getElementById('product_quantity');

            const mainListbox = document.getElementById('item_listbox');
            const modalSummaryList = document.getElementById('modal_summary_list');
            const hiddenContainer = document.getElementById('hidden_inputs_container');
            const processReceiptButton = document.getElementById('process_receipt_btn');

            let itemIndex = 0;

            if (addBtn) {
                addBtn.addEventListener('click', function() {
                    const selectedOption = combobox.options[combobox.selectedIndex];
                    const productName = selectedOption.value;
                    const productImage = selectedOption.getAttribute('data-image');
                    const qty = quantity.value;

                    if (productName === 'Choose a product...' || !productName || qty < 1) {
                        alert('Please select a valid product and quantity.');
                        return;
                    }

                    // Remove empty messages if they exist
                    const mainEmpty = document.getElementById('empty_msg');
                    if (mainEmpty) mainEmpty.remove();

                    const modalEmpty = document.getElementById('modal_empty_msg');
                    if (modalEmpty) modalEmpty.remove();

                    // Generate a unique ID for this list item block
                    const uniqueId = 'item-row-' + itemIndex;

                    // 1. Build the Main Dashboard UI Row (Matches image_40a057.png)
                    const mainLi = document.createElement('li');
                    mainLi.className = 'product-list-item d-flex align-items-center justify-content-between p-2';
                    mainLi.id = uniqueId + '-main';

                    let imageHtml = productImage
                        ? `<img src="${productImage}" alt="product" class="border border-dark me-3" style="width: 50px; height: 50px; object-fit: cover;">`
                        : `<div class="border border-dark me-3 bg-light" style="width: 50px; height: 50px;"></div>`;

                    mainLi.innerHTML = `
                        <div class="d-flex align-items-center flex-grow-1">
                            ${imageHtml}
                            <span class="fw-bold small text-uppercase">${productName}</span>
                        </div>
                        <div class="d-flex align-items-center">
                            <div class="text-center me-3">
                                <span class="d-block" style="font-size: 0.65rem; font-weight: 900;">QTY</span>
                                <div class="qty-box bg-white">${qty}</div>
                            </div>
                            <button type="button" class="btn btn-dark rounded-0 fw-bold fs-5 px-3 py-0 remove-btn" data-target="${uniqueId}"><i class="bi bi-dash-lg"></i></button>
                        </div>
                    `;
                    mainListbox.appendChild(mainLi);
                    processReceiptButton.disabled = false;

                    // 2. Build the Modal Summary Row (Matches image_40a0bb.png)
                    const modalLi = document.createElement('li');
                    modalLi.id = uniqueId + '-modal';
                    modalLi.innerHTML = `• ${productName} — QTY ${qty}`;
                    modalSummaryList.appendChild(modalLi);

                    // 3. Add hidden inputs for Laravel form submission inside the modal form
                    const hiddenWrapper = document.createElement('div');
                    hiddenWrapper.id = uniqueId + '-hidden';

                    hiddenWrapper.innerHTML = `
                        <input type="hidden" name="items[${itemIndex}][product_name]" value="${productName}">
                        <input type="hidden" name="items[${itemIndex}][quantity]" value="${qty}">
                    `;
                    hiddenContainer.appendChild(hiddenWrapper);

                    // 4. Handle Deletion globally
                    mainLi.querySelector('.remove-btn').addEventListener('click', function() {
                        const targetId = this.getAttribute('data-target');

                        document.getElementById(targetId + '-main').remove();
                        document.getElementById(targetId + '-modal').remove();
                        document.getElementById(targetId + '-hidden').remove();

                        // Restore empty states if all items are removed
                        if (mainListbox.children.length === 0) {
                            mainListbox.innerHTML = '<li class="p-4 text-muted text-center fw-bold" id="empty_msg">NO PRODUCTS SELECTED YET.</li>';
                            modalSummaryList.innerHTML = '<li class="text-muted" id="modal_empty_msg">No items recorded.</li>';
                            processReceiptButton.disabled = true;
                        }
                    });

                    // Reset Inputs
                    itemIndex++;
                    combobox.selectedIndex = 0;
                    quantity.value = 1;
                });
            }

            if (processReceiptButton) {
                processReceiptButton.disabled = true;
            }
        });
    </script>
</body>
</html>
