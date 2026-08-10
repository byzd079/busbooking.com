@extends('layout')

@section('title', 'Checkout & Payment - JatraPoth')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <!-- Back Navigation -->
        <div class="mb-3">
            <a href="{{ route('seat_view', ['id' => $bus->id]) }}" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Back to Seat Selection
            </a>
        </div>

        <!-- Order Breakdown Card -->
        <div class="glass-card mb-4">
            <h1 class="h4 fw-bold text-dark border-bottom pb-3 mb-3">
                <i class="fas fa-ticket-alt text-primary me-2"></i> Ticket & Journey Summary
            </h1>

            <div class="row g-3">
                <!-- Bus Operator & Route -->
                <div class="col-md-6">
                    <div class="bg-light p-3 rounded-3 h-100">
                        <div class="fw-bold text-primary fs-5 mb-1">{{ $bus->bus_name }}</div>
                        <div class="small text-muted mb-2">Coach No: {{ $bus->coach_no }} ({{ $bus->coach_type }})</div>
                        <div class="fw-semibold text-dark">
                            {{ $bus->starting_point }} <i class="fas fa-arrow-right mx-1 text-muted"></i> {{ $bus->ending_point }}
                        </div>
                        <div class="small text-muted mt-1">
                            <i class="far fa-calendar-alt me-1"></i> {{ $bus->date }} &bull; 
                            <i class="far fa-clock me-1"></i> {{ $bus->departing_time }}
                        </div>
                    </div>
                </div>

                <!-- Seat & Price Breakdown -->
                <div class="col-md-6">
                    <div class="bg-light p-3 rounded-3 h-100">
                        <div class="small text-muted mb-1">Selected Seats:</div>
                        <div class="d-flex flex-wrap gap-1 mb-3">
                            @php $totalFare = 0; $fare = floatval($bus->fare); @endphp
                            @foreach ($ticketlist as $ticket)
                                @php $totalFare += $fare; @endphp
                                <span class="badge bg-primary fs-6">{{ $ticket }}</span>
                            @endforeach
                        </div>
                        <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                            <span class="fw-bold text-dark">Total Payable:</span>
                            <span class="fw-bold fs-4 text-success">৳ {{ number_format($totalFare) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Passenger Billing Form -->
        <div class="glass-card">
            <h2 class="h5 fw-bold text-dark border-bottom pb-3 mb-3">
                <i class="fas fa-user-check text-success me-2"></i> Passenger & Billing Details
            </h2>

            @guest
            <div class="alert alert-info d-flex align-items-start gap-2 py-2 px-3 small mb-3" role="alert">
                <i class="fas fa-info-circle mt-1"></i>
                <div>
                    No account needed to book. We'll create one from these details so you
                    can track this booking — afterwards you can set a password to log in
                    later with your mobile or email.
                </div>
            </div>
            @endguest

            <form action="{{ url('/pay') }}" method="POST">
                @csrf
                <input type="hidden" name="amount" value="{{ $totalFare }}" />
                <input type="hidden" name="bus_id" value="{{ $bus->id }}" />
                @foreach($ticketlist as $index => $ticket)
                    <input type="hidden" name="ticketlist[{{ $index }}]" value="{{ $ticket }}" />
                @endforeach

                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="customer_name" class="form-label">Full Name</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="fas fa-user text-muted"></i></span>
                            <input type="text" name="customer_name" class="form-control" id="customer_name"
                                value="{{ Auth::check() ? Auth::user()->name : '' }}" placeholder="Passenger Full Name" required>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label for="customer_mobile" class="form-label">Mobile Number</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="fas fa-phone text-muted"></i></span>
                            <input type="tel" name="customer_mobile" class="form-control" id="customer_mobile"
                                value="{{ Auth::check() ? Auth::user()->mobile_no : '' }}" placeholder="01712345678" pattern="[0-9]{11}" required>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label for="customer_email" class="form-label">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="fas fa-envelope text-muted"></i></span>
                            <input type="email" name="customer_email" class="form-control" id="customer_email"
                                value="{{ Auth::check() ? Auth::user()->email : '' }}" placeholder="email@domain.com" required>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label for="address" class="form-label">Address</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="fas fa-home text-muted"></i></span>
                            <input type="text" class="form-control" id="address" name="address"
                                value="Dhaka, Bangladesh" placeholder="Your City/Address" required>
                        </div>
                    </div>

                    <!-- Payment Button (Thumb Zone Priority CTA) -->
                    <div class="col-12 mt-4">
                        <button type="submit" class="btn btn-primary-touch w-100 py-3 text-uppercase fw-bold">
                            <i class="fas fa-lock me-2"></i> Pay ৳ {{ number_format($totalFare) }} via SSLCommerz
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection