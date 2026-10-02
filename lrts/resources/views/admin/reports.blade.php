@extends('layouts.admin')

@section('content')
    @if (session('success'))
        <div class="alert alert-success border-dark rounded-0 fw-bold">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger border-dark rounded-0">
            <ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="laicom-card p-4 mb-4">
        <h5 class="fw-bold mb-4 border-bottom border-dark pb-2">REPORT TYPE</h5>
        <form method="GET" action="{{ route('admin.reports') }}" id="reportForm">
            <div class="row g-3">
                <div class="col-12 col-lg-6">
                    <label class="form-label small fw-bold" for="reportType">CHOOSE REPORT</label>
                    <select class="form-select border-dark border-2 rounded-0 fw-bold" id="reportType" name="report_type">
                        @foreach ($reports as $key => $label)
                            <option value="{{ $key }}" @selected($reportType === $key)>{{ strtoupper($label) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-lg-6">
                    <label class="form-label small fw-bold" for="reportRange">DATE RANGE</label>
                    <select class="form-select border-dark border-2 rounded-0 fw-bold" id="reportRange" name="range">
                        <option value="today" @selected($rangeKey === 'today')>TODAY</option>
                        <option value="last_7_days" @selected($rangeKey === 'last_7_days')>LAST 7 DAYS</option>
                        <option value="last_30_days" @selected($rangeKey === 'last_30_days')>LAST 30 DAYS</option>
                        <option value="last_90_days" @selected($rangeKey === 'last_90_days')>LAST 90 DAYS</option>
                        <option value="last_12_months" @selected($rangeKey === 'last_12_months')>LAST 12 MONTHS</option>
                        <option value="year_to_date" @selected($rangeKey === 'year_to_date')>YEAR TO DATE</option>
                        <option value="all_time" @selected($rangeKey === 'all_time')>ALL TIME</option>
                        <option value="custom" @selected($rangeKey === 'custom')>CUSTOM RANGE</option>
                    </select>
                </div>
                <div class="col-12 col-md-6 report-custom-date {{ $rangeKey === 'custom' ? '' : 'd-none' }}">
                    <label class="form-label small fw-bold" for="startDate">START DATE</label>
                    <input class="form-control border-dark border-2 rounded-0" type="date" id="startDate" name="start_date" value="{{ $rangeKey === 'custom' ? $fromDate : request('start_date') }}" {{ $rangeKey === 'custom' ? 'required' : '' }}>
                </div>
                <div class="col-12 col-md-6 report-custom-date {{ $rangeKey === 'custom' ? '' : 'd-none' }}">
                    <label class="form-label small fw-bold" for="endDate">END DATE</label>
                    <input class="form-control border-dark border-2 rounded-0" type="date" id="endDate" name="end_date" value="{{ $rangeKey === 'custom' ? $toDate : request('end_date') }}" {{ $rangeKey === 'custom' ? 'required' : '' }}>
                </div>
                <div class="col-12">
                    <h6 class="fw-bold border-bottom border-dark pb-2 mb-3">EXPORT FORMAT <span class="fw-normal text-muted">(optional — leave unselected to preview)</span></h6>
                    <div class="d-flex flex-wrap gap-4 fw-bold">
                        <div class="form-check">
                            <input class="form-check-input border-dark" type="radio" name="export_format" id="xlsxReport" value="xlsx" @checked(request('export_format') === 'xlsx')>
                            <label class="form-check-label" for="xlsxReport">Excel Spreadsheet (XLSX)</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input border-dark" type="radio" name="export_format" id="csvReport" value="csv" @checked(request('export_format') === 'csv')>
                            <label class="form-check-label" for="csvReport">CSV File</label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="text-end mt-4">
                <button type="submit" class="btn laicom-btn-primary px-5 fw-bold"><i class="bi bi-play-fill me-1"></i>GENERATE</button>
            </div>
        </form>
    </div>

    <div class="laicom-card p-4">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 border-bottom border-dark pb-2 mb-3">
            <div>
                <h5 class="fw-bold mb-1">{{ strtoupper($reports[$reportType]) }} PREVIEW</h5>
                <p class="small text-muted mb-0">Range: {{ $rangeLabel }}</p>
            </div>
            <span class="badge text-bg-light border border-dark rounded-0">{{ count($rows) }} {{ \Illuminate\Support\Str::plural('record', count($rows)) }}</span>
        </div>

        <div class="row g-3 mb-4">
            @foreach ($summaries as [$label, $value])
                <div class="col-6 col-xl-{{ count($summaries) > 4 ? '2' : '3' }}">
                    <div class="border border-dark p-3 h-100 bg-white">
                        <div class="small fw-bold text-muted">{{ strtoupper($label) }}</div>
                        <div class="fs-4 fw-bold text-dark">{{ is_numeric($value) ? number_format((float) $value) : $value }}</div>
                    </div>
                </div>
            @endforeach
        </div>

        @if ($reportType === 'inventory_movement')
            <p class="small text-muted">Inventory movement history begins when the stock ledger is introduced. Existing historical opening balances cannot be reconstructed, so this report labels the current balance instead.</p>
        @elseif ($reportType === 'premium_stock')
            <p class="small text-muted">Reward stock is deducted when a receipt is approved and rewards are issued; “Net Outflow (Issued)” reflects that stock movement.</p>
        @else
            <p class="small text-muted">Promotions with no qualifying receipts are included with zero activity. Qualification and issuance are date-filtered; claim rate shows the current claimed status of rewards issued in that period.</p>
        @endif

        <div class="table-responsive border border-dark">
            <table class="table table-hover align-middle mb-0 laicom-table">
                <thead>
                    <tr>@foreach ($columns as $column)<th class="text-nowrap">{{ $column }}</th>@endforeach</tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr>@foreach ($row as $value)<td>{{ $value }}</td>@endforeach</tr>
                    @empty
                        <tr><td class="text-center py-4 fw-bold text-muted" colspan="{{ count($columns) }}">NO DATA FOR THIS REPORT.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <script>
        (() => {
            const range = document.getElementById('reportRange');
            const dateFields = document.querySelectorAll('.report-custom-date');
            const start = document.getElementById('startDate');
            const end = document.getElementById('endDate');
            const updateDateFields = () => {
                const custom = range.value === 'custom';
                dateFields.forEach((field) => field.classList.toggle('d-none', !custom));
                start.required = custom;
                end.required = custom;
            };
            range.addEventListener('change', updateDateFields);
            updateDateFields();
        })();
    </script>
@endsection
