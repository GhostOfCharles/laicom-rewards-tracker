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
                {{ $tickets->appends(['tab'=>'help','open_drawer'=>1])->links() }}
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

