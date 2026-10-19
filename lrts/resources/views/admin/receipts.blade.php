@extends('layouts.admin')
@section('content')
@include('admin.partials.receipts-list')
<div class="modal fade" id="receiptPanelModal" tabindex="-1" aria-labelledby="receiptPanelTitle" aria-hidden="true"><div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable"><div class="modal-content"><div class="modal-header"><h5 class="modal-title text-white" id="receiptPanelTitle">Receipt details</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button></div><div class="modal-body" id="receiptPanelContent" aria-live="polite"></div></div></div></div>
@endsection
@push('scripts')<script src="{{ asset('js/receipt-panel.js') }}?v={{ filemtime(public_path('js/receipt-panel.js')) }}"></script>@endpush
