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
                            <i class="bi bi-shield-lock"></i> Your inquiry is private and only visible to system support.
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
                <div class="lrts-ticket-modal-thread" data-poll-thread="{{ route('customer.tickets.replies',$ticket) }}" data-read-url="{{ route('customer.tickets.read',$ticket) }}" data-after="{{ $ticket->replies->max('id') ?? 0 }}">
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
                                        <img src="{{ route('customer.tickets.attachment', $reply) }}"
                                             alt="attachment"
                                             class="lrts-ticket-attachment-img lrts-zoomable-img"
                                             data-src="{{ route('customer.tickets.attachment', $reply) }}">
                                    @else
                                        <a href="{{ route('customer.tickets.attachment', $reply) }}" target="_blank" class="lrts-ticket-attachment-file">
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
            <form id="orderForm" action="{{ route('customer.submit_order') }}" method="POST" enctype="multipart/form-data" class="w-100">
                @csrf
                <div class="modal-content border-dark border-3 rounded-0 bg-light shadow">
                    <div class="modal-header border-bottom border-dark bg-white py-2">
                        <h5 class="modal-title fw-bold">CONFIRM RECEIPT SUBMISSION</h5>
                    </div>
                    <div class="modal-body bg-white p-4">

                        <p class="small fw-bold mb-3 text-uppercase">PLEASE ENTER THE ORDER NUMBER PROVIDED BY YOUR SALESMAN TO LOG YOUR PURCHASE AND CHECK ELIGIBLE REWARDS.</p>

                        <div class="row g-2 mb-3"><div class="col-md-6"><label class="form-label fw-bold small mb-1">SALESMAN ORDER NUMBER:</label><input type="text" name="salesman_order_number" value="{{ old('salesman_order_number') }}" maxlength="100" class="form-control border-dark border-2 rounded-0 fw-bold" placeholder="E.G. ORD-2026-00481" required></div><div class="col-md-6"><label class="form-label fw-bold small mb-1">ORDER DATE:</label><input type="date" name="order_date" value="{{ old('order_date', today()->toDateString()) }}" max="{{ today()->toDateString() }}" min="{{ today()->subDays((int) config('lrts.order_max_age_days', 60))->toDateString() }}" class="form-control border-dark border-2 rounded-0 fw-bold" required></div></div>
                        <div class="mb-3"><label class="form-label fw-bold small mb-1">RECEIPT PHOTO (REQUIRED):</label><input type="file" name="slip" id="receiptSlipInput" class="form-control border-dark border-2 rounded-0" accept="image/jpeg,image/png,image/webp" required><small class="text-muted">Required · JPG, PNG, or WebP · up to {{ (int) (config('lrts.slip_max_kb', 10240) / 1024) }} MB</small><div id="receiptSlipPreview" class="small mt-1 text-muted">No image selected.</div></div>
                        <div class="mb-3"><label class="form-label fw-bold small mb-1">NOTE (OPTIONAL):</label><textarea name="customer_note" maxlength="500" rows="2" class="form-control border-dark border-2 rounded-0" placeholder="Anything staff should know?">{{ old('customer_note') }}</textarea></div>

                        <label class="form-label fw-bold small mb-1">SUMMARY OF LOGGED ITEMS:</label>
                        <div class="border border-dark border-2 overflow-auto bg-white p-2 lrts-order-summary-scroll">
                            <ul id="modal_summary_list" class="list-unstyled small fw-bold mb-0">
                                <li class="text-muted" id="modal_empty_msg">No items recorded.</li>
                            </ul>
                        </div>

                        <div id="hidden_inputs_container"></div>

                    </div>
                    <div class="modal-footer border-top-0 bg-white justify-content-end p-3">
                        <button type="button" class="btn btn-outline-dark fw-bold rounded-0 px-4 border-2" data-bs-dismiss="modal">CANCEL</button>
                        <button type="submit" id="submitOrderButton" class="btn btn-dark fw-bold rounded-0 px-4 border-2">SUBMIT</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @include('customer.partials.order-details')
    <!-- ============================================================
         IMAGE LIGHTBOX MODAL
         ============================================================ -->
    <div id="lrtsImageLightbox" class="lrts-lightbox" onclick="closeLightbox(event)">
        <button type="button" class="lrts-lightbox-close" onclick="closeLightbox(event)" aria-label="Close image">
            <i class="bi bi-x-lg"></i>
        </button>
        <img id="lrtsLightboxImg" src="" alt="Zoomed attachment" onclick="event.stopPropagation()">
    </div>

    @include('components.feedback-toast')
