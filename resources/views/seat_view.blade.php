@extends('layout')

@section('title', 'Select Seats - ' . $bus->bus_name)

@section('styles')
<style>
    .bus-container-card {
        background: #ffffff;
        border-radius: 20px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1);
        padding: 1.5rem;
    }

    .bus-layout-outer {
        background: #f8fafc;
        border: 2px dashed #cbd5e1;
        border-radius: 16px;
        padding: 1.25rem;
        max-width: 440px;
        margin: 0 auto;
    }

    /* Bus Front / Driver Area */
    .bus-header-front {
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: #e2e8f0;
        padding: 0.75rem 1rem;
        border-radius: 12px;
        margin-bottom: 1rem;
        font-weight: 600;
        color: #475569;
    }

    /* Sun Guidance Column Headers */
    .sun-column-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.5rem 0.75rem;
        border-radius: 8px;
        margin-bottom: 1rem;
        font-size: 0.78rem;
        font-weight: 700;
    }

    /* Seat Grid Rows & Items */
    .seat-grid-row {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        margin-bottom: 10px;
    }

    .row-letter {
        width: 24px;
        font-weight: 700;
        color: #64748b;
        text-align: center;
        font-size: 0.9rem;
    }

    .aisle-gap {
        width: 28px;
    }

    .seat-item {
        width: 52px;
        height: 52px;
        min-width: 52px;
        min-height: 52px;
        border-radius: 10px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        font-size: 0.85rem;
        font-weight: 700;
        cursor: pointer;
        user-select: none;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        border: 2px solid transparent;
        position: relative;
    }

    .seat-item:active {
        transform: scale(0.92);
    }

    /* Seat States */
    .seat-item.available {
        background: #ffffff;
        border-color: #10b981;
        color: #065f46;
    }

    .seat-item.available:hover {
        background: #ecfdf5;
        border-color: #059669;
    }

    .seat-item.selected {
        background: var(--primary) !important;
        border-color: var(--primary-hover) !important;
        color: #ffffff !important;
        box-shadow: 0 4px 12px rgba(37, 99, 235, 0.4);
    }

    .seat-item.booked {
        background: #f1f5f9;
        border-color: #cbd5e1;
        color: #94a3b8;
        cursor: not-allowed;
    }

    .seat-item.held {
        background: #fef3c7;
        border-color: #f59e0b;
        color: #b45309;
        cursor: not-allowed;
    }

    .seat-item.broken {
        background: #fee2e2;
        border-color: #fca5a5;
        color: #991b1b;
        cursor: not-allowed;
    }

    .seat-checkbox {
        display: none;
    }

    /* Legend Box */
    .legend-box {
        display: flex;
        justify-content: space-around;
        align-items: center;
        background: #ffffff;
        padding: 0.75rem;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        margin-top: 1.25rem;
        font-size: 0.85rem;
        font-weight: 600;
    }

    .legend-indicator {
        width: 16px;
        height: 16px;
        border-radius: 4px;
        display: inline-block;
        margin-right: 4px;
    }

    /* Sticky Bottom Mobile Bar */
    .mobile-thumb-bar {
        position: fixed;
        bottom: 68px;
        left: 0;
        right: 0;
        background: rgba(15, 23, 42, 0.95);
        backdrop-filter: blur(10px);
        color: white;
        padding: 0.85rem 1rem;
        border-top: 1px solid rgba(255, 255, 255, 0.15);
        z-index: 1035;
    }
</style>
@endsection

@section('content')

@php
    $timeStr = strtolower($bus->departing_time);
    $isMorning = false;
    $isAfternoon = false;
    $isNight = false;

    if (str_contains($timeStr, 'pm')) {
        $hour = (int) preg_replace('/[^0-9]/', '', explode(':', $timeStr)[0] ?? '12');
        if ($hour >= 1 && $hour <= 5) {
            $isAfternoon = true;
        } elseif ($hour >= 6 && $hour < 12) {
            $isNight = true;
        } else {
            $isAfternoon = true;
        }
    } elseif (str_contains($timeStr, 'am')) {
        $hour = (int) preg_replace('/[^0-9]/', '', explode(':', $timeStr)[0] ?? '12');
        if ($hour >= 6 && $hour < 12) {
            $isMorning = true;
        } else {
            $isNight = true;
        }
    } else {
        $hour = (int) (explode(':', $timeStr)[0] ?? 12);
        if ($hour >= 6 && $hour < 12) { $isMorning = true; }
        elseif ($hour >= 12 && $hour < 18) { $isAfternoon = true; }
        else { $isNight = true; }
    }

    $start = strtolower($bus->starting_point ?? '');
    $end = strtolower($bus->ending_point ?? '');
    $isSouthbound = (str_contains($start, 'dhaka') && (str_contains($end, 'chattogram') || str_contains($end, 'cox') || str_contains($end, 'cumilla')));

    // Sun Side Calculation
    $leftIsSun = false;
    $rightIsSun = false;

    if ($isMorning) {
        if ($isSouthbound) { $leftIsSun = true; } else { $rightIsSun = true; }
    } elseif ($isAfternoon) {
        if ($isSouthbound) { $rightIsSun = true; } else { $leftIsSun = true; }
    }
