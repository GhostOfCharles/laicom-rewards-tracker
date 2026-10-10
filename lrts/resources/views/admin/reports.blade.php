@extends('layouts.admin')

@section('content')
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

        @php
            $approvalChart = $charts['approval'] ?? ['total' => 0, 'segments' => []];
            $chartCircumference = 2 * pi() * 43;
            $chartOffset = 0;
        @endphp
        <section class="lrts-report-chart-grid mb-4" aria-label="{{ $reports[$reportType] }} charts">
            @if ($reportType === 'promo_performance')
                <article class="lrts-report-chart-card">
                    <h6 class="lrts-report-chart-title">APPROVAL BREAKDOWN</h6>
                    <div class="lrts-report-chart-body lrts-report-donut-layout">
                        <div class="lrts-report-donut-wrap">
                            <svg class="lrts-report-donut" viewBox="0 0 120 120" role="img" aria-label="{{ $approvalChart['total'] }} receipts: {{ $approvalChart['segments'][0]['value'] }} approved, {{ $approvalChart['segments'][1]['value'] }} rejected, {{ $approvalChart['segments'][2]['value'] }} pending">
                                <circle cx="60" cy="60" r="43" fill="none" stroke="#e8edf4" stroke-width="18" />
                                @if ($approvalChart['total'] > 0)
                                    @foreach ($approvalChart['segments'] as $segment)
                                        @if ($segment['value'] > 0)
                                            @php
                                                $segmentLength = ($segment['value'] / $approvalChart['total']) * $chartCircumference;
                                                $segmentOffset = $chartOffset;
                                                $chartOffset += $segmentLength;
                                            @endphp
                                            <circle cx="60" cy="60" r="43" fill="none" stroke="{{ $segment['color'] }}" stroke-width="18" stroke-dasharray="{{ number_format($segmentLength, 3, '.', '') }} {{ number_format($chartCircumference, 3, '.', '') }}" stroke-dashoffset="{{ number_format(-$segmentOffset, 3, '.', '') }}" transform="rotate(-90 60 60)" />
                                        @endif
                                    @endforeach
                                @endif
                                <text x="60" y="57" text-anchor="middle" class="lrts-report-donut-total">{{ number_format($approvalChart['total']) }}</text>
                                <text x="60" y="72" text-anchor="middle" class="lrts-report-donut-caption">RECEIPTS</text>
                            </svg>
                        </div>
                        <ul class="lrts-report-chart-legend mb-0" aria-label="Receipt statuses">
                            @foreach ($approvalChart['segments'] as $segment)
                                <li><span class="lrts-report-legend-swatch" style="background-color: {{ $segment['color'] }}"></span><span>{{ $segment['label'] }}</span><strong>{{ number_format($segment['value']) }}</strong></li>
                            @endforeach
                        </ul>
                    </div>
                </article>
            @endif

            @foreach (($charts['bar_charts'] ?? []) as $barChart)
                @php
                    $chartRows = $barChart['rows'];
                    $chartValues = collect($chartRows)->flatMap(fn ($row) => [$row['primary'], $row['secondary'] ?? 0]);
                    $chartMaximum = max(0, (int) $chartValues->max());
                @endphp
                <article class="lrts-report-chart-card">
                    <h6 class="lrts-report-chart-title">{{ $barChart['title'] }}</h6>
                    <div class="lrts-report-chart-body">
                        <div class="lrts-report-bar-legend">
                            <span><i class="is-primary"></i>{{ $barChart['primary_label'] }}</span>
                            @if ($barChart['secondary_label'])<span><i class="is-secondary"></i>{{ $barChart['secondary_label'] }}</span>@endif
                        </div>
                        @forelse ($chartRows as $chartRow)
                            @php
                                $primaryWidth = $chartMaximum > 0 ? ($chartRow['primary'] / $chartMaximum) * 100 : 0;
                                $secondaryWidth = $chartMaximum > 0 && ($chartRow['secondary'] ?? 0) > 0 ? ($chartRow['secondary'] / $chartMaximum) * 100 : 0;
                            @endphp
                            <div class="lrts-report-promotion-bar-row">
                                <div class="lrts-report-promotion-bar-label" title="{{ $chartRow['title'] }}">{{ $chartRow['title'] }}</div>
                                <div class="lrts-report-bar-pair">
                                    <div class="lrts-report-bar-line" aria-label="{{ $barChart['primary_label'] }}: {{ $chartRow['primary'] }}"><span class="lrts-report-bar"><i class="is-primary" style="width: {{ number_format($primaryWidth, 2, '.', '') }}%"></i></span><strong>{{ number_format($chartRow['primary']) }}</strong></div>
                                    @if ($barChart['secondary_label'])
                                        <div class="lrts-report-bar-line" aria-label="{{ $barChart['secondary_label'] }}: {{ $chartRow['secondary'] }}"><span class="lrts-report-bar"><i class="is-secondary" style="width: {{ number_format($secondaryWidth, 2, '.', '') }}%"></i></span><strong>{{ number_format($chartRow['secondary']) }}</strong></div>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <p class="small text-muted text-center py-4 mb-0">No products or promotions are available to chart yet.</p>
                        @endforelse
                        @if ($barChart['note'])<p class="small text-muted mb-0 mt-3">{{ $barChart['note'] }}</p>@endif
                    </div>
                </article>
            @endforeach
        </section>

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
