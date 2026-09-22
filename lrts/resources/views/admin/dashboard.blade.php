<!-- resources/views/admin/dashboard.blade.php -->
@extends('layouts.admin')

@section('content')
<div class="row">
    <div class="col-12 mb-4">
        <h2>Dashboard Overview</h2>
        <p class="text-muted">Welcome to the LRTS Administration Portal.</p>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="card text-white bg-primary mb-3">
            <div class="card-body">
                <h5 class="card-title">Pending Claims</h5>
                <p class="card-text display-6">0</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-white bg-success mb-3">
            <div class="card-body">
                <h5 class="card-title">Active Promotions</h5>
                <p class="card-text display-6">0</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-white bg-warning mb-3">
            <div class="card-body">
                <h5 class="card-title">Low Stock Alerts</h5>
                <p class="card-text display-6">0</p>
            </div>
        </div>
    </div>
</div>
@endsection