@endphp

<!-- Main Booking Form wrapping seat selection -->
<form action="{{ route('payment_details') }}" method="GET" id="booking-form">
    <input type="hidden" name="id" value="{{ $bus->id }}">

    <!-- Header Info Card -->
    <div class="bus-container-card mb-4">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                    <h1 class="h3 fw-bold text-primary mb-0">{{ $bus->bus_name }}</h1>
                    <span class="badge bg-light text-dark border">{{ $bus->coach_type }}</span>
                    <span class="badge bg-secondary">Coach #{{ $bus->coach_no }}</span>
                    
                    <!-- Special Solar Facility Badge -->
                    <span class="badge" style="background: linear-gradient(135deg, #3b82f6, #1d4ed8); color: white;">
                        <i class="fas fa-shield-alt me-1"></i> UV Tinted Anti-Glare Windows
                    </span>
                </div>
                <div class="text-muted small">
                    <i class="fas fa-route text-primary me-1"></i>
                    <strong>{{ $bus->starting_point }}</strong> &rarr; <strong>{{ $bus->ending_point }}</strong>
                    &nbsp;|&nbsp; Date: <strong>{{ $bus->date }}</strong> &nbsp;|&nbsp; Departure: <strong>{{ $bus->departing_time }}</strong>
                </div>
            </div>

            <!-- Seat Rating Trigger Button -->
            <button type="button" class="btn btn-outline-touch btn-sm" onclick="openBusReviewsModal({{ $bus->id }})">
                <i class="fas fa-star text-warning me-1"></i> View Seat Ratings & Reviews
            </button>
        </div>

        <!-- Sun Position Guidance Banner -->
        <div class="mt-3 p-3 rounded-3" style="background: #fffbeb; border: 1px solid #fcd34d;">
            <div class="d-flex align-items-center gap-3">
                <div class="fs-3 text-warning"><i class="fas fa-sun"></i></div>
                <div>
                    <div class="fw-bold text-dark mb-1">
                        ☀️ Live Sun Position & Seat Shade Guidance
                    </div>
                    <div class="small text-muted">
                        @if($isNight)
                            🌙 <strong>Night Journey:</strong> No direct sun glare during this trip.
                        @elseif($leftIsSun)
                            ☀️ <strong>Direct Sun Side:</strong> <span class="badge bg-warning text-dark">Left Window (Seats A/B)</span> will receive maximum sunlight during travel. <br>
                            🌤️ <strong>Shaded Comfort Side:</strong> <span class="badge bg-success text-white">Right Window (Seats C/D)</span> stays shaded for maximum comfort!
                        @elseif($rightIsSun)
                            ☀️ <strong>Direct Sun Side:</strong> <span class="badge bg-warning text-dark">Right Window (Seats C/D)</span> will receive maximum sunlight during travel. <br>
                            🌤️ <strong>Shaded Comfort Side:</strong> <span class="badge bg-success text-white">Left Window (Seats A/B)</span> stays shaded for maximum comfort!
                        @else
                            🌤️ Overcast or Balanced Sunlight along this route.
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Seat Map View (Left Column) -->
        <div class="col-lg-7">
            <div class="bus-container-card text-center">
                <h2 class="h5 fw-bold mb-3 text-dark">Select Your Seat</h2>

                <div class="bus-layout-outer">
                    <!-- Driver Area -->
                    <div class="bus-header-front">
                        <span><i class="fas fa-door-open me-1"></i> Entrance</span>
                        <span><i class="fas fa-steering-wheel me-1"></i> Driver</span>
                    </div>

                    <!-- Column Sun Position Indicators -->
                    <div class="sun-column-header bg-white border">
                        <div class="px-2 py-1 rounded" style="background: {{ $leftIsSun ? '#fef3c7' : '#ecfdf5' }}; color: {{ $leftIsSun ? '#92400e' : '#065f46' }}; font-weight: bold;">
                            @if($leftIsSun)
                                ☀️ Seats A/B: Sun Side
                            @else
                                🌤️ Seats A/B: Shaded Side
                            @endif
                        </div>
                        <div class="px-2 py-1 rounded" style="background: {{ $rightIsSun ? '#fef3c7' : '#ecfdf5' }}; color: {{ $rightIsSun ? '#92400e' : '#065f46' }}; font-weight: bold;">
                            @if($rightIsSun)
                                ☀️ Seats C/D: Sun Side
                            @else
                                🌤️ Seats C/D: Shaded Side
                            @endif
                        </div>
                    </div>

                    <!-- Seat Matrix -->
                    @php
                        $view = $bus->view ?? '';
                        $totalSeats = $bus->total_seats ?? strlen($view);
                        $rows = ceil($totalSeats / 4);
                        $letters = range('A', 'Z');
                    @endphp

                    @for ($r = 0; $r < $rows; $r++)
                        @php
                            $rowLetter = $letters[$r] ?? ('R' . ($r + 1));
                        @endphp
                        <div class="seat-grid-row">
                            <span class="row-letter">{{ $rowLetter }}</span>

                            @for ($c = 1; $c <= 4; $c++)
                                @php
                                    $index = ($r * 4) + ($c - 1);
                                    $seatName = $rowLetter . $c;
                                    $statusChar = isset($view[$index]) ? $view[$index] : '0';
                                    
                                    $isBooked = ($statusChar === '1');
                                    $isBroken = ($statusChar === '2');
                                    $isHeld = in_array($seatName, $heldSeats ?? [], true);
                                    $isAvailable = ($statusChar === '0' && !$isHeld);
                                @endphp

                                @if ($c == 3)
                                    <div class="aisle-gap"></div>
                                @endif

                                @if ($isBooked)
                                    <div class="seat-item booked" title="Seat {{ $seatName }} - Booked">
                                        <span>{{ $seatName }}</span>
                                    </div>
                                @elseif ($isHeld)
                                    <div class="seat-item held" title="Seat {{ $seatName }} - Temporarily On Hold (Payment in progress)">
                                        <span>{{ $seatName }}</span>
                                    </div>
                                @elseif ($isBroken)
                                    <div class="seat-item broken" title="Seat {{ $seatName }} - Out of Service">
                                        <span>{{ $seatName }}</span>
                                    </div>
                                @elseif ($isAvailable)
                                    <div class="seat-item available" data-seat="{{ $seatName }}" title="Seat {{ $seatName }} - Available">
                                        <input type="checkbox" name="{{ $seatName }}" value="1" class="seat-checkbox">
                                        <span>{{ $seatName }}</span>
                                    </div>
                                @endif
                            @endfor
                        </div>
                    @endfor
                </div>

                <!-- Legend Box -->
                <div class="legend-box flex-wrap gap-2">
                    <div><span class="legend-indicator" style="background:#ffffff; border:2px solid #10b981;"></span> Available</div>
                    <div><span class="legend-indicator" style="background:var(--primary);"></span> Selected</div>
                    <div><span class="legend-indicator" style="background:#fef3c7; border:2px solid #f59e0b;"></span> On Hold</div>
                    <div><span class="legend-indicator" style="background:#f1f5f9; border:1px solid #cbd5e1;"></span> Booked</div>
                </div>
            </div>
        </div>

        <!-- Booking Summary Card (Desktop) -->
        <div class="col-lg-5">
            <div class="bus-container-card sticky-top" style="top: 90px; z-index: 10;">
                <h3 class="h5 fw-bold border-bottom pb-3 mb-3">
                    <i class="fas fa-receipt text-primary me-2"></i> Booking Summary
                </h3>

                <div class="mb-3">
                    <label class="text-muted small">Selected Seats:</label>
                    <div id="selected-seats-badge-container" class="d-flex flex-wrap gap-1 mt-1">
                        <span class="text-muted small italic">No seats selected yet</span>
                    </div>
                </div>

                <div class="bg-light p-3 rounded-3 mb-3">
                    <div class="d-flex justify-content-between mb-2 small text-muted">
                        <span>Ticket Price (৳ {{ number_format($bus->fare) }} x <span id="seat-qty-text">0</span>)</span>
                        <span id="subtotal-text">৳ 0</span>
                    </div>
                    <hr class="my-2">
                    <div class="d-flex justify-content-between fw-bold text-dark fs-5">
                        <span>Total Payable:</span>
                        <span class="text-success" id="total-price-text">৳ 0</span>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary-touch w-100 py-3 fw-bold" id="proceed-submit-btn" disabled>
                    <i class="fas fa-credit-card me-2"></i> Proceed to Checkout
                </button>
            </div>
        </div>
    </div>
