@extends('admin.layouts.admin')

@section('title', 'Bulk Bus Generator')
@section('page-title', 'Bulk Bus Schedule Generator')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <!-- Header Card -->
        <div class="glass-card mb-4">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <h1 class="h3 fw-bold text-dark mb-1">
                        <i class="fas fa-magic text-primary me-2"></i> Bulk Bus Schedule Generator
                    </h1>
                    <p class="text-muted small mb-0">
                        Automatically generate bus trip schedules across date ranges for all master bus routes
                    </p>
                </div>
                <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-arrow-left me-1"></i> Dashboard
                </a>
            </div>
        </div>

        <!-- Generator Form Card -->
        <div class="glass-card">
            <form action="{{ route('admin.generate.process') }}" method="POST">
                @csrf
                <div class="row g-4">
                    <div class="col-md-6">
                        <label for="start_date" class="form-label fw-bold">Start Date:</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" value="{{ date('Y-m-d') }}" required>
                    </div>

                    <div class="col-md-6">
                        <label for="end_date" class="form-label fw-bold">End Date:</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" value="{{ date('Y-m-d', strtotime('+7 days')) }}" required>
                    </div>

                    <div class="col-12">
                        <div class="bg-light p-3 rounded-3">
                            <h6 class="fw-bold text-dark mb-2"><i class="fas fa-bus text-primary me-2"></i> Master Bus Route Templates ({{ count($masterBuses) }})</h6>
                            <p class="small text-muted mb-2">The generator will clone schedules for all master routes listed below across the selected date range:</p>
                            <div class="d-flex flex-wrap gap-2">
                                @foreach($masterBuses as $b)
                                    <span class="badge bg-white text-dark border p-2">
                                        <strong>{{ $b->bus_name }}</strong> ({{ $b->coach_no }}) — {{ $b->starting_point }} &rarr; {{ $b->ending_point }} @ {{ $b->departing_time }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="col-12 mt-4">
                        <button type="submit" class="btn btn-primary-touch w-100 py-3 text-uppercase fw-bold">
                            <i class="fas fa-magic me-2"></i> Generate All Bus Schedules Now
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
