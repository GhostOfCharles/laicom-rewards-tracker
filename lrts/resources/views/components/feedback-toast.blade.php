@if (session('success') || $errors->any())
    <div class="toast-container position-fixed bottom-0 start-0 p-3 lrts-feedback-toast-container">
        <div class="toast show border border-dark rounded-0 shadow" role="status" aria-live="polite" aria-atomic="true" data-feedback-toast>
            <div class="toast-header text-white rounded-0">
                <i class="bi {{ session('success') ? 'bi-check-circle-fill' : 'bi-exclamation-circle-fill' }} me-2"></i>
                <strong class="me-auto">{{ session('success') ? 'UPDATE COMPLETE' : 'PLEASE CHECK THIS' }}</strong>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body fw-semibold">{{ session('success') ?: $errors->first() }}</div>
        </div>
    </div>
@endif