</form>

<!-- Mobile Thumb-Zone Bar (Visible on Mobile <769px) -->
<div class="mobile-thumb-bar d-md-none">
    <div class="d-flex align-items-center justify-content-between">
        <div>
            <div class="small opacity-75">Total (<span id="mobile-qty">0</span> seats)</div>
            <div class="fw-bold fs-5 text-warning" id="mobile-total">৳ 0</div>
        </div>
        <button type="button" class="btn btn-warning px-4 py-2 fw-bold text-dark" id="mobile-proceed-btn" onclick="document.getElementById('booking-form').submit()" disabled>
            Checkout <i class="fas fa-arrow-right ms-1"></i>
        </button>
    </div>
</div>

<!-- Seat Reviews Modal -->
<div class="modal fade" id="busReviewsModal" tabindex="-1" aria-labelledby="busReviewsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="busReviewsModalLabel">
                    <i class="fas fa-star text-warning me-2"></i> Seat Ratings & Passenger Reviews
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="busReviewsModalBody">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading reviews...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const seatPrice = parseFloat({{ $bus->fare ?? 0 }});
        let selectedSeats = [];

        document.querySelectorAll('.seat-item.available').forEach(seat => {
            seat.addEventListener('click', function(e) {
                const checkbox = this.querySelector('.seat-checkbox');
                const seatName = this.dataset.seat;

                if (checkbox.checked) {
                    checkbox.checked = false;
                    this.classList.remove('selected');
                    selectedSeats = selectedSeats.filter(s => s !== seatName);
                } else {
                    checkbox.checked = true;
                    this.classList.add('selected');
                    selectedSeats.push(seatName);
                }

                updateSummary();
            });
        });

        function updateSummary() {
            const count = selectedSeats.length;
            const total = count * seatPrice;

            const badgeContainer = document.getElementById('selected-seats-badge-container');
            if (count === 0) {
                badgeContainer.innerHTML = '<span class="text-muted small">No seats selected yet</span>';
            } else {
                badgeContainer.innerHTML = selectedSeats.map(s => `<span class="badge bg-primary fs-6">${s}</span>`).join(' ');
            }

            document.getElementById('seat-qty-text').innerText = count;
            document.getElementById('subtotal-text').innerText = '৳ ' + total.toLocaleString();
            document.getElementById('total-price-text').innerText = '৳ ' + total.toLocaleString();

            document.getElementById('mobile-qty').innerText = count;
            document.getElementById('mobile-total').innerText = '৳ ' + total.toLocaleString();

            const proceedBtn = document.getElementById('proceed-submit-btn');
            const mobileProceedBtn = document.getElementById('mobile-proceed-btn');

            if (count > 0) {
                proceedBtn.removeAttribute('disabled');
                mobileProceedBtn.removeAttribute('disabled');
            } else {
                proceedBtn.setAttribute('disabled', 'disabled');
                mobileProceedBtn.setAttribute('disabled', 'disabled');
            }
        }
    });

    function openBusReviewsModal(busId) {
        const modal = new bootstrap.Modal(document.getElementById('busReviewsModal'));
        const modalBody = document.getElementById('busReviewsModalBody');
        modal.show();

        fetch(`/bus-reviews?bus_id=${busId}`)
            .then(res => res.json())
            .then(data => {
                if (data.success && data.reviews && data.reviews.length > 0) {
                    let html = '<div class="list-group list-group-flush">';
                    data.reviews.forEach(r => {
                        let stars = '';
                        for (let i = 1; i <= 5; i++) {
                            stars += `<i class="fas fa-star ${i <= r.rating ? 'text-warning' : 'text-muted'}"></i>`;
                        }
                        html += `
                            <div class="list-group-item px-0">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <div class="fw-bold">${r.user ? r.user.name : 'Passenger'} <span class="badge bg-light text-dark border ms-1">Seat ${r.seat_name}</span></div>
                                    <div class="small">${stars} (${r.rating}/5)</div>
                                </div>
                                <p class="mb-1 text-muted small">${r.comment ? r.comment : 'No written comment'}</p>
                                <small class="text-muted opacity-75">${new Date(r.created_at).toLocaleDateString()}</small>
                            </div>
                        `;
                    });
                    html += '</div>';
                    modalBody.innerHTML = html;
                } else {
                    modalBody.innerHTML = '<div class="alert alert-info text-center mb-0"><i class="fas fa-info-circle me-1"></i> No passenger reviews submitted for this bus yet.</div>';
                }
            })
            .catch(err => {
                modalBody.innerHTML = '<div class="alert alert-danger text-center mb-0">Error loading reviews.</div>';
            });
    }
</script>
@endsection
