@extends('layout')

@section('title', 'My Ticket History - JatraPoth')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-10">
        <!-- Page Header -->
        <div class="glass-card mb-4">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <h1 class="h3 fw-bold mb-1">Purchase History</h1>
                    <p class="text-muted small mb-0">View all your booked bus tickets and download e-tickets</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('seat.swap.list') }}" class="btn btn-outline-touch">
                        <i class="fas fa-exchange-alt me-1"></i> Seat Swaps
                    </a>
                    <a href="{{ route('search_bus') }}" class="btn btn-primary-touch">
                        <i class="fas fa-plus me-1"></i> Book Ticket
                    </a>
                </div>
            </div>
        </div>

        @if($order && count($order) > 0)
            <div class="d-flex flex-column gap-3">
                @foreach ($order as $item)
                @php
                    $bus = \App\Models\Bus::find($item->bus_id);
                    $ticketlist = json_decode($item->ticketlist, true);
                    $seats = is_array($ticketlist) ? $ticketlist : [];
                @endphp
                <div class="glass-card p-3 p-md-4 mb-0">
                    <div class="row align-items-center g-3">
                        <!-- Bus & Route Info -->
                        <div class="col-md-5">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <h2 class="h5 fw-bold text-primary mb-0">{{ $bus->bus_name ?? 'Express Coach' }}</h2>
                                @if($item->status == 'Processing' || $item->status == 'Successful')
                                    <span class="badge bg-success">Confirmed</span>
                                @elseif($item->status == 'Refunding')
                                    <span class="badge bg-warning text-dark">Refunding</span>
                                @elseif($item->status == 'Refunded')
                                    <span class="badge bg-secondary">Refunded</span>
                                @else
                                    <span class="badge bg-danger">{{ $item->status }}</span>
                                @endif
                            </div>
                            <div class="small text-muted mb-2">
                                Transaction ID: <span class="font-monospace text-dark">{{ $item->transaction_id }}</span>
                            </div>
                            <div class="fw-semibold text-dark">
                                {{ $bus->starting_point ?? 'Origin' }} <i class="fas fa-arrow-right mx-1 text-muted"></i> {{ $bus->ending_point ?? 'Destination' }}
                            </div>
                            <div class="small text-muted">
                                Date: {{ $bus->date ?? 'N/A' }} | Time: {{ $bus->departing_time ?? 'N/A' }}
                            </div>
                        </div>

                        <!-- Seats & Fare -->
                        <div class="col-md-4">
                            <div class="small text-muted mb-1">Booked Seats:</div>
                            <div class="d-flex flex-wrap gap-1 mb-2">
                                @foreach($seats as $seat)
                                    <span class="badge bg-primary fs-6">{{ $seat }}</span>
                                @endforeach
                            </div>
                            <div class="fw-bold text-success fs-5">
                                Total Paid: ৳ {{ number_format($item->amount) }}
                            </div>
                        </div>

                        <!-- Action Buttons (48px Touch Targets) -->
                        <div class="col-md-3 text-md-end">
                            <div class="d-flex flex-column gap-2">
                                @if($bus)
                                <form action="{{ route('showdownloadinfo') }}" method="GET" class="w-100">
                                    <input type="hidden" name="bus_id" value="{{ $bus->id }}">
                                    <input type="hidden" name="order_id" value="{{ $item->id }}">
                                    <button type="submit" class="btn btn-outline-touch w-100 py-2">
                                        <i class="fas fa-download me-1"></i> Download PDF
                                    </button>
                                </form>

                                <!-- Official Seat Swap Trigger -->
                                @if($item->status === 'Processing' || $item->status === 'Successful')
                                    <a href="{{ route('seat.swap.form', $item->id) }}" class="btn btn-outline-primary w-100 py-2 fw-semibold">
                                        <i class="fas fa-exchange-alt me-1"></i> Request Seat Swap
                                    </a>
                                @endif

                                <!-- Combined trip rating: seat stars + conduct dimensions in one form -->
                                <a href="{{ route('trip.rating.form', $item->id) }}"
                                    class="btn btn-warning w-100 py-2 fw-semibold">
                                    <i class="fas fa-star me-1"></i> Rate This Trip
                                </a>

                                <!-- Community gallery for this bus -->
                                <a href="{{ route('bus.gallery', $bus->id) }}"
                                    class="btn btn-outline-primary w-100 py-2 fw-semibold">
                                    <i class="fas fa-camera me-1"></i> Photos &amp; Reports
                                </a>
                                @endif

                                @if($item->status === 'Processing' || $item->status === 'Successful')
                                    <a href="{{ route('refund.policy', $item->id) }}" class="btn btn-sm btn-outline-danger w-100 py-2">
                                        <i class="fas fa-undo me-1"></i> Request Refund
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-center mt-4">
                {{ $order->links() }}
            </div>
        @else
            <!-- Empty State -->
            <div class="glass-card text-center py-5">
                <div class="mb-3 text-muted" style="font-size: 3.5rem;">
                    <i class="fas fa-ticket-alt"></i>
                </div>
                <h2 class="h4 fw-bold mb-2">No Ticket History Found</h2>
                <p class="text-muted mb-4">You haven't made any bus bookings yet.</p>
                <a href="{{ route('search_bus') }}" class="btn btn-primary-touch px-4 py-3">
                    <i class="fas fa-search me-2"></i> Book Your First Ticket
                </a>
            </div>
        @endif
    </div>
</div>
@endsection

