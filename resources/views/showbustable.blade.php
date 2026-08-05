@extends('layout')

@section('title', 'Available Buses - JatraPoth')

@section('styles')
<style>
    /* Community corner on each result card: a secondary, tappable strip that
       stays visually below the fare and the Select Seats CTA. */
    .gallery-corner {
        display: flex;
        align-items: center;
        gap: .6rem;
        padding: .5rem .7rem;
        min-height: var(--touch-target-min, 48px);
        background: var(--primary-light, #eef2ff);
        border: 1px solid rgba(79, 70, 229, .18);
        border-radius: var(--border-radius-md, 8px);
        text-decoration: none;
        color: inherit;
        transition: background .15s ease, border-color .15s ease;
    }

    .gallery-corner:hover,
    .gallery-corner:focus-visible {
        background: #e0e7ff;
        border-color: rgba(79, 70, 229, .4);
    }

    .gallery-corner:focus-visible {
        outline: 3px solid var(--primary, #4f46e5);
        outline-offset: 2px;
    }

    .gc-icon {
        flex: 0 0 auto;
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: #fff;
        color: var(--primary, #4f46e5);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: .9rem;
    }

    .gc-text { display: flex; flex-direction: column; min-width: 0; flex: 1 1 auto; }

    .gc-title {
        font-size: .84rem;
        font-weight: 600;
        color: var(--text-dark, #1e293b);
        line-height: 1.25;
    }

    .gc-sub {
        font-size: .74rem;
        color: var(--text-muted, #64748b);
        line-height: 1.3;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .gc-arrow { color: var(--text-muted, #64748b); font-size: .75rem; flex: 0 0 auto; }

    @media (prefers-reduced-motion: reduce) {
        .gallery-corner { transition: none; }
    }
</style>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-10">
        <!-- Page Header -->
        <div class="glass-card mb-4">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div>
                    <h1 class="h3 fw-bold mb-1">Available Buses</h1>
                    <p class="text-muted small mb-0">
                        <i class="fas fa-calendar-alt text-primary me-1"></i>
                        Showing trips for {{ request('date', date('Y-m-d')) }}
                        @if(request('starting_point'))
                            ({{ request('starting_point') }} <i class="fas fa-arrow-right mx-1"></i> {{ request('ending_point') }})
                        @endif
                    </p>
                </div>
                <a href="{{ url('/') }}" class="btn btn-outline-touch">
                    <i class="fas fa-search me-1"></i> Modify Search
                </a>
            </div>
        </div>

        @if(count($buses) == 0)
        <!-- Empty State Card -->
        <div class="glass-card text-center py-5">
            <div class="mb-3 text-muted" style="font-size: 3.5rem;">
                <i class="fas fa-bus-alt"></i>
            </div>
            <h2 class="h4 fw-bold mb-2">No Buses Found</h2>
            <p class="text-muted mb-4">We couldn't find any buses matching your selected route or date.</p>
            <a href="{{ url('/') }}" class="btn btn-primary-touch px-4 py-3">
                <i class="fas fa-arrow-left me-2"></i> Search Other Routes
            </a>
        </div>
        @else

        <!-- Mobile & Desktop Responsive Bus Cards List -->
        <div class="d-flex flex-column gap-3">
            @foreach ($buses as $key => $bus)
            @php
                $timeStr = strtolower($bus->departing_time);
                $isNight = false;
                if (str_contains($timeStr, 'pm')) {
                    $parts = explode(':', $timeStr);
                    $hour = (int) preg_replace('/[^0-9]/', '', $parts[0] ?? '12');
                    if ($hour >= 6 && $hour < 12) { $isNight = true; }
                } elseif (str_contains($timeStr, 'am')) {
                    $parts = explode(':', $timeStr);
                    $hour = (int) preg_replace('/[^0-9]/', '', $parts[0] ?? '12');
                    if ($hour == 12 || $hour < 6) { $isNight = true; }
                } else {
                    $parts = explode(':', $timeStr);
                    $hour = (int) ($parts[0] ?? 12);
                    if ($hour >= 18 || $hour < 6) { $isNight = true; }
                }
            @endphp

            <div class="glass-card p-3 p-md-4 mb-0 position-relative overflow-hidden" 
                 style="border-left: 5px solid {{ $isNight ? '#312e81' : '#f59e0b' }};">
                <div class="row align-items-center g-3">
                    <!-- Bus Operator & Ratings -->
                    <div class="col-md-4">
                        <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                            <h2 class="h5 fw-bold text-primary mb-0">{{ $bus->bus_name }}</h2>
                            <span class="badge bg-light text-dark border">{{ $bus->coach_type }}</span>
                            
                            <!-- Dynamic Day / Night Service Badge -->
                            @if($isNight)
                                <span class="badge" style="background: linear-gradient(135deg, #1e1b4b, #312e81); color: #a5b4fc; border: 1px solid #4338ca;">
                                    <i class="fas fa-moon me-1 text-info"></i> Night Service
                                </span>
                            @else
                                <span class="badge" style="background: linear-gradient(135deg, #fef3c7, #fde68a); color: #78350f; border: 1px solid #f59e0b;">
                                    <i class="fas fa-sun me-1 text-warning"></i> Day Service
                                </span>
                            @endif
                        </div>
                        <div class="small text-muted mb-2">Coach No: {{ $bus->coach_no }}</div>
                        
                        <!-- Star Rating System Component & Link -->
                        <div class="d-flex align-items-center gap-2">
                            @include('components.bus-rating', ['rating_data' => $bus->rating_data])
                            <button type="button" class="btn btn-sm btn-link text-decoration-none p-0 small text-primary ms-1" onclick="openBusReviewsModal({{ $bus->id }})">
                                Reviews <i class="fas fa-external-link-alt ms-1" style="font-size:0.75rem;"></i>
                            </button>
                        </div>

                        <!-- Passenger photos + conduct score: the community corner -->
                        <a href="{{ route('bus.gallery', $bus->id) }}" class="gallery-corner mt-2">
                            <span class="gc-icon">
                                <i class="fas fa-camera"></i>
                            </span>
                            <span class="gc-text">
                                <span class="gc-title">
                                    @if(($bus->photo_count ?? 0) > 0)
                                        {{ $bus->photo_count }} passenger {{ Str::plural('photo', $bus->photo_count) }}
                                    @else
                                        No photos yet
                                    @endif
                                </span>
                                <span class="gc-sub">
                                    @if(!empty($bus->behavior) && $bus->behavior['published'])
                                        <i class="fas fa-clipboard-check"></i>
                                        Conduct {{ number_format($bus->behavior['average'], 1) }}/5
                                        &middot; {{ $bus->behavior['band']['label'] }}
                                    @elseif(($bus->photo_count ?? 0) > 0)
                                        Tap to see what riders posted
                                    @else
                                        Be the first to share one
                                    @endif
                                </span>
                            </span>
                            <i class="fas fa-chevron-right gc-arrow"></i>
                        </a>
                    </div>

                    <!-- Journey Details (Route & Departure Time with Day/Night Theme) -->
                    <div class="col-6 col-md-3">
                        <div class="small text-muted mb-1">Departure Time</div>
                        <div class="p-2 rounded-3 text-center" 
                             style="background: {{ $isNight ? '#1e1b4b' : '#fffbeb' }}; border: 1px solid {{ $isNight ? '#3730a3' : '#fcd34d' }};">
                            <div class="fw-bold fs-5" style="color: {{ $isNight ? '#818cf8' : '#b45309' }};">
                                <i class="{{ $isNight ? 'fas fa-moon text-info' : 'fas fa-sun text-warning' }} me-1"></i> {{ $bus->departing_time }}
                            </div>
                        </div>
                        <div class="small text-muted mt-1">
                            {{ $bus->starting_point }} <i class="fas fa-arrow-right mx-1"></i> {{ $bus->ending_point }}
                        </div>
                    </div>

                    <!-- Fare & Available Seats -->
                    <div class="col-6 col-md-2 text-md-center">
                        <div class="small text-muted">Fare / Seat</div>
                        <div class="fw-bold fs-4 text-success">৳ {{ number_format($bus->fare) }}</div>
                        <div class="mt-1">
                            @if($bus->seats_available > 0)
                                <span class="badge bg-success">
                                    <i class="fas fa-chair me-1"></i> {{ $bus->seats_available }} Available
                                </span>
                            @else
                                <span class="badge bg-danger">Sold Out</span>
                            @endif
                        </div>
                    </div>

                    <!-- Action Button (Touch Target Priority: 48px+ Min Height) -->
                    <div class="col-12 col-md-3 text-md-end">
                        <a href="{{ route('seat_view', ['id' => $bus->id]) }}" 
                           class="btn btn-primary-touch w-100 py-3 fw-bold">
                            Select Seats <i class="fas fa-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @endif
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