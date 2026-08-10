@extends('layout')

@section('title', 'JatraPoth - Book Bus Tickets Online')

@section('styles')
<style>
    /* Quick-select popular route pills. Built on the same primary tokens as
       the rest of the app so they read as one system with the search card. */
    .route-chip {
        display: inline-flex;
        align-items: center;
        gap: .15rem;
        min-height: 40px;
        padding: .5rem 1rem;
        background: var(--primary-light, #eff6ff);
        color: var(--primary, #2563eb);
        border: 1.5px solid rgba(37, 99, 235, .25);
        border-radius: 999px;
        font-size: .875rem;
        font-weight: 600;
        line-height: 1.2;
        font-family: inherit;
        cursor: pointer;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        touch-action: manipulation;
    }

    .route-chip:hover {
        background: #dbeafe;
        border-color: var(--primary, #2563eb);
        transform: translateY(-1px);
        box-shadow: var(--shadow-sm, 0 2px 4px rgba(0,0,0,0.05));
    }

    .route-chip:focus-visible {
        outline: 3px solid var(--primary, #2563eb);
        outline-offset: 2px;
        background: #dbeafe;
        border-color: var(--primary, #2563eb);
    }

    .route-chip:active {
        transform: translateY(0) scale(0.97);
    }

    .route-chip .fa-arrow-right {
        font-size: .7rem;
        opacity: .65;
    }

    @media (prefers-reduced-motion: reduce) {
        .route-chip { transition: none; }
        .route-chip:hover { transform: none; }
    }
</style>
@endsection

@section('content')
@php
    // Controller-supplied; the ?? fallbacks keep this view renderable even if it
    // is ever rendered from a context that does not pass them.
    $defaultRoute = $defaultRoute ?? ['starting_point' => 'Dhaka', 'ending_point' => '', 'source' => 'fallback'];
    $popularRoutes = $popularRoutes ?? [];
    $cities = $cities ?? [];
    $operators = $operators ?? [];
@endphp
<div class="row justify-content-center">
    <div class="col-lg-8 col-md-10">
        <!-- Hero Section Header -->
        <div class="text-center text-white mb-4">
            <h1 class="display-6 fw-bold text-white mb-2">Book Bus Tickets Online</h1>
            <p class="lead opacity-90 text-white-50">Fast, secure, and hassle-free travel across Bangladesh</p>
        </div>

        <!-- Main Search Form Card -->
        <div class="glass-card">
            <form action="{{ route('search_bus') }}" method="GET" id="busSearchForm">
                <div class="row g-3">
                    <!-- Origin City Input -->
                    <div class="col-md-6">
                        <label for="starting_point" class="form-label fw-semibold">
                            <i class="fas fa-map-marker-alt text-danger me-1"></i> Starting From
                        </label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="starting_point" name="starting_point"
                                list="cityList" placeholder="e.g. Dhaka" required autocomplete="off"
                                value="{{ request('starting_point', $defaultRoute['starting_point']) }}">
                        </div>
                    </div>

                    <!-- Destination City Input -->
                    <div class="col-md-6">
                        <label for="ending_point" class="form-label fw-semibold">
                            <i class="fas fa-location-arrow text-primary me-1"></i> Going To
                        </label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="ending_point" name="ending_point"
                                list="cityList" placeholder="e.g. Chattogram" required autocomplete="off"
                                value="{{ request('ending_point', $defaultRoute['ending_point']) }}">
                        </div>
                    </div>

                    <!-- Why these two cities were prefilled -->
                    @if(!request()->hasAny(['starting_point', 'ending_point']) && $defaultRoute['ending_point'])
                    <div class="col-12">
                        <p class="small text-muted mb-0">
                            @if($defaultRoute['source'] === 'personal')
                                <i class="fas fa-user-clock text-primary me-1"></i>
                                Prefilled with the route you travel most — change it any time.
                            @elseif($defaultRoute['source'] === 'bookings')
                                <i class="fas fa-fire text-danger me-1"></i>
                                Prefilled with the most-booked route of the last {{ \App\Services\RouteInsights::WINDOW_DAYS }} days.
                            @else
                                <i class="fas fa-route text-muted me-1"></i>
                                Prefilled with our busiest scheduled route.
                            @endif
                        </p>
                    </div>
                    @endif

                    <!-- Shared City Datalist, built from the routes actually served -->
                    <datalist id="cityList">
                        @forelse($cities as $city)
                            <option value="{{ $city }}">
                        @empty
                            <option value="Dhaka">
                            <option value="Chattogram">
                            <option value="Sylhet">
                            <option value="Rajshahi">
                            <option value="Khulna">
                            <option value="Barishal">
                            <option value="Rangpur">
                            <option value="Mymensingh">
                            <option value="Cox's Bazar">
                        @endforelse
                    </datalist>

                    <!-- Departure Date Input -->
                    <div class="col-md-6">
                        <label for="depart-date" class="form-label fw-semibold">
                            <i class="fas fa-calendar-alt text-success me-1"></i> Journey Date
                        </label>
                        <input type="date" class="form-control" id="depart-date" name="date"
                            value="{{ request('date', date('Y-m-d')) }}" min="{{ date('Y-m-d') }}" required>
                    </div>

                    <!-- Return Date Input (Optional) -->
                    <div class="col-md-6">
                        <label for="return-date" class="form-label fw-semibold">
                            <i class="fas fa-calendar-check text-muted me-1"></i> Return Date (Optional)
                        </label>
                        <input type="date" class="form-control" id="return-date" name="return-date"
                            min="{{ date('Y-m-d') }}">
                    </div>

                    <!-- Operator Name (Optional): search by bus rather than by route -->
                    <div class="col-12">
                        <label for="bus_name" class="form-label fw-semibold">
                            <i class="fas fa-bus text-info me-1"></i> Bus Name (Optional)
                        </label>
                        <input type="text" class="form-control" id="bus_name" name="bus_name"
                            list="operatorList" autocomplete="off" value="{{ request('bus_name') }}"
                            placeholder="e.g. Green Line — leave blank for all operators">
                        <small class="text-muted">Looking for a specific operator? Type its name to filter results.</small>
                        <datalist id="operatorList">
                            @foreach($operators as $operator)
                                <option value="{{ $operator }}">
                            @endforeach
                        </datalist>
                    </div>

                    <!-- Popular Route Quick Chips (One-Tap Selection) -->
                    <div class="col-12 mt-3">
                        <small class="text-muted d-block mb-2 fw-semibold">Popular Routes (Tap to select):</small>
                        <div class="d-flex flex-wrap gap-2">
                            @forelse($popularRoutes as $route)
                                <button type="button" class="route-chip"
                                    onclick="selectRoute('{{ addslashes($route['starting_point']) }}', '{{ addslashes($route['ending_point']) }}')">
                                    {{ $route['starting_point'] }} <i class="fas fa-arrow-right mx-1 text-muted"></i> {{ $route['ending_point'] }}
                                </button>
                            @empty
                                <button type="button" class="route-chip" onclick="selectRoute('Dhaka', 'Chattogram')">
                                    Dhaka <i class="fas fa-arrow-right mx-1 text-muted"></i> Chattogram
                                </button>
                                <button type="button" class="route-chip" onclick="selectRoute('Dhaka', 'Sylhet')">
                                    Dhaka <i class="fas fa-arrow-right mx-1 text-muted"></i> Sylhet
                                </button>
                                <button type="button" class="route-chip" onclick="selectRoute('Dhaka', 'Cox\'s Bazar')">
                                    Dhaka <i class="fas fa-arrow-right mx-1 text-muted"></i> Cox's Bazar
                                </button>
                            @endforelse
                        </div>
                    </div>

                    <!-- Primary CTA Button (Thumb Zone Priority) -->
                    <div class="col-12 mt-4">
                        <button type="submit" class="btn btn-primary-touch w-100 py-3 text-uppercase tracking-wider fw-bold">
                            <i class="fas fa-search me-2"></i> Search Available Buses
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Features Grid -->
        <div class="row g-3 text-white mt-2">
            <div class="col-md-4">
                <div class="p-3 glass-card bg-dark bg-opacity-50 border-secondary border-opacity-25 h-100 text-center">
                    <div class="fs-2 text-warning mb-2"><i class="fas fa-shield-alt"></i></div>
                    <h3 class="h6 fw-bold text-white">Instant E-Ticket</h3>
                    <p class="small text-white-50 mb-0">Get your ticket confirmed immediately with QR verification.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="p-3 glass-card bg-dark bg-opacity-50 border-secondary border-opacity-25 h-100 text-center">
                    <div class="fs-2 text-success mb-2"><i class="fas fa-star"></i></div>
                    <h3 class="h6 fw-bold text-white">Verified Seat Reviews</h3>
                    <p class="small text-white-50 mb-0">Read passenger feedback for every coach before booking.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="p-3 glass-card bg-dark bg-opacity-50 border-secondary border-opacity-25 h-100 text-center">
                    <div class="fs-2 text-info mb-2"><i class="fas fa-credit-card"></i></div>
                    <h3 class="h6 fw-bold text-white">Secure Payments</h3>
                    <p class="small text-white-50 mb-0">Pay with bKash, Nagad, cards or net banking safely.</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function selectRoute(start, end) {
        document.getElementById('starting_point').value = start;
        document.getElementById('ending_point').value = end;
        document.getElementById('depart-date').focus();
    }
</script>
@endsection