@extends('layouts.admin')

@section('content')
<div class="laicom-card p-4 mb-5" style="max-width: 600px;">
    <h5 class="fw-bold mb-4 border-bottom border-dark pb-2">REPORT TYPE</h5>
    <form method="GET" action="{{ route('admin.reports') }}">
        <div class="mb-4">
            <select class="form-select border-dark border-2 rounded-0 fw-bold" name="report_type">
                <option>PROMO PERFORMANCE ANALYSIS</option>
            </select>
        </div>
        
        <h5 class="fw-bold mb-3 border-bottom border-dark pb-2">DATE RANGE</h5>
        <div class="mb-4">
            <select class="form-select border-dark border-2 rounded-0 fw-bold" name="range">
                <option value="7" {{ $days === 7 ? 'selected' : '' }}>1 WEEK</option>
                <option value="30" {{ $days === 30 ? 'selected' : '' }}>1 MONTH</option>
                <option value="90" {{ $days === 90 ? 'selected' : '' }}>3 MONTHS</option>
                <option value="365" {{ $days === 365 ? 'selected' : '' }}>1 YEAR</option>
            </select>
        </div>

        <h5 class="fw-bold mb-3 border-bottom border-dark pb-2">EXPORT FORMAT:</h5>
        <div class="mb-4 fw-bold">
            <div class="form-check mb-2">
                <input class="form-check-input border-dark" type="radio" name="export_format" id="pdfReport" value="pdf" checked>
                <label class="form-check-label" for="pdfReport">PDF REPORT (preview only)</label>
            </div>
            <div class="form-check">
                <input class="form-check-input border-dark" type="radio" name="export_format" id="excelReport" value="excel">
                <label class="form-check-label" for="excelReport">EXCEL SPREADSHEET (preview only)</label>
            </div>
        </div>

        <div class="text-end mt-4">
            <button type="submit" class="btn btn-success rounded-0 px-5 fw-bold">GENERATE</button>
        </div>
    </form>
</div>

<div class="laicom-card p-4" style="max-width: 800px;">
    <h5 class="fw-bold border-bottom border-dark pb-2">PROMO PERFORMANCE PREVIEW</h5>
    <p class="small text-muted">{{ $from->format('M d, Y') }} to {{ now()->format('M d, Y') }}. Exports are intentionally deferred; this preview is suitable for the capstone demonstration.</p>
    <div class="row text-center g-3">
        <div class="col-6 col-md"><div class="border border-dark p-3"><div class="small fw-bold">SUBMITTED</div><div class="fs-4 fw-bold">{{ $summary['submitted'] }}</div></div></div>
        <div class="col-6 col-md"><div class="border border-dark p-3"><div class="small fw-bold">APPROVED</div><div class="fs-4 fw-bold text-success">{{ $summary['approved'] }}</div></div></div>
        <div class="col-6 col-md"><div class="border border-dark p-3"><div class="small fw-bold">REJECTED</div><div class="fs-4 fw-bold text-danger">{{ $summary['rejected'] }}</div></div></div>
        <div class="col-6 col-md"><div class="border border-dark p-3"><div class="small fw-bold">REWARDS ISSUED</div><div class="fs-4 fw-bold">{{ $summary['rewards_issued'] }}</div></div></div>
        <div class="col-12 col-md"><div class="border border-dark p-3"><div class="small fw-bold">REWARDS CLAIMED</div><div class="fs-4 fw-bold">{{ $summary['rewards_claimed'] }}</div></div></div>
    </div>
</div>
@endsection
