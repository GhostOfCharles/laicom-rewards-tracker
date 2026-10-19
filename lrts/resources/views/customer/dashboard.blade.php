<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RTS - Customer Dashboard</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}?v={{ filemtime(public_path('favicon.ico')) }}" type="image/x-icon">
    <link href="{{ asset('vendor/bootstrap/bootstrap.min.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.css') }}">
    <link rel="stylesheet" href="{{ asset('css/rts.css') }}?v={{ filemtime(public_path('css/rts.css')) }}">
</head>
<body class="rts-page rts-customer-dashboard" data-pulse-url="{{ route('customer.pulse') }}" data-pulse-role="customer">

    @php $eligibilityData = $promotions->map(fn($p)=>['product'=>$p->buy_product_name,'title'=>$p->title,'required'=>$p->required_quantity]); @endphp
    <script>window.rtsCustomerConfig = {notificationsPreview: @json(route('customer.notifications.preview')), notificationsRead: @json(route('customer.notifications.read')), csrf: @json(csrf_token())}; window.rtsPromotions = @json($eligibilityData);</script>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark rts-navbar shadow-sm">
        <div class="container-fluid px-3 px-md-4">
            <button class="btn btn-sm lrts-menu-toggle me-3" id="menuToggle" type="button" aria-expanded="false" aria-controls="primaryNav" aria-label="Toggle navigation">
                <i class="bi bi-list fs-5"></i>
            </button>

            <a class="navbar-brand fw-bold d-flex align-items-center lrts-customer-brand" href="#order">
                <img src="{{ asset('images/rts-logo.png') }}" alt="RTS logo">
                <span class="lrts-customer-brand-divider" aria-hidden="true"></span>
                <span class="lrts-customer-brand-text">REWARDS TRACKER SYSTEM</span>
            </a>

            <div class="d-flex align-items-center text-white ms-auto">
                <span class="me-3 fw-semibold small d-none d-md-inline">WELCOME, {{ strtoupper(auth()->user()->name) }}</span>
                <div class="dropdown me-3" id="customerNotificationsDropdown">
                    <button type="button" class="btn btn-sm btn-outline-light rounded-0 rts-bell" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Open notifications">
                        <i class="bi bi-bell-fill fs-6"></i>
                        <span class="badge rounded-pill bg-danger" data-customer-count="unread_notifications" aria-live="polite" @if($unreadNotificationCount < 1) hidden @endif>{{ $unreadNotificationCount }}</span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end shadow border border-dark rounded-0 p-0 lrts-notification-menu">
                        <div class="lrts-notification-header">NOTIFICATIONS <a class="text-white small" href="{{ route('customer.notifications') }}">View all</a></div>
                        <div class="lrts-notification-list">
                        @forelse($notifications as $notification)
                            <div class="px-3 py-2 small border-bottom {{ $notification->read_at ? 'text-muted' : 'fw-bold' }}"><span>{{ $notification->title }}</span><div>{{ $notification->body }}</div><time class="text-muted">{{ $notification->created_at?->diffForHumans() }}</time></div>
                        @empty<div class="px-3 py-3 small text-muted">You are all caught up.</div>@endforelse
                        </div>
                        <button type="button" class="dropdown-item small fw-bold text-center py-2" onclick="document.getElementById('tracker-tab').click()">VIEW ORDER TRACKER</button>
                    </div>
                </div>
                <a href="{{ route('customer.profile') }}" class="text-white small me-3" aria-label="Account settings"><i class="bi bi-person-circle"></i></a>
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
                        <span>Help &amp;<br>Support <span class="badge bg-danger" data-customer-count="unseen_support_tickets" aria-live="polite" hidden></span></span>
                    </button>
                </li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="lrts-content">

            <!-- Tab Content -->
            <div class="tab-content" id="customerTabsContent">

                @include('customer.partials.order')
                @include('customer.partials.tracker')
                @include('customer.partials.promos')
                @include('customer.partials.help')
            </div> <!-- End Tab Content -->
        </main>
    </div> <!-- End Shell -->

    @include('customer.partials.drawer')
    @include('customer.partials.modals')
    <script src="{{ asset('vendor/bootstrap/bootstrap.bundle.min.js') }}?v={{ filemtime(public_path('vendor/bootstrap/bootstrap.bundle.min.js')) }}"></script>
    <script src="{{ asset('js/customer/dashboard-1.js') }}?v={{ filemtime(public_path('js/customer/dashboard-1.js')) }}"></script>

    <!-- Sidebar toggle (mobile) -->
    <script src="{{ asset('js/customer/dashboard-2.js') }}?v={{ filemtime(public_path('js/customer/dashboard-2.js')) }}"></script>

    <!-- Promos tab: in-content section toggle -->
    <script src="{{ asset('js/customer/dashboard-3.js') }}?v={{ filemtime(public_path('js/customer/dashboard-3.js')) }}"></script>

    <!-- Support drawer + bubble -->
    <script src="{{ asset('js/customer/dashboard-4.js') }}?v={{ filemtime(public_path('js/customer/dashboard-4.js')) }}"></script>

    <!-- Create Ticket modal: chips, counters, receipt preview, dropzone -->
    <script src="{{ asset('js/customer/dashboard-5.js') }}?v={{ filemtime(public_path('js/customer/dashboard-5.js')) }}"></script>

    <!-- Ticket view modals: attachment filename display -->
    <script src="{{ asset('js/customer/dashboard-6.js') }}?v={{ filemtime(public_path('js/customer/dashboard-6.js')) }}"></script>

    <!-- Product combobox and order list logic -->
    <script src="{{ asset('js/customer/dashboard-7.js') }}?v={{ filemtime(public_path('js/customer/dashboard-7.js')) }}"></script>

    <!-- Image Lightbox Logic -->
    <script src="{{ asset('js/customer/dashboard-8.js') }}?v={{ filemtime(public_path('js/customer/dashboard-8.js')) }}"></script>

    <script src="{{ asset('js/customer/dashboard-9.js') }}?v={{ filemtime(public_path('js/customer/dashboard-9.js')) }}"></script>
<script src="{{ asset('js/customer/resubmit.js') }}?v={{ filemtime(public_path('js/customer/resubmit.js')) }}"></script>
<script src="{{ asset('js/rts-poll.js') }}?v={{ filemtime(public_path('js/rts-poll.js')) }}"></script>
<script src="{{ asset('js/form-feedback.js') }}?v={{ filemtime(public_path('js/form-feedback.js')) }}"></script>
</body>
</html>
