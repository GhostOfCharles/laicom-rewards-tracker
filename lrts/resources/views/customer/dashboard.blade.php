<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LRTS - Customer Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="{{ asset('css/laicom.css') }}">
</head>
<body class="laicom-page">

    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark laicom-navbar py-3 shadow-sm">
        <div class="container-fluid px-3 px-md-4">
            <button class="btn btn-sm lrts-menu-toggle me-3" id="menuToggle" type="button" aria-expanded="false" aria-controls="primaryNav" aria-label="Toggle navigation">
                <i class="bi bi-list fs-5"></i>
            </button>

            <a class="navbar-brand fw-bold d-flex align-items-center" href="#order">
                <img src="{{ asset('images/laicom-logo.png') }}" alt="Laicom" height="30" class="me-2"> LRTS CUSTOMER
            </a>

            <div class="d-flex align-items-center text-white ms-auto">
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

    <!-- Mobile backdrop -->
    <div class="lrts-backdrop" id="sidebarBackdrop"></div>

    <!-- Shell: Sidebar + Content -->
    <div class="lrts-shell">

        <!-- Sidebar / Vertical Tabs -->
        <aside class="lrts-sidebar" id="primaryNav">
            <div class="lrts-sidebar-heading">CUSTOMER MENU</div>

            <ul class="nav nav-pills flex-column" id="customerTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="order-tab" data-bs-toggle="pill" data-bs-target="#order" type="button" role="tab" aria-selected="true">
                        <i class="bi bi-cart-plus-fill"></i>
                        <span>Submit<br>New Order</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="tracker-tab" data-bs-toggle="pill" data-bs-target="#tracker" type="button" role="tab" aria-selected="false">
                        <i class="bi bi-gift-fill"></i>
                        <span>Reward<br>Tracker</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="promos-tab" data-bs-toggle="pill" data-bs-target="#promos" type="button" role="tab" aria-selected="false">
                        <i class="bi bi-tags-fill"></i>
                        <span>Promos<br>&amp; Claim</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="help-tab" data-bs-toggle="pill" data-bs-target="#help" type="button" role="tab" aria-selected="false">
                        <i class="bi bi-question-circle-fill"></i>
                        <span>Help &amp;<br>Support</span>
                    </button>
                </li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="lrts-content">

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

            <!-- Tab Content -->
            <div class="tab-content" id="customerTabsContent">

                <!-- TAB 1: SUBMIT NEW ORDER -->
                <div class="tab-pane fade show active" id="order" role="tabpanel">
                    <div class="card laicom-card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span>SELECT BOUGHT PRODUCTS</span>
                            <span class="small fw-normal d-none d-md-inline" style="opacity:.7;">Build your purchase list before submitting.</span>
                        </div>
                        <div class="card-body p-4">

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

                            <div class="border border-dark border-2 bg-white mb-4 position-relative" style="height: 320px; overflow-y: auto;">
                                <ul id="item_listbox" class="list-unstyled mb-0">
                                    <li class="p-4 text-muted text-center fw-bold" id="empty_msg">NO PRODUCTS SELECTED YET.</li>
                                </ul>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mt-2">
                                <button type="button" class="btn laicom-btn-primary px-4 small" onclick="document.getElementById('promos-tab').click()">PROMOS &amp; REWARDS</button>
                                <button type="button" id="process_receipt_btn" class="btn laicom-btn-accent px-4 small" data-bs-toggle="modal" data-bs-target="#processReceiptModal">PROCESS RECEIPT</button>
                            </div>

                        </div>
                    </div>
                </div>

                <!-- TAB 2: MY REWARD TRACKER -->
                <div class="tab-pane fade" id="tracker" role="tabpanel">
                    <div class="card laicom-card">
                        <div class="card-header">SUBMITTED ORDERS</div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0 align-middle text-center">
                                    <thead class="border-bottom border-dark">
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

                    <!-- Section Toggle -->
                    <div class="row g-0 lrts-section-toggle">
                        <div class="col-6">
                            <button type="button" class="lrts-section-btn is-active" data-promos-section="promotions">
                                ACTIVE PROMOTIONS
                            </button>
                        </div>
                        <div class="col-6">
                            <button type="button" class="lrts-section-btn" data-promos-section="claims">
                                CLAIMABLE REWARDS
                            </button>
                        </div>
                    </div>

                    <!-- Section: Active Promotions -->
                    <div class="lrts-section-pane is-active" data-promos-pane="promotions">
                        <div class="card laicom-card">
                            <div class="card-header">ACTIVE PROMOTIONS</div>
                            <div class="card-body">
                                <p class="small text-muted fw-bold mb-4">Purchase the required items to automatically earn these premium rewards!</p>

                                @if ($promotions->count() > 0)
                                    <div class="row g-4">
                                        @foreach ($promotions as $promo)
                                            @php
                                                $buyProduct = $products->firstWhere('name', $promo->buy_product_name);
                                                $buyImage = $buyProduct?->image_path;
                                                $rewardImage = $promo->premiumProduct?->image_path;
                                            @endphp
                                            <div class="col-md-6">
                                                <div class="promo-card">

                                                    <span class="promo-badge">PROMO {{ $loop->iteration }}</span>

                                                    <div class="promo-headline">
                                                        BUY {{ $promo->required_quantity }} {{ $promo->buy_product_name }}
                                                        <span class="promo-get">GET {{ $promo->reward_quantity }} {{ $promo->premiumProduct->name ?? 'Premium Reward' }}</span>
                                                    </div>

                                                    <div class="promo-products">

                                                        <div class="promo-product">
                                                            @if ($buyImage)
                                                                <img src="{{ asset('storage/' . $buyImage) }}" alt="{{ $promo->buy_product_name }}" class="promo-product-img">
                                                            @else
                                                                <img src="{{ asset('images/laicom-logo.png') }}" alt="{{ $promo->buy_product_name }}" class="promo-product-img is-empty">
                                                            @endif
                                                            <span class="promo-product-tag">BUY {{ $promo->required_quantity }}</span>
                                                            <p class="promo-product-name">{{ $promo->buy_product_name }}</p>
                                                        </div>

                                                        <div class="promo-arrow">
                                                            <svg viewBox="0 0 36 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                                                <path d="M0 9h22V2.5L36 12l-14 9.5V15H0z"/>
                                                            </svg>
                                                        </div>

                                                        <div class="promo-product">
                                                            @if ($rewardImage)
                                                                <img src="{{ asset('storage/' . $rewardImage) }}" alt="{{ $promo->premiumProduct->name }}" class="promo-product-img">
                                                            @else
                                                                <img src="{{ asset('images/laicom-logo.png') }}" alt="{{ $promo->premiumProduct->name ?? 'Reward' }}" class="promo-product-img is-empty">
                                                            @endif
                                                            <span class="promo-product-tag">GET {{ $promo->reward_quantity }}</span>
                                                            <p class="promo-product-name">{{ $promo->premiumProduct->name ?? 'Premium Reward' }}</p>
                                                            <span class="promo-stock">
                                                                <i class="bi bi-box-seam"></i>
                                                                {{ $promo->premiumProduct?->stock ?? 0 }} left
                                                            </span>
                                                        </div>

                                                    </div>

                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="border border-dark p-4 text-center bg-light">
                                        <i class="bi bi-tags text-muted" style="font-size: 2rem;"></i>
                                        <p class="mt-2 mb-0 fw-bold text-muted">NO ACTIVE PROMOTIONS AT THIS TIME</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Section: Claimable Rewards -->
                    <div class="lrts-section-pane" data-promos-pane="claims">
                        <div class="card laicom-card">
                            <div class="card-header">MY CLAIMABLE REWARDS</div>
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

                <!-- TAB 4: HELP & SUPPORT -->
                <div class="tab-pane fade" id="help" role="tabpanel">

                    <!-- FAQ Card -->
                    <div class="card laicom-card mb-4">
                        <div class="card-header">FREQUENTLY ASKED QUESTIONS</div>
                        <div class="card-body p-0">
                            <div class="accordion accordion-flush" id="faqAccordion">
                                @php
                                    $faqs = [
                                        ['q' => 'How do I submit a new order?', 'a' => 'Go to Submit New Order, select products from the dropdown, enter quantities, then click Process Receipt and enter the order number from your salesman.'],
                                        ['q' => 'Why is my order still "Processing"?', 'a' => 'Orders are reviewed by admin before rewards are issued. This usually takes less than 24 hours.'],
                                        ['q' => 'How do I claim a reward?', 'a' => 'Go to Promos & Claim → Claimable Rewards, click CLAIM, and present the confirmation at the store to physically receive your item.'],
                                        ['q' => 'What if my reward is missing?', 'a' => 'Submit an inquiry here in the Help tab. Include your order number and the reward you expected so we can trace it.'],
                                        ['q' => 'How do I contact support?', 'a' => 'Click the chat bubble in the bottom-right corner, or use the Open Support Chat button below.'],
                                    ];
                                @endphp

                                @foreach ($faqs as $i => $faq)
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed" type="button"
                                                data-bs-toggle="collapse"
                                                data-bs-target="#faq{{ $i }}">
                                            <i class="bi bi-chevron-right me-2 faq-chevron"></i>
                                            {{ $faq['q'] }}
                                        </button>
                                    </h2>
                                    <div id="faq{{ $i }}" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                        <div class="accordion-body small text-muted">
                                            {{ $faq['a'] }}
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <!-- Need More Help CTA -->
                    <div class="card laicom-card">
                        <div class="card-header">NEED MORE HELP?</div>
                        <div class="card-body text-center py-4">
                            <p class="fw-bold mb-3">Can't find what you're looking for? Our support team can help.</p>
                            <button type="button" class="btn laicom-btn-accent px-4 py-2 fw-bold"
                                    onclick="lrtsOpenDrawer()">
                                OPEN SUPPORT CHAT
                            </button>
                        </div>
                    </div>

                </div>

            </div> <!-- End Tab Content -->
        </main>
    </div> <!-- End Shell -->

    <!-- ============================================================
         SUPPORT DRAWER + CHAT BUBBLE
         ============================================================ -->
    <div class="lrts-drawer-backdrop" id="lrtsDrawerBackdrop" onclick="lrtsCloseDrawer()"></div>

    <aside class="lrts-drawer" id="lrtsDrawer" aria-hidden="true">
        <header class="lrts-drawer-header">
            <span>SUPPORT</span>
            <button type="button" class="lrts-drawer-close" onclick="lrtsCloseDrawer()" aria-label="Close support drawer">
                <i class="bi bi-x-lg"></i>
            </button>
        </header>

        <div class="lrts-drawer-body">

            <button type="button" class="lrts-drawer-create" data-bs-toggle="modal" data-bs-target="#createTicketModal">
                <i class="bi bi-plus-lg"></i> CREATE NEW TICKET
            </button>

            <div class="lrts-drawer-section-title">MY TICKETS</div>

            <div class="lrts-ticket-list">
                @forelse ($tickets as $ticket)
                    @php
                        $badgeClass = match($ticket->status) {
                            'open' => 'lrts-badge-open',
                            'pending' => 'lrts-badge-pending',
                            'in_progress' => 'lrts-badge-progress',
                            'resolved' => 'lrts-badge-resolved',
                            'closed' => 'lrts-badge-closed',
                            default => 'lrts-badge-closed',
                        };
                    @endphp
                    <button type="button"
                            class="lrts-ticket-row"
                            data-bs-toggle="modal"
                            data-bs-target="#viewTicketModal{{ $ticket->id }}">
                        <div class="lrts-ticket-row-main">
                            <div class="lrts-ticket-row-subject">
                                #{{ str_pad($ticket->id, 4, '0', STR_PAD_LEFT) }} · {{ $ticket->subject }}
                            </div>
                            <div class="lrts-ticket-row-meta">
                                <span class="lrts-badge {{ $badgeClass }}">{{ $ticket->statusLabel() }}</span>
                                <span>· {{ $ticket->replies_count }} {{ Str::plural('reply', $ticket->replies_count) }}</span>
                                <span>· {{ $ticket->created_at->diffForHumans() }}</span>
                            </div>
                        </div>
                        <i class="bi bi-chevron-right"></i>
                    </button>
                @empty
                    <div class="lrts-ticket-empty">
                        <i class="bi bi-inbox"></i>
                        <div>NO TICKETS YET</div>
                        <small>Start one with the button above.</small>
                    </div>
                @endforelse
            </div>

        </div>
    </aside>

    <!-- Pulsing bubble -->
    <button type="button" class="lrts-chat-bubble" id="lrtsChatBubble" onclick="lrtsOpenDrawer()" aria-label="Open support">
        <span class="lrts-chat-bubble-label">NEED HELP?</span>
        <span class="lrts-chat-bubble-ring"></span>
        <span class="lrts-chat-bubble-ring lrts-chat-bubble-ring-2"></span>
        <span class="lrts-chat-bubble-icon"><i class="bi bi-chat-dots-fill"></i></span>
    </button>

    <!-- ============================================================
         CREATE TICKET MODAL (UPGRADED)
         ============================================================ -->
    <div class="modal fade" id="createTicketModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form action="{{ route('customer.tickets.store') }}" method="POST" class="w-100" enctype="multipart/form-data" id="createTicketForm">
                @csrf
                <div class="modal-content lrts-create-ticket">

                    <!-- HEADER -->
                    <div class="lrts-create-header">
                        <div class="lrts-create-header-row">
                            <h6 class="lrts-create-title">
                                <i class="bi bi-life-preserver"></i> CREATE NEW TICKET
                            </h6>
                            <button type="button" class="lrts-create-close" data-bs-dismiss="modal" aria-label="Close">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </div>
                        <div class="lrts-create-subtitle">
                            We typically reply within 24 hours.
                        </div>
                    </div>
                    <div class="lrts-create-accent"></div>

                    <!-- BODY -->
                    <div class="lrts-create-body">

                        <!-- Subject -->
                        <div class="lrts-create-field">
                            <label class="lrts-create-label">
                                <i class="bi bi-tag"></i> SUBJECT
                            </label>
                            <input type="text"
                                   name="subject"
                                   id="lrtsCreateSubject"
                                   class="lrts-create-input"
                                   placeholder="e.g. Missing reward for order ORD-2026-00481"
                                   minlength="3"
                                   maxlength="200"
                                   required>
                            <div class="lrts-create-counter" data-counter-for="lrtsCreateSubject" data-max="200">0 / 200</div>
                        </div>

                        <!-- Category chips -->
                        <div class="lrts-create-field">
                            <label class="lrts-create-label">
                                <i class="bi bi-list-check"></i> CATEGORY
                            </label>
                            <input type="hidden" name="category" id="lrtsCreateCategory" value="" required>
                            <div class="lrts-create-chips" id="lrtsCreateChips">
                                <button type="button" class="lrts-create-chip" data-value="orders"><i class="bi bi-bag"></i> Orders</button>
                                <button type="button" class="lrts-create-chip" data-value="rewards"><i class="bi bi-gift"></i> Rewards</button>
                                <button type="button" class="lrts-create-chip" data-value="promos"><i class="bi bi-tags"></i> Promos</button>
                                <button type="button" class="lrts-create-chip" data-value="account"><i class="bi bi-person"></i> Account</button>
                                <button type="button" class="lrts-create-chip" data-value="other"><i class="bi bi-question-circle"></i> Other</button>
                            </div>
                            <div class="lrts-create-error" id="lrtsCategoryError" style="display:none;">Please select a category.</div>
                        </div>

                        <!-- Related order -->
                        <div class="lrts-create-field">
                            <label class="lrts-create-label">
                                <i class="bi bi-receipt"></i> RELATED ORDER <span class="lrts-create-optional">(OPTIONAL)</span>
                            </label>
                            <select name="related_receipt_id" id="lrtsCreateReceipt" class="lrts-create-input">
                                <option value="">— None —</option>
                                @foreach ($receipts as $receipt)
                                    <option value="{{ $receipt->id }}"
                                            data-number="{{ $receipt->salesman_order_number }}"
                                            data-date="{{ \Carbon\Carbon::parse($receipt->submitted_at)->format('M d, Y') }}"
                                            data-status="{{ $receipt->status }}">
                                        #{{ $receipt->salesman_order_number }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="lrts-create-receipt-preview" id="lrtsReceiptPreview" style="display:none;">
                                <div class="lrts-create-receipt-preview-num" id="lrtsReceiptNum"></div>
                                <div class="lrts-create-receipt-preview-meta">
                                    <span id="lrtsReceiptDate"></span>
                                    <span class="lrts-create-receipt-status" id="lrtsReceiptStatus"></span>
                                </div>
                            </div>
                            <div class="lrts-create-hint" id="lrtsReceiptHint">
                                Link this ticket to an existing order so we can trace it faster.
                            </div>
                        </div>

                        <!-- Message -->
                        <div class="lrts-create-field">
                            <label class="lrts-create-label">
                                <i class="bi bi-chat-left-text"></i> MESSAGE
                            </label>
                            <textarea name="body"
                                      id="lrtsCreateBody"
                                      class="lrts-create-input lrts-create-textarea"
                                      rows="5"
                                      placeholder="Describe your issue in detail..."
                                      minlength="10"
                                      maxlength="5000"
                                      required></textarea>
                            <div class="lrts-create-counter" data-counter-for="lrtsCreateBody" data-max="5000">0 / 5000</div>
                        </div>

                        <!-- Attachment -->
                        <div class="lrts-create-field">
                            <label class="lrts-create-label">
                                <i class="bi bi-paperclip"></i> ATTACHMENT <span class="lrts-create-optional">(OPTIONAL)</span>
                            </label>
                            <label for="lrtsCreateAttachment" class="lrts-create-dropzone" id="lrtsDropzone">
                                <i class="bi bi-cloud-arrow-up lrts-create-dropzone-icon"></i>
                                <div class="lrts-create-dropzone-text">
                                    Drop a screenshot here, or <span>click to browse</span>
                                </div>
                                <div class="lrts-create-dropzone-hint">
                                    Allowed: JPG, PNG, GIF, WEBP, PDF (Max 5MB)
                                </div>
                                <input type="file"
                                       name="attachment"
                                       id="lrtsCreateAttachment"
                                       accept="image/jpeg,image/png,image/gif,image/webp,application/pdf"
                                       data-max-size="5242880"
                                       class="lrts-create-file-input">
                            </label>
                            <div class="lrts-create-file-info" id="lrtsFileInfo" style="display:none;">
                                <i class="bi bi-file-earmark-check"></i>
                                <span id="lrtsFileName"></span>
                                <button type="button" class="lrts-create-file-remove" id="lrtsFileRemove" aria-label="Remove file">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </div>
                            <div class="lrts-create-error" id="lrtsFileError" style="display:none;"></div>
                        </div>

                    </div>

                    <!-- FOOTER -->
                    <div class="lrts-create-footer">
                        <div class="lrts-create-privacy">
                            <i class="bi bi-shield-lock"></i> Your inquiry is private and only visible to Laicom support.
                        </div>
                        <div class="lrts-create-actions">
                            <button type="button" class="lrts-create-btn-cancel" data-bs-dismiss="modal">CANCEL</button>
                            <button type="submit" class="lrts-create-btn-submit">
                                <i class="bi bi-send-fill"></i> SUBMIT TICKET
                            </button>
                        </div>
                    </div>

                </div>
            </form>
        </div>
    </div>

    <!-- ============================================================
         VIEW TICKET MODALS (one per ticket)
         ============================================================ -->
    @foreach ($tickets as $ticket)
    @php
        $isResolved = in_array($ticket->status, ['resolved', 'closed'], true);
        $badgeClass = match($ticket->status) {
            'open'        => 'lrts-badge-open',
            'pending'     => 'lrts-badge-pending',
            'in_progress' => 'lrts-badge-progress',
            'resolved'    => 'lrts-badge-resolved',
            'closed'      => 'lrts-badge-closed',
            default       => 'lrts-badge-closed',
        };
    @endphp

    <div class="modal fade" id="viewTicketModal{{ $ticket->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content lrts-ticket-modal">

                <!-- HEADER -->
                <div class="lrts-ticket-modal-header">
                    <div class="lrts-ticket-modal-header-left">
                        <span class="lrts-ticket-modal-num">#{{ str_pad($ticket->id, 4, '0', STR_PAD_LEFT) }}</span>
                        <span class="lrts-ticket-modal-sep">·</span>
                        <span class="lrts-ticket-modal-subject">{{ strtoupper($ticket->subject) }}</span>
                    </div>
                    <button type="button" class="lrts-ticket-modal-close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>

                <!-- METADATA STRIP -->
                <div class="lrts-ticket-modal-meta">
                    <div class="lrts-ticket-modal-meta-item">
                        <i class="bi bi-tag-fill lrts-ticket-modal-meta-icon"></i>
                        <div>
                            <div class="lrts-ticket-modal-meta-label">CATEGORY</div>
                            <div class="lrts-ticket-modal-meta-value">{{ strtoupper($ticket->category ?? 'GENERAL') }}</div>
                        </div>
                    </div>
                    <div class="lrts-ticket-modal-meta-item">
                        <i class="bi bi-circle-fill lrts-ticket-modal-meta-icon"></i>
                        <div>
                            <div class="lrts-ticket-modal-meta-label">STATUS</div>
                            <div class="lrts-ticket-modal-meta-value">
                                <span class="lrts-badge {{ $badgeClass }}">{{ $ticket->statusLabel() }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="lrts-ticket-modal-meta-item">
                        <i class="bi bi-clock-fill lrts-ticket-modal-meta-icon"></i>
                        <div>
                            <div class="lrts-ticket-modal-meta-label">SUBMITTED</div>
                            <div class="lrts-ticket-modal-meta-value">{{ $ticket->created_at->format('M d, Y · g:i A') }}</div>
                        </div>
                    </div>
                </div>

                <!-- THREAD -->
                <div class="lrts-ticket-modal-thread">
                    @forelse ($ticket->replies as $reply)
                        @php $isMine = $reply->user_id === auth()->id(); @endphp

                        <div class="lrts-ticket-msg {{ $isMine ? 'is-me' : 'is-support' }}">
                            <div class="lrts-ticket-msg-head">
                                <span class="lrts-ticket-msg-role">{{ $isMine ? 'YOU' : 'SUPPORT' }}</span>
                                <span class="lrts-ticket-msg-dot">·</span>
                                <span class="lrts-ticket-msg-time">{{ $reply->created_at->format('M d, g:i A') }}</span>
                                <span class="lrts-ticket-msg-dot">·</span>
                                <span class="lrts-ticket-msg-time">{{ $reply->created_at->diffForHumans() }}</span>
                            </div>
                            <div class="lrts-ticket-msg-body">{{ $reply->body }}</div>

                            @if ($reply->attachment_path)
                                <div class="lrts-ticket-msg-attachment">
                                    @if (preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $reply->attachment_path))
                                        <img src="{{ asset('storage/' . $reply->attachment_path) }}"
                                             alt="attachment"
                                             class="lrts-ticket-attachment-img lrts-zoomable-img"
                                             data-src="{{ asset('storage/' . $reply->attachment_path) }}"
                                             style="cursor: zoom-in;">
                                    @else
                                        <a href="{{ asset('storage/' . $reply->attachment_path) }}" target="_blank" class="lrts-ticket-attachment-file">
                                            <i class="bi bi-file-earmark-pdf-fill"></i> View attachment
                                        </a>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="lrts-ticket-modal-empty">No messages yet.</div>
                    @endforelse
                </div>

                <!-- COMPOSER / LOCKED -->
                @if (!$isResolved)
                    <div class="lrts-ticket-modal-composer">
                        <div class="lrts-ticket-modal-composer-label">YOUR REPLY</div>

                        <form action="{{ route('customer.tickets.reply', $ticket) }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <textarea name="body"
                                      rows="4"
                                      class="lrts-ticket-modal-textarea"
                                      placeholder="Type your reply..."
                                      minlength="10"
                                      required></textarea>

                            <div class="lrts-ticket-modal-composer-foot">
                                <div class="lrts-ticket-modal-attach">
                                    <label for="replyAttachment{{ $ticket->id }}" class="lrts-ticket-modal-attach-btn">
                                        <i class="bi bi-paperclip"></i> ATTACH FILE
                                    </label>
                                    <input type="file"
                                           name="attachment"
                                           id="replyAttachment{{ $ticket->id }}"
                                           accept="image/jpeg,image/png,image/gif,image/webp,application/pdf"
                                           class="lrts-ticket-modal-attach-input"
                                           data-max-size="5242880">
                                    <span class="lrts-ticket-modal-attach-name" data-for="replyAttachment{{ $ticket->id }}">NO FILE CHOSEN</span>
                                </div>

                                <button type="submit" class="lrts-ticket-modal-send">
                                    SEND REPLY
                                </button>
                            </div>

                            <div class="lrts-ticket-modal-hint">
                                Allowed: JPG, JPEG, PNG, GIF, WEBP, PDF (Max 5MB)
                            </div>
                        </form>
                    </div>
                @else
                    <div class="lrts-ticket-modal-locked">
                        <div class="lrts-ticket-modal-locked-icon">
                            <i class="bi bi-lock-fill"></i>
                        </div>
                        <div class="lrts-ticket-modal-locked-content">
                            <div class="lrts-ticket-modal-locked-title">
                                THIS TICKET IS {{ $ticket->statusLabel() }} AND NO LONGER ACCEPTING REPLIES.
                            </div>
                            <div class="lrts-ticket-modal-locked-text">
                                If you need further help, please open a new ticket.
                            </div>
                        </div>
                    </div>
                @endif

            </div>
        </div>
    </div>
    @endforeach

    <!-- ============================================================
         PROCESS RECEIPT SUBMISSION MODAL
         ============================================================ -->
    <div class="modal fade" id="processReceiptModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
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

    <!-- ============================================================
         IMAGE LIGHTBOX MODAL
         ============================================================ -->
    <div id="lrtsImageLightbox" class="lrts-lightbox" onclick="closeLightbox(event)">
        <button type="button" class="lrts-lightbox-close" onclick="closeLightbox(event)" aria-label="Close image">
            <i class="bi bi-x-lg"></i>
        </button>
        <img id="lrtsLightboxImg" src="" alt="Zoomed attachment" onclick="event.stopPropagation()">
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Sidebar toggle (mobile) -->
    <script>
        (function() {
            const menuToggle = document.getElementById("menuToggle");
            const primaryNav = document.getElementById("primaryNav");
            const backdrop = document.getElementById("sidebarBackdrop");

            if (!menuToggle || !primaryNav) return;

            menuToggle.addEventListener("click", () => {
                const isOpen = primaryNav.classList.toggle("is-open");
                backdrop.classList.toggle("is-open", isOpen);
                menuToggle.setAttribute("aria-expanded", String(isOpen));
            });

            primaryNav.querySelectorAll(".nav-link").forEach((link) => {
                link.addEventListener("click", () => {
                    primaryNav.classList.remove("is-open");
                    backdrop.classList.remove("is-open");
                    menuToggle.setAttribute("aria-expanded", "false");
                });
            });

            backdrop.addEventListener("click", () => {
                primaryNav.classList.remove("is-open");
                backdrop.classList.remove("is-open");
                menuToggle.setAttribute("aria-expanded", "false");
            });
        })();
    </script>

    <!-- Promos tab: in-content section toggle -->
    <script>
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
    </script>

    <!-- Support drawer + bubble -->
    <script>
        function lrtsOpenDrawer() {
            document.getElementById('lrtsDrawer').classList.add('is-open');
            document.getElementById('lrtsDrawerBackdrop').classList.add('is-open');
            document.body.classList.add('lrts-drawer-open');
            document.getElementById('lrtsDrawer').setAttribute('aria-hidden', 'false');
        }

        function lrtsCloseDrawer() {
            document.getElementById('lrtsDrawer').classList.remove('is-open');
            document.getElementById('lrtsDrawerBackdrop').classList.remove('is-open');
            document.body.classList.remove('lrts-drawer-open');
            document.getElementById('lrtsDrawer').setAttribute('aria-hidden', 'true');
        }

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') lrtsCloseDrawer();
        });

        document.addEventListener('DOMContentLoaded', () => {
            const params = new URLSearchParams(window.location.search);

            if (params.get('tab') === 'help') {
                const helpTab = document.getElementById('help-tab');
                if (helpTab) helpTab.click();
            }

            if (params.get('open_drawer') === '1') {
                lrtsOpenDrawer();
            }

            const ticketId = params.get('ticket');
            if (ticketId) {
                const modalEl = document.getElementById('viewTicketModal' + ticketId);
                if (modalEl) new bootstrap.Modal(modalEl).show();
            }
        });
    </script>

    <!-- Create Ticket modal: chips, counters, receipt preview, dropzone -->
    <script>
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
    </script>

    <!-- Ticket view modals: attachment filename display -->
    <script>
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
    </script>

    <!-- Synchronized Dual-Listbox Logic (UNCHANGED) -->
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

                    const mainEmpty = document.getElementById('empty_msg');
                    if (mainEmpty) mainEmpty.remove();

                    const modalEmpty = document.getElementById('modal_empty_msg');
                    if (modalEmpty) modalEmpty.remove();

                    const uniqueId = 'item-row-' + itemIndex;

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

                    const modalLi = document.createElement('li');
                    modalLi.id = uniqueId + '-modal';
                    modalLi.innerHTML = `• ${productName} — QTY ${qty}`;
                    modalSummaryList.appendChild(modalLi);

                    const hiddenWrapper = document.createElement('div');
                    hiddenWrapper.id = uniqueId + '-hidden';

                    hiddenWrapper.innerHTML = `
                        <input type="hidden" name="items[${itemIndex}][product_name]" value="${productName}">
                        <input type="hidden" name="items[${itemIndex}][quantity]" value="${qty}">
                    `;
                    hiddenContainer.appendChild(hiddenWrapper);

                    mainLi.querySelector('.remove-btn').addEventListener('click', function() {
                        const targetId = this.getAttribute('data-target');

                        document.getElementById(targetId + '-main').remove();
                        document.getElementById(targetId + '-modal').remove();
                        document.getElementById(targetId + '-hidden').remove();

                        if (mainListbox.children.length === 0) {
                            mainListbox.innerHTML = '<li class="p-4 text-muted text-center fw-bold" id="empty_msg">NO PRODUCTS SELECTED YET.</li>';
                            modalSummaryList.innerHTML = '<li class="text-muted" id="modal_empty_msg">No items recorded.</li>';
                            processReceiptButton.disabled = true;
                        }
                    });

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

    <!-- Image Lightbox Logic -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const zoomableImages = document.querySelectorAll('.lrts-zoomable-img');
            const lightbox = document.getElementById('lrtsImageLightbox');
            const lightboxImg = document.getElementById('lrtsLightboxImg');

            // Attach click event to all zoomable images
            zoomableImages.forEach(img => {
                img.addEventListener('click', function() {
                    // Get the source from data-src or src
                    const src = this.getAttribute('data-src') || this.src;
                    lightboxImg.src = src;
                    lightbox.classList.add('is-open');
                    document.body.style.overflow = 'hidden'; // Prevent background scrolling
                });
            });

            // Function to close the lightbox
            window.closeLightbox = function(e) {
                if (e) e.stopPropagation();
                lightbox.classList.remove('is-open');
                document.body.style.overflow = ''; // Restore background scrolling
                
                // Clear the src after the fade-out animation to save memory
                setTimeout(() => {
                    lightboxImg.src = '';
                }, 200);
            }

            // Allow closing with the Escape key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && lightbox.classList.contains('is-open')) {
                    closeLightbox();
                }
            });
        });
    </script>
</body>
</html>