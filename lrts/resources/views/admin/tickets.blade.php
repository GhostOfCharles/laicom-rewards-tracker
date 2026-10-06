@extends('layouts.admin')

@section('content')

@if (session('success'))
    <div class="alert alert-success py-2 small fw-bold rounded-0 mb-3">{{ session('success') }}</div>
@endif
@if ($errors->any())
    <div class="alert alert-danger py-2 small fw-bold rounded-0 mb-3">{{ $errors->first() }}</div>
@endif

@php
    $filters = [
        'all' => 'ALL',
        'open' => 'OPEN',
        'pending' => 'PENDING',
        'in_progress' => 'IN PROGRESS',
        'resolved' => 'RESOLVED',
        'closed' => 'CLOSED',
    ];
@endphp

<div class="adm-ticket-grid">

    {{-- ============================================================
         LEFT — TICKET QUEUE
         ============================================================ --}}
    <section class="laicom-card adm-queue-card">

        <div class="card-header">TICKET QUEUE</div>

        <div class="adm-ticket-filters">
            <span class="adm-filter-label">FILTER:</span>
            @foreach ($filters as $key => $label)
                <a href="{{ route('admin.tickets', ['status' => $key]) }}"
                   class="adm-filter-pill {{ $filter === $key ? 'is-active' : '' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>

        <div class="adm-ticket-list">
            @forelse ($tickets as $ticket)
                @php
                    $isSelected = $selectedTicket && $selectedTicket->id === $ticket->id;
                    $badgeClass = match($ticket->status) {
                        'open' => 'lrts-badge-open',
                        'pending' => 'lrts-badge-pending',
                        'in_progress' => 'lrts-badge-progress',
                        'resolved' => 'lrts-badge-resolved',
                        'closed' => 'lrts-badge-closed',
                        default => 'lrts-badge-closed',
                    };
                @endphp

                <a href="{{ route('admin.tickets', ['ticket' => $ticket->id, 'status' => $filter]) }}"
                   class="adm-ticket-row {{ $isSelected ? 'is-selected' : '' }}">
                    <div class="adm-ticket-row-main">
                        <div class="adm-ticket-row-subject">
                            #{{ str_pad($ticket->id, 4, '0', STR_PAD_LEFT) }} · {{ $ticket->subject }}
                        </div>
                        <div class="adm-ticket-row-meta">
                            <span class="lrts-badge {{ $badgeClass }}">{{ $ticket->statusLabel() }}</span>
                            <span>· {{ $ticket->replies_count }} {{ \Illuminate\Support\Str::plural('reply', $ticket->replies_count) }}</span>
                            <span>· {{ $ticket->created_at->diffForHumans() }}</span>
                        </div>
                    </div>
                    <i class="bi bi-chevron-right"></i>
                </a>
            @empty
                <div class="lrts-ticket-empty">
                    <i class="bi bi-inbox"></i>
                    <div>NO TICKETS IN THIS FILTER</div>
                    <small>Try a different filter or wait for new inquiries.</small>
                </div>
            @endforelse
        </div>

    </section>

    {{-- ============================================================
         RIGHT — TICKET DETAIL
         ============================================================ --}}
    <section class="laicom-card adm-detail-card">

        @if ($selectedTicket)

            <div class="card-header">
                TICKET #{{ str_pad($selectedTicket->id, 4, '0', STR_PAD_LEFT) }} · {{ strtoupper($selectedTicket->subject) }}
            </div>

            <div class="adm-ticket-meta">
                <div>
                    <div class="adm-meta-label">FROM:</div>
                    <div class="adm-meta-value">{{ $selectedTicket->user->name ?? 'Unknown' }}</div>
                </div>
                <div>
                    <div class="adm-meta-label">STORE:</div>
                    <div class="adm-meta-value">{{ $selectedTicket->user->store_name ?? '—' }}</div>
                </div>
                <div>
                    <div class="adm-meta-label">SUBMITTED:</div>
                    <div class="adm-meta-value">{{ $selectedTicket->created_at->format('m/d/Y · g:i A') }}</div>
                </div>
                <div>
                    <div class="adm-meta-label">STATUS:</div>
                    <div class="adm-meta-value">
                        <span class="lrts-badge {{ match($selectedTicket->status) {
                            'open' => 'lrts-badge-open',
                            'pending' => 'lrts-badge-pending',
                            'in_progress' => 'lrts-badge-progress',
                            'resolved' => 'lrts-badge-resolved',
                            'closed' => 'lrts-badge-closed',
                            default => 'lrts-badge-closed',
                        } }}">{{ $selectedTicket->statusLabel() }}</span>
                    </div>
                </div>
            </div>

            <div class="adm-ticket-thread">
                @forelse ($selectedTicket->replies as $reply)
                    @php $isAdmin = $reply->user_id !== $selectedTicket->user_id; @endphp

                    @if ($reply->is_internal_note)
                        <div class="adm-thread-note">
                            <div class="adm-thread-note-head">
                                <span class="adm-note-badge">INTERNAL NOTE</span>
                                <span class="adm-thread-meta-inline">ADMIN · {{ $reply->created_at->format('M d, g:i A') }} · {{ $reply->created_at->diffForHumans() }}</span>
                            </div>
                            <div class="adm-thread-body">{{ $reply->body }}</div>

                            @if ($reply->attachment_path)
                                <div class="adm-thread-attachment">
                                    @if (preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $reply->attachment_path))
                                        <img src="{{ route('admin.tickets.attachment', $reply) }}"
                                             class="adm-attachment-img lrts-zoomable-img"
                                             data-src="{{ route('admin.tickets.attachment', $reply) }}"
                                             alt="attachment"
                                             style="cursor: zoom-in;">
                                    @else
                                        <a href="{{ route('admin.tickets.attachment', $reply) }}" target="_blank" class="adm-attachment-file">
                                            <i class="bi bi-file-earmark-pdf"></i> View attachment
                                        </a>
                                    @endif
                                </div>
                            @endif

                            <div class="adm-thread-footnote">This note is only visible to administrators.</div>
                        </div>
                    @else
                        <div class="adm-thread-msg {{ $isAdmin ? 'is-admin' : 'is-customer' }}">
                            <div class="adm-thread-meta">
                                {{ $isAdmin ? 'ADMIN' : 'CUSTOMER' }} · {{ $reply->created_at->format('M d, g:i A') }} · {{ $reply->created_at->diffForHumans() }}
                            </div>
                            <div class="adm-thread-body">{{ $reply->body }}</div>

                            @if ($reply->attachment_path)
                                <div class="adm-thread-attachment">
                                    @if (preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $reply->attachment_path))
                                        <img src="{{ route('admin.tickets.attachment', $reply) }}"
                                             class="adm-attachment-img lrts-zoomable-img"
                                             data-src="{{ route('admin.tickets.attachment', $reply) }}"
                                             alt="attachment"
                                             style="cursor: zoom-in;">
                                    @else
                                        <a href="{{ route('admin.tickets.attachment', $reply) }}" target="_blank" class="adm-attachment-file">
                                            <i class="bi bi-file-earmark-pdf"></i> View attachment
                                        </a>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @endif
                @empty
                    <div class="text-muted small fw-bold text-center py-4">No messages yet.</div>
                @endforelse
            </div>

            @if ($selectedTicket->status !== 'closed')
                <div class="adm-ticket-composer">
                    <div class="adm-composer-title">REPLY AS ADMIN</div>

                    <form id="adminReplyForm"
                          action="{{ route('admin.tickets.reply', $selectedTicket) }}?status={{ $filter }}"
                          method="POST"
                          enctype="multipart/form-data">
                        @csrf
                        <textarea name="body"
                                  rows="3"
                                  class="form-control adm-composer-textarea"
                                  placeholder="Type your reply... (min 10 characters)"
                                  minlength="10"
                                  required></textarea>

                        <label class="adm-composer-check">
                            <input type="checkbox" name="is_internal_note" value="1">
                            Mark as internal note (not visible to customer)
                        </label>

                        <div class="adm-composer-attach">
                            <label class="adm-composer-file-label" for="adminAttachment">
                                <i class="bi bi-paperclip"></i> Attach file
                            </label>
                            <input type="file" name="attachment" id="adminAttachment" class="adm-composer-file" accept="image/*,application/pdf">
                            <span class="adm-composer-file-name" id="adminAttachmentName"></span>
                        </div>
                    </form>

                    <div class="adm-composer-actions">
                        <button type="submit" form="adminReplyForm" class="adm-btn adm-btn-navy">
                            <i class="bi bi-send-fill"></i> SEND REPLY
                        </button>

                        <form action="{{ route('admin.tickets.status', $selectedTicket) }}?status={{ $filter }}" method="POST">
                            @csrf
                            <input type="hidden" name="status" value="pending">
                            <button type="submit" class="adm-btn adm-btn-outline">MARK PENDING</button>
                        </form>

                        <form action="{{ route('admin.tickets.status', $selectedTicket) }}?status={{ $filter }}" method="POST">
                            @csrf
                            <input type="hidden" name="status" value="resolved">
                            <button type="submit" class="adm-btn adm-btn-green">MARK RESOLVED</button>
                        </form>

                        <form action="{{ route('admin.tickets.status', $selectedTicket) }}?status={{ $filter }}" method="POST">
                            @csrf
                            <input type="hidden" name="status" value="closed">
                            <button type="submit" class="adm-btn adm-btn-red">CLOSE TICKET</button>
                        </form>
                    </div>
                </div>
            @else
                <div class="lrts-ticket-closed-notice">
                    <i class="bi bi-lock-fill"></i>
                    This ticket is closed. Reopen it first if you need to add another reply.
                </div>
            @endif

        @else

            <div class="adm-ticket-empty-state">
                <i class="bi bi-inbox" style="font-size: 3rem; opacity: .4;"></i>
                <p class="mt-3 mb-0 fw-bold" style="color: var(--laicom-navy);">NO TICKET SELECTED</p>
                <p class="small mb-0">Pick a ticket from the queue to view its conversation.</p>
            </div>

        @endif

    </section>

</div>

{{-- ============================================================
     IMAGE LIGHTBOX MODAL
     ============================================================ --}}
<div id="lrtsImageLightbox" class="lrts-lightbox" onclick="closeLightbox(event)">
    <button type="button" class="lrts-lightbox-close" onclick="closeLightbox(event)" aria-label="Close image">
        <i class="bi bi-x-lg"></i>
    </button>
    <img id="lrtsLightboxImg" src="" alt="Zoomed attachment" onclick="event.stopPropagation()">
</div>

<script>
    (function () {
        const input = document.getElementById('adminAttachment');
        const label = document.getElementById('adminAttachmentName');
        if (!input || !label) return;
        input.addEventListener('change', () => {
            label.textContent = input.files.length ? input.files[0].name : '';
        });
    })();

    // Image Lightbox Logic
    document.addEventListener('DOMContentLoaded', function() {
        const zoomableImages = document.querySelectorAll('.lrts-zoomable-img');
        const lightbox = document.getElementById('lrtsImageLightbox');
        const lightboxImg = document.getElementById('lrtsLightboxImg');

        // Attach click event to all zoomable images
        zoomableImages.forEach(img => {
            img.addEventListener('click', function() {
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

@endsection