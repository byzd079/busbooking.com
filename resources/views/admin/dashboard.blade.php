@extends('admin.layouts.admin')

@section('title', 'Executive Dashboard')
@section('page-title', 'Dashboard')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-11">
        <!-- Dashboard Header -->
        <div class="glass-card mb-4">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div>
                    <h1 class="h3 fw-bold text-dark mb-1">
                        <i class="fas fa-user-shield text-warning me-2"></i> Admin Executive Control Center
                    </h1>
                    <p class="text-muted small mb-0">System performance, bus schedule generator, and order management</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('admin.generate.view') }}" class="btn btn-warning fw-bold">
                        <i class="fas fa-magic me-1"></i> Bulk Bus Generator
                    </a>
                </div>
            </div>
        </div>

        <!-- Metric KPI Cards Row (48px Touch Compliant) -->
        <div class="row g-3 mb-4">
            <!-- Total Revenue -->
            <div class="col-6 col-md-3">
                <div class="glass-card p-3 h-100 border-start border-4 border-success mb-0">
                    <div class="text-muted small fw-bold text-uppercase mb-1">Total Revenue</div>
                    <div class="fs-3 fw-bold text-success">৳ {{ number_format($totalRevenue) }}</div>
                    <div class="small text-muted mt-1"><i class="fas fa-chart-line text-success me-1"></i> Processed Payments</div>
                </div>
            </div>

            <!-- Total Scheduled Buses -->
            <div class="col-6 col-md-3">
                <div class="glass-card p-3 h-100 border-start border-4 border-primary mb-0">
                    <div class="text-muted small fw-bold text-uppercase mb-1">Active Buses</div>
                    <div class="fs-3 fw-bold text-primary">{{ number_format($totalBuses) }}</div>
                    <div class="small text-muted mt-1"><i class="fas fa-bus text-primary me-1"></i> Trip Schedules</div>
                </div>
            </div>

            <!-- Total Confirmed Tickets -->
            <div class="col-6 col-md-3">
                <div class="glass-card p-3 h-100 border-start border-4 border-info mb-0">
                    <div class="text-muted small fw-bold text-uppercase mb-1">Confirmed Tickets</div>
                    <div class="fs-3 fw-bold text-info">{{ number_format($totalPurchasedTickets) }}</div>
                    <div class="small text-muted mt-1"><i class="fas fa-ticket-alt text-info me-1"></i> Booked Orders</div>
                </div>
            </div>

            <!-- Pending Refund Requests -->
            <div class="col-6 col-md-3">
                <div class="glass-card p-3 h-100 border-start border-4 border-danger mb-0">
                    <div class="text-muted small fw-bold text-uppercase mb-1">Refund Requests</div>
                    <div class="fs-3 fw-bold text-danger">{{ number_format($totalRefundRequests) }}</div>
                    <div class="small text-muted mt-1"><i class="fas fa-undo text-danger me-1"></i> Action Required</div>
                </div>
            </div>
        </div>

        <!-- Executive Quick Operations Panel -->
        <div class="glass-card mb-4">
            <h2 class="h5 fw-bold text-dark border-bottom pb-3 mb-3">
                <i class="fas fa-cogs text-primary me-2"></i> Executive Admin Operations
            </h2>

            <div class="row g-3">
                <div class="col-6 col-md-3">
                    <a href="{{ route('admin.generate.view') }}" class="btn btn-warning w-100 py-3 d-flex flex-column align-items-center justify-content-center shadow-sm">
                        <i class="fas fa-magic fs-3 mb-2"></i>
                        <span class="fw-bold">Bulk Bus Generator</span>
                        <small class="opacity-75">Create 100+ Schedules</small>
                    </a>
                </div>

                <div class="col-6 col-md-3">
                    <a href="{{ route('adminOrders') }}" class="btn btn-primary-touch w-100 py-3 d-flex flex-column align-items-center justify-content-center shadow-sm">
                        <i class="fas fa-receipt fs-3 mb-2"></i>
                        <span class="fw-bold">Manage Orders</span>
                        <small class="opacity-75">View Passenger Tickets</small>
                    </a>
                </div>

                <div class="col-6 col-md-3">
                    <a href="{{ route('admin.refund.requests') }}" class="btn btn-outline-danger w-100 py-3 d-flex flex-column align-items-center justify-content-center shadow-sm">
                        <i class="fas fa-undo fs-3 mb-2"></i>
                        <span class="fw-bold">Refund Requests</span>
                        <small class="opacity-75">{{ $totalRefundRequests }} Pending</small>
                    </a>
                </div>

                <div class="col-6 col-md-3">
                    <a href="{{ route('showdata') }}" class="btn btn-outline-touch w-100 py-3 d-flex flex-column align-items-center justify-content-center shadow-sm">
                        <i class="fas fa-bus fs-3 mb-2"></i>
                        <span class="fw-bold">Master Bus List</span>
                        <small class="opacity-75">Route Templates</small>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection