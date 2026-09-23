<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LRTS - Customer Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { background-color: #f8f9fa; }
        .navbar-custom { background-color: #12284c; }
        .card-custom { border: 2px solid #12284c; border-radius: 8px; }
        .card-header-custom { background-color: #12284c; color: white; font-weight: bold; letter-spacing: 1px; }
    </style>
</head>
<body>

    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom mb-4 py-3">
        <div class="container">
            <a class="navbar-brand fw-bold" href="#">
                <i class="bi bi-award-fill me-2"></i>LRTS CUSTOMER
            </a>
            <div class="d-flex align-items-center text-white">
                <span class="me-4 fw-semibold small">WELCOME, {{ strtoupper(auth()->user()->name) }}</span>
                <form action="{{ route('logout') }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-light rounded-5 px-3 fw-bold">LOGOUT</button>
                </form>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container">
        <div class="row">
            <!-- Submit Order Form with Combo Box & Listbox -->
            <div class="col-md-4 mb-4">
                <div class="card card-custom h-100">
                    <div class="card-header card-header-custom">SUBMIT NEW ORDER</div>
                    <div class="card-body">
                        <p class="small text-muted mb-3">Enter your Salesman Order Number to log a new purchase for reward calculation.</p>

                        @if(session('success'))
                            <div class="alert alert-success py-2 small fw-bold mb-3">{{ session('success') }}</div>
                        @endif
                        @if($errors->any())
                            <div class="alert alert-danger py-2 small mb-3">
                                <ul class="mb-0 list-unstyled">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form id="orderForm" action="{{ route('customer.submit_order') }}" method="POST">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label fw-bold small">ORDER NUMBER:</label>
                                <input type="text" name="salesman_order_number" class="form-control border-dark" placeholder="e.g., LKM-12345" required value="{{ old('salesman_order_number') }}">
                            </div>

                            <div class="p-3 border border-dark rounded mb-3 bg-light">
                                <label class="form-label fw-bold small">SELECT PURCHASED PRODUCT:</label>
                                <select id="product_combobox" class="form-select border-dark mb-2">
                                    <option selected disabled>Choose a product...</option>
                                    @foreach($products as $product)
                                        <option value="{{ $product->name }}">{{ $product->name }}</option>
                                    @endforeach
                                </select>

                                <label class="form-label fw-bold small">QUANTITY:</label>
                                <div class="input-group mb-1">
                                    <input type="number" id="product_quantity" class="form-control border-dark" min="1" value="1">
                                    <button type="button" id="add_item_btn" class="btn btn-secondary border-dark fw-bold">ADD TO LIST</button>
                                </div>
                            </div>

                            <label class="form-label fw-bold small">YOUR ITEM LISTBOX:</label>
                            <div class="border border-dark bg-white mb-3" style="height: 140px; overflow-y: auto;">
                                <ul id="item_listbox" class="list-group list-group-flush small fw-bold">
                                    <li class="list-group-item text-muted text-center" id="empty_msg">No items added yet.</li>
                                </ul>
                            </div>

                            <div id="hidden_inputs_container"></div>

                            <button type="submit" class="btn btn-dark w-100 fw-bold">SUBMIT FOR REVIEW</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Order History & Status -->
            <div class="col-md-8 mb-4">
                <div class="card card-custom h-100">
                    <div class="card-header card-header-custom">MY REWARD TRACKER</div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 align-middle text-center">
                                <thead class="table-light border-bottom border-dark">
                                    <tr>
                                        <th class="py-3">ORDER NO.</th>
                                        <th class="py-3">DATE SUBMITTED</th>
                                        <th class="py-3">STATUS</th>
                                        <th class="py-3">REWARDS</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($receipts as $receipt)
                                    <tr>
                                        <td class="fw-bold">{{ $receipt->salesman_order_number }}</td>
                                        <td class="small">{{ \Carbon\Carbon::parse($receipt->submitted_at)->format('M d, Y') }}</td>
                                        <td>
                                            @if($receipt->status === 'pending')
                                                <span class="badge bg-warning text-dark border border-dark w-75 py-2">PROCESSING</span>
                                            @elseif($receipt->status === 'approved')
                                                <span class="badge bg-success border border-dark w-75 py-2">APPROVED</span>
                                            @else
                                                <span class="badge bg-danger border border-dark w-75 py-2">REJECTED</span>
                                            @endif
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-outline-dark fw-bold" data-bs-toggle="modal" data-bs-target="#viewOrderModal{{ $receipt->id }}">VIEW</button>
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
        </div>
    </div>

    <!-- Dynamic View Order Modals (one per receipt, placed OUTSIDE the table) -->
    @foreach($receipts as $receipt)
    <div class="modal fade" id="viewOrderModal{{ $receipt->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content border-dark border-2 rounded-0">
                <div class="modal-header border-bottom border-dark text-white" style="background-color: #12284c;">
                    <h6 class="modal-title fw-bold">ORDER DETAILS: {{ $receipt->salesman_order_number }}</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-start">

                    <!-- Submitted Items -->
                    <h6 class="fw-bold border-bottom border-dark pb-1 mb-2">LOGGED PURCHASES:</h6>
                    <ul class="list-unstyled small fw-bold mb-4">
                        @forelse($receipt->items as $item)
                            <li>• {{ $item->quantity }}X {{ $item->product_name }}</li>
                        @empty
                            <li class="text-muted">No items recorded.</li>
                        @endforelse
                    </ul>

                    <!-- Earned Rewards -->
                    <h6 class="fw-bold border-bottom border-dark pb-1 mb-2">EARNED REWARDS:</h6>
                    @if($receipt->status === 'pending')
                        <div class="alert alert-warning py-2 small fw-bold mb-0">
                            Rewards will be calculated once an admin approves this order.
                        </div>
                    @elseif($receipt->status === 'rejected')
                        <div class="alert alert-danger py-2 small fw-bold mb-0">
                            This order was rejected. No rewards were issued.
                        </div>
                    @else
                        @if($receipt->earnedRewards->count() > 0)
                            <ul class="list-unstyled fw-bold mb-0">
                                @foreach($receipt->earnedRewards as $reward)
                                    <li class="text-success mb-2">
                                        <i class="bi bi-gift-fill me-2"></i> {{ $reward->reward_quantity }}X {{ $reward->premiumProduct->name ?? 'Premium Item' }}

                                        @if($reward->claim_status === 'claimed')
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Order Form JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const addBtn = document.getElementById('add_item_btn');
            const combobox = document.getElementById('product_combobox');
            const quantity = document.getElementById('product_quantity');
            const listbox = document.getElementById('item_listbox');
            const hiddenContainer = document.getElementById('hidden_inputs_container');
            let itemIndex = 0;

            addBtn.addEventListener('click', function() {
                const productName = combobox.value;
                const qty = quantity.value;

                if (productName === 'Choose a product...' || !productName || qty < 1) {
                    alert('Please select a valid product and quantity.');
                    return;
                }

                const emptyMsg = document.getElementById('empty_msg');
                if (emptyMsg) emptyMsg.remove();

                const li = document.createElement('li');
                li.className = 'list-group-item d-flex justify-content-between align-items-center py-1';
                li.innerHTML = `${qty}X ${productName} <button type="button" class="btn btn-sm btn-danger py-0 px-2 remove-btn" style="font-size: 0.75rem;">&times;</button>`;
                listbox.appendChild(li);

                const wrapper = document.createElement('div');

                const hiddenName = document.createElement('input');
                hiddenName.type = 'hidden';
                hiddenName.name = `items[${itemIndex}][product_name]`;
                hiddenName.value = productName;

                const hiddenQty = document.createElement('input');
                hiddenQty.type = 'hidden';
                hiddenQty.name = `items[${itemIndex}][quantity]`;
                hiddenQty.value = qty;

                wrapper.appendChild(hiddenName);
                wrapper.appendChild(hiddenQty);
                hiddenContainer.appendChild(wrapper);

                li.querySelector('.remove-btn').addEventListener('click', function() {
                    li.remove();
                    wrapper.remove();
                    if (listbox.children.length === 0) {
                        listbox.innerHTML = '<li class="list-group-item text-muted text-center" id="empty_msg">No items added yet.</li>';
                    }
                });

                itemIndex++;
                combobox.selectedIndex = 0;
                quantity.value = 1;
            });
        });
    </script>
</body>
</html>