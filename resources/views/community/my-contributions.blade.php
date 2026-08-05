@extends('layout')

@section('title', 'My contributions')

@section('styles')
<style>
    .contrib-card {
        background: var(--card-bg);
        border: 1px solid var(--card-border);
        border-radius: var(--border-radius-lg);
        box-shadow: var(--shadow-md);
        padding: 1.25rem;
        margin-bottom: 1rem;
    }

    .stat-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
        gap: .75rem;
    }

    .stat-tile {
        background: #fff;
        border: 1px solid var(--card-border);
        border-radius: var(--border-radius-md);
        padding: .9rem;
        text-align: center;
    }

    .stat-tile .num {
        font-size: 1.6rem;
        font-weight: 700;
        line-height: 1.1;
        color: var(--primary);
        font-variant-numeric: tabular-nums;
    }

    .stat-tile .lbl {
        font-size: .78rem;
        color: var(--text-muted);
    }

    .badge-pill {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        padding: .35rem .8rem;
        border-radius: 999px;
        color: #fff;
        font-weight: 600;
        font-size: .85rem;
    }

    .mini-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
        gap: .75rem;
    }

    .mini-thumb {
        border-radius: var(--border-radius-md);
        overflow: hidden;
        border: 1px solid var(--card-border);
        background: #e2e8f0;
        display: block;
        text-decoration: none;
    }

    .mini-thumb img {
        width: 100%;
        aspect-ratio: 1;
        object-fit: cover;
        display: block;
    }

    .mini-meta {
        padding: .4rem .5rem;
        font-size: .72rem;
        color: var(--text-muted);
        background: #fff;
    }

    .score-line {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
        padding: .7rem 0;
        border-bottom: 1px solid rgba(226,232,240,.7);
    }

    .score-line:last-child { border-bottom: 0; }

    .unrated-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
        background: var(--primary-light);
        border-radius: var(--border-radius-md);
        padding: .75rem .9rem;
        margin-bottom: .5rem;
        flex-wrap: wrap;
    }
</style>
@endsection

@section('content')
<div class="contrib-card">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <h1 class="h4 fw-bold mb-1">My contributions</h1>
            <p class="text-muted small mb-0">Photos, replies and trip reports you have shared.</p>
        </div>
        <span class="badge-pill" style="background: {{ $badge['color'] }}">
            <i class="fas {{ $badge['icon'] }}"></i> {{ $badge['label'] }}
        </span>
    </div>

    <div class="stat-row">
        <div class="stat-tile">
            <div class="num">{{ $posts->total() }}</div>
            <div class="lbl">{{ Str::plural('Photo', $posts->total()) }} shared</div>
        </div>
        <div class="stat-tile">
            <div class="num">{{ $helpfulEarned }}</div>
            <div class="lbl">Marked helpful</div>
        </div>
        <div class="stat-tile">
            <div class="num">{{ $commentCount }}</div>
            <div class="lbl">{{ Str::plural('Reply', $commentCount) }} posted</div>
        </div>
        <div class="stat-tile">
            <div class="num">{{ $scores->total() }}</div>
            <div class="lbl">{{ Str::plural('Trip', $scores->total()) }} rated</div>
        </div>
    </div>
</div>

{{-- Trips still waiting for a rating -------------------------------- --}}
@if($unratedOrders->isNotEmpty())
<div class="contrib-card">
    <h2 class="h6 fw-bold mb-2">
        <i class="fas fa-bell text-warning me-1"></i>
        Rate a recent trip
    </h2>
    <p class="text-muted small mb-3">
        These bookings do not have a report yet. It takes about a minute.
    </p>

    @foreach($unratedOrders as $order)
    <div class="unrated-item">
        <div>
            <div class="fw-semibold small">Booking #{{ $order->id }}</div>
            <div class="text-muted" style="font-size:.78rem">
                <i class="far fa-calendar me-1"></i>{{ $order->created_at->format('j M Y') }}
                @if($order->ticketlist)
                    <span class="mx-1">&middot;</span>Seats {{ $order->ticketlist }}
                @endif
            </div>
        </div>
        <a href="{{ route('trip.rating.form', $order->id) }}" class="btn btn-sm btn-primary">
            <i class="fas fa-star me-1"></i> Rate this trip
        </a>
    </div>
    @endforeach
</div>
@endif

{{-- Photos ---------------------------------------------------------- --}}
<div class="contrib-card">
    <h2 class="h6 fw-bold mb-3">
        <i class="fas fa-images text-primary me-1"></i> My photos
    </h2>

    @if($posts->isEmpty())
        <div class="text-center py-4">
            <i class="fas fa-camera-retro text-muted mb-2" style="font-size:2.5rem; opacity:.35"></i>
            <p class="mb-1 fw-semibold">You have not shared any photos yet</p>
            <p class="text-muted small mb-3">
                A photo of the seats or the bus helps the next passenger know what to expect.
            </p>
            <a href="{{ route('search_bus') }}" class="btn btn-sm btn-primary">
                <i class="fas fa-search me-1"></i> Find a bus
            </a>
        </div>
    @else
        <div class="mini-grid">
            @foreach($posts as $post)
            <a href="{{ route('bus.gallery', $post->bus_id) }}" class="mini-thumb">
                <img src="{{ route('bus.post.image', $post->id) }}"
                     alt="{{ $post->alt_text }}"
                     loading="lazy">
                <div class="mini-meta">
                    <div class="fw-semibold text-truncate" style="color: var(--text-dark)">
                        {{ $post->buslist->bus_name ?? 'Bus' }}
                    </div>
                    <div>
                        <i class="fas fa-thumbs-up"></i> {{ $post->helpful_count }}
                        <span class="mx-1">&middot;</span>
                        <i class="far fa-comment"></i> {{ $post->comments_count }}
                    </div>
                </div>
            </a>
            @endforeach
        </div>

        <div class="mt-3 d-flex justify-content-center">
            {{ $posts->links() }}
        </div>
    @endif
</div>

{{-- Trip reports ---------------------------------------------------- --}}
<div class="contrib-card">
    <h2 class="h6 fw-bold mb-3">
        <i class="fas fa-clipboard-check text-primary me-1"></i> My trip reports
    </h2>

    @if($scores->isEmpty())
        <p class="text-muted small mb-0">
            You have not rated a trip yet. After a confirmed booking, a rating link appears here.
        </p>
    @else
        @foreach($scores as $score)
        @php $band = \App\Models\BusBehaviorScore::band($score->overall_score); @endphp
        <div class="score-line">
            <div>
                <div class="fw-semibold small">{{ $score->buslist->bus_name ?? 'Bus' }}</div>
                <div class="text-muted" style="font-size:.78rem">
                    <i class="far fa-calendar me-1"></i>
                    {{ $score->trip_date->format('j M Y') }}
                    <span class="mx-1">&middot;</span>
                    Coach {{ $score->buslist->coach_no ?? '—' }}
                </div>
            </div>
            <div class="text-end">
                <span class="fw-bold" style="color: {{ $band['color'] }}">
                    {{ $score->overall_score }}/5
                </span>
                <div class="text-muted" style="font-size:.72rem">{{ $band['label'] }}</div>
            </div>
        </div>
        @endforeach

        <div class="mt-3 d-flex justify-content-center">
            {{ $scores->links() }}
        </div>
    @endif
</div>
@endsection
