                <!-- TAB 4: HELP & SUPPORT -->
                <div class="tab-pane fade" id="help" role="tabpanel">

                    <!-- FAQ Card -->
                    <div class="card rts-card mb-4">
                        <div class="card-header">FREQUENTLY ASKED QUESTIONS</div>
                        <div class="card-body p-0">
                            <div class="accordion accordion-flush" id="faqAccordion">
                                @php
                                    $faqs = [
                                        ['q' => 'How do I submit a new order?', 'a' => 'Go to Submit New Order, select products and quantities, enter the order number and date, and upload a receipt photo. Staff use it to review your order.'],
                                        ['q' => 'Why is my order still "Processing"?', 'a' => 'Orders are reviewed by admin before rewards are issued. This usually takes less than 24 hours.'],
                                        ['q' => 'How do I claim a reward?', 'a' => 'After your order is approved, open Promos & Claim and request a claim code. Show it to staff. When staff mark the reward as handed over, confirm receipt in your dashboard; then it moves to Claim History.'],
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
                    <div class="card rts-card">
                        <div class="card-header">NEED MORE HELP?</div>
                        <div class="card-body text-center py-4">
                            <p class="fw-bold mb-3">Can't find what you're looking for? Our support team can help.</p>
                            <button type="button" class="btn rts-btn-accent px-4 py-2 fw-bold"
                                    onclick="lrtsOpenDrawer()">
                                OPEN SUPPORT CHAT
                            </button>
                        </div>
                    </div>

                </div>

