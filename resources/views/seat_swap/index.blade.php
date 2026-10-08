@extends('layout')

@section('title', 'My Seat Swap Requests - JatraPoth')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-10">
        <!-- Header -->
        <div class="glass-card mb-4">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <h1 class="h3 fw-bold text-dark mb-1">
                        <i class="fas fa-exchange-alt text-primary me-2"></i> Seat Swapping Center
                    </h1>
                    <p class="text-muted small mb-0">Manage incoming and outgoing seat swap requests with fellow passengers</p>
                </div>
                <a href="{{ route('purchase_history') }}" class="btn btn-primary-touch">
                    <i class="fas fa-ticket-alt me-1"></i> My Tickets
                </a>
            </div>
        </div>

        <!-- Incoming Swap Requests (Sent to Me) -->
        <div class="glass-card mb-4">
            <h2 class="h5 fw-bold text-dark border-bottom pb-3 mb-3">
                <i class="fas fa-inbox text-warning me-2"></i> Incoming Swap Requests (Passengers Want to Swap with You)
            </h2>

            @if(count($incomingSwaps) > 0)
                <div class="d-flex flex-column gap-3">
                    @foreach($incomingSwaps as $swap)
                    <div class="bg-light p-3 rounded-3 border">
                        <div class="row align-items-center g-3">
                            <div class="col-md-5">
                                <div class="fw-bold text-primary mb-1">
                                    {{ $swap->requester->name ?? 'Passenger' }} wants to swap seats
                                </div>
                                <div class="small text-muted">
                                    Bus: <strong>{{ $swap->bus->bus_name ?? 'Coach' }}</strong> ({{ $swap->bus->date ?? '' }})
                                </div>
                                <div class="small text-muted">
                                    Route: {{ $swap->bus->starting_point ?? '' }} <i class="fas fa-arrow-right mx-1"></i> {{ $swap->bus->ending_point ?? '' }}
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="text-center p-2 bg-white rounded border">
                                        <div class="small text-muted">They Offer</div>
                                        <span class="badge bg-primary fs-6">Seat {{ $swap->requester_seat }}</span>
                                    </div>
                                    <div class="fs-4 text-muted"><i class="fas fa-exchange-alt"></i></div>
                                    <div class="text-center p-2 bg-white rounded border">
                                        <div class="small text-muted">For Your</div>
                                        <span class="badge bg-success fs-6">Seat {{ $swap->target_seat }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-3 text-md-end">
                                @if($swap->status === 'Pending')
                                    @if($swap->requesterOrder && $swap->requesterOrder->canSwapSeats())
                                        <div class="d-flex gap-2">
                                            <form action="{{ route('seat.swap.accept', $swap->id) }}" method="POST" class="flex-grow-1">
                                                @csrf
                                                <button type="submit" class="btn btn-success btn-sm w-100 py-2 fw-bold">
                                                    <i class="fas fa-check me-1"></i> Accept
                                                </button>
                                            </form>
                                            <form action="{{ route('seat.swap.decline', $swap->id) }}" method="POST" class="flex-grow-1">
                                                @csrf
                                                <button type="submit" class="btn btn-outline-danger btn-sm w-100 py-2">
                                                    <i class="fas fa-times me-1"></i> Decline
                                                </button>
                                            </form>
                                        </div>
                                    @else
                                        <span class="badge bg-secondary fs-6">Expired</span>
                                    @endif
                                @else
                                    <span class="badge {{ $swap->status === 'Accepted' ? 'bg-success' : 'bg-secondary' }} fs-6">
                                        {{ $swap->status }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-4 text-muted">
                    <i class="fas fa-envelope-open fa-2x mb-2 opacity-50"></i>
                    <p class="mb-0">No incoming seat swap requests right now.</p>
                </div>
            @endif
        </div>

        <!-- Outgoing Swap Requests (Sent by Me) -->
        <div class="glass-card">
            <h2 class="h5 fw-bold text-dark border-bottom pb-3 mb-3">
                <i class="fas fa-paper-plane text-info me-2"></i> Outgoing Swap Requests (Sent by You)
            </h2>

            @if(count($outgoingSwaps) > 0)
                <div class="d-flex flex-column gap-3">
                    @foreach($outgoingSwaps as $swap)
                    <div class="bg-light p-3 rounded-3 border">
                        <div class="row align-items-center g-3">
                            <div class="col-md-5">
                                <div class="fw-bold text-dark mb-1">
                                    Request to Passenger ({{ $swap->targetOrder->name ?? 'Passenger' }})
                                </div>
                                <div class="small text-muted">
                                    Bus: <strong>{{ $swap->bus->bus_name ?? 'Coach' }}</strong> ({{ $swap->bus->date ?? '' }})
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="text-center p-2 bg-white rounded border">
                                        <div class="small text-muted">Your Seat</div>
                                        <span class="badge bg-primary fs-6">Seat {{ $swap->requester_seat }}</span>
                                    </div>
                                    <div class="fs-4 text-muted"><i class="fas fa-arrow-right"></i></div>
                                    <div class="text-center p-2 bg-white rounded border">
                                        <div class="small text-muted">Target Seat</div>
                                        <span class="badge bg-warning text-dark fs-6">Seat {{ $swap->target_seat }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-3 text-md-end">
                                @if($swap->status === 'Pending')
                                    <form action="{{ route('seat.swap.cancel', $swap->id) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="btn btn-outline-secondary btn-sm w-100 py-2">
                                            <i class="fas fa-ban me-1"></i> Cancel Request
                                        </button>
                                    </form>
                                @else
                                    <span class="badge {{ $swap->status === 'Accepted' ? 'bg-success' : 'bg-secondary' }} fs-6">
                                        {{ $swap->status }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-4 text-muted">
                    <i class="fas fa-paper-plane fa-2x mb-2 opacity-50"></i>
                    <p class="mb-0">You haven't sent any seat swap requests yet.</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
