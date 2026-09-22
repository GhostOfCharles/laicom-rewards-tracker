@extends('layouts.admin')

@section('content')
<div class="border border-dark border-2 p-4 mb-5" style="max-width: 600px;">
    <h5 class="fw-bold mb-4 border-bottom border-dark pb-2">REPORT TYPE</h5>
    <form>
        <div class="mb-4">
            <select class="form-select border-dark rounded-0 fw-bold">
                <option>PROMO PERFORMANCE ANALYSIS</option>
            </select>
        </div>
        
        <h5 class="fw-bold mb-3 border-bottom border-dark pb-2">DATE RANGE</h5>
        <div class="mb-4">
            <select class="form-select border-dark rounded-0 fw-bold">
                <option>1 MONTH</option>
            </select>
        </div>

        <h5 class="fw-bold mb-3 border-bottom border-dark pb-2">EXPORT FORMAT:</h5>
        <div class="mb-4 fw-bold">
            <div class="form-check mb-2">
                <input class="form-check-input border-dark" type="radio" name="exportFormat" id="pdfReport" checked>
                <label class="form-check-label" for="pdfReport">(-) PDF REPORT</label>
            </div>
            <div class="form-check">
                <input class="form-check-input border-dark" type="radio" name="exportFormat" id="excelReport">
                <label class="form-check-label" for="excelReport">( ) EXCEL SPREADSHEET (.XLSX)</label>
            </div>
        </div>

        <div class="text-end mt-4">
            <button type="button" class="btn btn-success rounded-0 px-5 fw-bold">GENERATE</button>
        </div>
    </form>
</div>
@endsection