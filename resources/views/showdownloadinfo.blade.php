@extends('layout')

@section('title', 'Ticket Confirmed - JatraPoth')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <!-- Success Confirmation Header -->
        <div class="glass-card text-center mb-4">
            <div class="display-4 text-success mb-2">
                <i class="fas fa-check-circle"></i>
            </div>
            <h1 class="h3 fw-bold text-dark mb-1">Booking Confirmed!</h1>
            <p class="text-muted mb-0">Your bus ticket has been successfully booked.</p>
        </div>

        <!-- Ticket Info Card -->
        <div class="glass-card mb-4">
            <h2 class="h5 fw-bold text-dark border-bottom pb-3 mb-3">
                <i class="fas fa-ticket-alt text-primary me-2"></i> Ticket Details
            </h2>

            @php 
                $ticketlist = is_string($order->ticketlist) ? json_decode($order->ticketlist, true) : $order->ticketlist;
                $seats = is_array($ticketlist) ? $ticketlist : [];
            @endphp

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <div class="bg-light p-3 rounded-3 h-100">
                        <div class="small text-muted mb-1">Passenger Name</div>
                        <div class="fw-bold text-dark fs-5">{{ $order->name }}</div>
                        <div class="small text-muted mt-2">Mobile: {{ $order->phone }}</div>
                        <div class="small text-muted">Email: {{ $order->email }}</div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="bg-light p-3 rounded-3 h-100">
                        <div class="small text-muted mb-1">Transaction ID</div>
                        <div class="fw-bold text-primary font-monospace">{{ $order->transaction_id }}</div>
                        <div class="small text-muted mt-2">Payment Method: {{ $card_issuer ?? 'SSLCommerz' }}</div>
                        <div class="fw-bold text-success mt-1">Status: Paid</div>
                    </div>
                </div>
            </div>

            <div class="bg-light p-3 rounded-3 mb-3">
                <div class="row align-items-center g-2">
                    <div class="col-md-6">
                        <div class="fw-bold text-primary fs-5">{{ $bus->bus_name }}</div>
                        <div class="small text-muted">Coach: {{ $bus->coach_no }} ({{ $bus->coach_type }})</div>
                        <div class="fw-semibold text-dark mt-1">
                            {{ $bus->starting_point }} <i class="fas fa-arrow-right mx-1 text-muted"></i> {{ $bus->ending_point }}
                        </div>
                        <div class="small text-muted">Date: {{ $bus->date }} | Time: {{ $bus->departing_time }}</div>
                    </div>

                    <div class="col-md-6 text-md-end border-top border-md-0 pt-2 pt-md-0">
                        <div class="small text-muted mb-1">Booked Seats</div>
                        <div class="d-flex flex-wrap gap-1 justify-md-end mb-2">
                            @foreach($seats as $seat)
                                <span class="badge bg-primary fs-6">{{ $seat }}</span>
                            @endforeach
                        </div>
                        <div class="fw-bold text-success fs-4">৳ {{ number_format($order->amount) }}</div>
                    </div>
                </div>
            </div>

            <!-- Download Button (Thumb Zone Priority) -->
            <form action="{{ route('downloadTicket') }}" method="GET" class="w-100">
                @csrf
                <input type="hidden" name="order_id" value="{{ $order->id }}">
                <input type="hidden" name="token" value="{{ $order->downloadToken() }}">
                <button type="submit" class="btn btn-primary-touch w-100 py-3 fw-bold text-uppercase">
                    <i class="fas fa-file-pdf me-2"></i> Download PDF E-Ticket
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
