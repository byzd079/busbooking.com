@extends('layout')

@section('title', 'Request Seat Swap - JatraPoth')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <!-- Header Card -->
        <div class="glass-card mb-4">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <h1 class="h3 fw-bold text-dark mb-1">
                        <i class="fas fa-exchange-alt text-primary me-2"></i> Request Seat Swap
                    </h1>
                    <p class="text-muted small mb-0">
                        Swap seats officially with another passenger on {{ $bus->bus_name }} ({{ $bus->date }})
                    </p>
                </div>
                <a href="{{ route('purchase_history') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-arrow-left me-1"></i> Back to Tickets
                </a>
            </div>
        </div>

        <!-- Request Form Card -->
        <div class="glass-card">
            @if($order->getSwapDeadline())
            <div class="alert alert-info py-2 small mb-4">
                <i class="fas fa-clock me-1"></i> Seat swapping is permitted until 1 hour after departure (Deadline: {{ $order->getSwapDeadline()->format('h:i A, d M Y') }}).
            </div>
            @endif

            <form action="{{ route('seat.swap.request') }}" method="POST">
                @csrf
                <input type="hidden" name="requester_order_id" value="{{ $order->id }}">

                <div class="row g-4">
                    <!-- My Seat Selection -->
                    <div class="col-md-6">
                        <div class="bg-light p-3 rounded-3 h-100">
                            <label for="requester_seat" class="form-label fw-bold">Select Your Seat to Swap Out:</label>
                            <select class="form-select" id="requester_seat" name="requester_seat" required>
                                @foreach($mySeats as $seat)
                                    <option value="{{ $seat }}">Seat {{ $seat }}</option>
                                @endforeach
                            </select>
                            <div class="form-text mt-2">Choose which of your booked seats you want to offer for swap.</div>
                        </div>
                    </div>

                    <!-- Target Seat Selection -->
                    <div class="col-md-6">
                        <div class="bg-light p-3 rounded-3 h-100">
                            <label for="target_seat_select" class="form-label fw-bold">Select Passenger Seat You Want:</label>
                            @if(count($availableTargetSeats) > 0)
                                <select class="form-select" id="target_seat_select" onchange="updateTargetFields()" required>
                                    <option value="">-- Choose Target Seat --</option>
                                    @foreach($availableTargetSeats as $target)
                                        <option value="{{ $target['seat_name'] }}" 
                                                data-order="{{ $target['order_id'] }}"
                                                data-passenger="{{ $target['passenger_name'] }}">
                                            Seat {{ $target['seat_name'] }} (Booked by {{ $target['passenger_name'] }})
                                        </option>
                                    @endforeach
                                </select>
                                <input type="hidden" name="target_order_id" id="target_order_id">
                                <input type="hidden" name="target_seat" id="target_seat">
                                <div class="form-text mt-2" id="target_info_text">Select a booked seat on this bus to send a swap request.</div>
                            @else
                                <div class="alert alert-info mb-0">
                                    <i class="fas fa-info-circle me-1"></i> No other passengers have booked seats on this bus trip yet.
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Submit CTA -->
                    @if(count($availableTargetSeats) > 0)
                    <div class="col-12 mt-4">
                        <button type="submit" class="btn btn-primary-touch w-100 py-3 text-uppercase fw-bold">
                            <i class="fas fa-paper-plane me-2"></i> Send Swap Request to Passenger
                        </button>
                    </div>
                    @endif
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function updateTargetFields() {
        const select = document.getElementById('target_seat_select');
        const selectedOption = select.options[select.selectedIndex];
        
        if (selectedOption && selectedOption.value) {
            document.getElementById('target_seat').value = selectedOption.value;
            document.getElementById('target_order_id').value = selectedOption.dataset.order;
            document.getElementById('target_info_text').innerText = 'Requesting swap for Seat ' + selectedOption.value + ' with ' + selectedOption.dataset.passenger;
        } else {
            document.getElementById('target_seat').value = '';
            document.getElementById('target_order_id').value = '';
            document.getElementById('target_info_text').innerText = 'Select a booked seat on this bus to send a swap request.';
        }
    }
</script>
@endsection
