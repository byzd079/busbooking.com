@extends('layout')

@section('title', 'Rate your trip — ' . $buslist->bus_name)

@section('styles')
<style>
    .rate-card {
        background: var(--card-bg);
        border: 1px solid var(--card-border);
        border-radius: var(--border-radius-lg);
        box-shadow: var(--shadow-lg);
        padding: 1.5rem;
        margin-bottom: 1rem;
    }

    .trip-summary {
        background: var(--primary-light);
        border-radius: var(--border-radius-md);
        padding: 1rem;
        margin-bottom: 1.25rem;
    }

    .dim-block {
        padding: 1rem 0;
        border-bottom: 1px solid rgba(226, 232, 240, .7);
    }

    .dim-block:last-of-type { border-bottom: 0; }

    .dim-head {
        display: flex;
        align-items: center;
        gap: .6rem;
        margin-bottom: .15rem;
    }

    .dim-head i {
        color: var(--primary);
        width: 20px;
        text-align: center;
    }

    .dim-name {
        font-weight: 600;
        font-size: 1rem;
        color: var(--text-dark);
    }

    .dim-help {
        font-size: .82rem;
        color: var(--text-muted);
        margin-bottom: .6rem;
    }

    /* Radio-backed stars: real inputs keep it keyboard- and screen-reader-
       navigable; the visual star is the label. */
    .star-row {
        display: flex;
        flex-direction: row-reverse;
        justify-content: flex-end;
        gap: .2rem;
    }

    .star-row input {
        position: absolute;
        opacity: 0;
        width: 1px;
        height: 1px;
    }

    .star-row label {
        font-size: 2rem;
        line-height: 1;
        color: #cbd5e1;
        cursor: pointer;
        padding: .1rem .15rem;
        min-width: var(--touch-target-min);
        min-height: var(--touch-target-min);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: color .12s ease, transform .12s ease;
    }

    /* Sibling selectors light up the chosen star and everything before it. */
    .star-row input:checked ~ label,
    .star-row label:hover,
    .star-row label:hover ~ label {
        color: var(--accent);
    }

    .star-row input:focus-visible + label {
        outline: 3px solid var(--primary);
        outline-offset: 2px;
        border-radius: var(--border-radius-sm);
    }

    .star-row label:active { transform: scale(.92); }

    .rating-word {
        font-size: .85rem;
        font-weight: 600;
        color: var(--text-muted);
        min-height: 1.2rem;
        margin-top: .2rem;
    }

    .seat-chip-row {
        display: flex;
        flex-wrap: wrap;
        gap: .75rem;
    }

    .seat-block {
        border: 1px solid var(--card-border);
        border-radius: var(--border-radius-md);
        padding: .6rem .8rem;
        background: #fff;
        min-width: 150px;
    }

    .seat-name {
        font-weight: 700;
        font-size: .9rem;
        color: var(--primary);
        margin-bottom: .2rem;
    }

    .seat-block .star-row label { font-size: 1.35rem; min-width: 36px; min-height: 36px; }

    .sticky-submit {
        position: sticky;
        bottom: 76px;
        background: var(--card-bg);
        border: 1px solid var(--card-border);
        border-radius: var(--border-radius-lg);
        box-shadow: var(--shadow-lg);
        padding: 1rem;
        z-index: 10;
    }

    @media (min-width: 769px) {
        .sticky-submit { bottom: 1rem; }
    }

    @media (prefers-reduced-motion: reduce) {
        .star-row label { transition: none; }
    }
</style>
@endsection

@section('content')
<div class="mb-3">
    <a href="{{ route('purchase_history') }}" class="btn btn-sm btn-outline-light">
        <i class="fas fa-arrow-left me-1"></i> Back to my tickets
    </a>
</div>

<form method="POST" action="{{ route('trip.rating.store') }}" id="rateForm">
    @csrf
    <input type="hidden" name="order_id" value="{{ $order->id }}">
    <input type="hidden" name="trip_date" value="{{ $tripDate }}">

    <div class="rate-card">
        <h1 class="h4 fw-bold mb-1">How was your trip?</h1>
        <p class="text-muted mb-3">
            Your answers help other passengers pick a bus, and give operators something concrete to fix.
        </p>

        <div class="trip-summary">
            <div class="fw-bold">{{ $buslist->bus_name }}</div>
            <div class="small text-muted">
                <i class="fas fa-route me-1"></i>
                {{ $buslist->starting_point }} &rarr; {{ $buslist->ending_point }}
                <span class="mx-1">&middot;</span>
                <i class="far fa-calendar me-1"></i>
                {{ \Carbon\Carbon::parse($tripDate)->format('j M Y') }}
                <span class="mx-1">&middot;</span>
                Coach {{ $buslist->coach_no }}
            </div>
        </div>

        @if($existingBehavior)
        <div class="alert alert-info py-2 small">
            <i class="fas fa-circle-info me-1"></i>
            You rated this trip {{ $existingBehavior->created_at->diffForHumans() }}.
            Submitting again will replace your earlier answers.
        </div>
        @endif

        @if($errors->any())
        <div class="alert alert-danger py-2">
            <ul class="mb-0 ps-3">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
        @endif

        {{-- Conduct dimensions ------------------------------------------ --}}
        <fieldset class="border-0 p-0 m-0">
            <legend class="h6 fw-bold mb-2">The bus and its crew</legend>

            @foreach($dimensions as $key => $dim)
            @php
                $field = $key . '_score';
                $current = old($field, $existingBehavior->{$field} ?? null);
            @endphp
            <div class="dim-block">
                <div class="dim-head">
                    <i class="fas {{ $dim['icon'] }}" aria-hidden="true"></i>
                    <span class="dim-name">{{ $dim['label'] }}</span>
                </div>
                <p class="dim-help" id="help-{{ $key }}">{{ $dim['help'] }}</p>

                <div class="star-row" role="radiogroup" aria-labelledby="help-{{ $key }}">
                    @for($i = 5; $i >= 1; $i--)
                    <input type="radio"
                           name="{{ $field }}"
                           id="{{ $field }}_{{ $i }}"
                           value="{{ $i }}"
                           {{ (int) $current === $i ? 'checked' : '' }}
                           required>
                    <label for="{{ $field }}_{{ $i }}"
                           title="{{ $i }} out of 5">
                        <i class="fas fa-star" aria-hidden="true"></i>
                        <span class="visually-hidden">{{ $i }} {{ Str::plural('star', $i) }}</span>
                    </label>
                    @endfor
                </div>
                <div class="rating-word" data-word-for="{{ $field }}" aria-live="polite"></div>
            </div>
            @endforeach
        </fieldset>
    </div>

    {{-- Seat stars ------------------------------------------------------ --}}
    @if(count($seats))
    <div class="rate-card">
        <h2 class="h6 fw-bold mb-1">Your {{ Str::plural('seat', count($seats)) }}</h2>
        <p class="text-muted small mb-3">
            Optional. Rating a seat helps the next passenger choosing where to sit.
        </p>

        <div class="seat-chip-row">
            @foreach($seats as $seat)
            @php
                $seatKey = 'seat_ratings.' . $seat;
                $seatCurrent = old($seatKey, $existingSeatRatings[$seat] ?? null);
            @endphp
            <div class="seat-block">
                <div class="seat-name">Seat {{ $seat }}</div>
                <div class="star-row" role="radiogroup" aria-label="Rating for seat {{ $seat }}">
                    @for($i = 5; $i >= 1; $i--)
                    <input type="radio"
                           name="seat_ratings[{{ $seat }}]"
                           id="seat_{{ $loop->parent->index }}_{{ $i }}"
                           value="{{ $i }}"
                           {{ (int) $seatCurrent === $i ? 'checked' : '' }}>
                    <label for="seat_{{ $loop->parent->index }}_{{ $i }}" title="{{ $i }} out of 5">
                        <i class="fas fa-star" aria-hidden="true"></i>
                        <span class="visually-hidden">Seat {{ $seat }}: {{ $i }} {{ Str::plural('star', $i) }}</span>
                    </label>
                    @endfor
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Comment --------------------------------------------------------- --}}
    <div class="rate-card">
        <label for="tripComment" class="form-label fw-bold">
            Anything else? <span class="text-muted fw-normal">(optional)</span>
        </label>
        <textarea name="comment" id="tripComment" rows="4" maxlength="1000"
                  class="form-control"
                  placeholder="e.g. Left 40 minutes late from Gabtoli, but the driver was careful and the AC worked."
                  aria-describedby="commentCount">{{ old('comment', $existingBehavior->comment ?? '') }}</textarea>
        <div id="commentCount" class="form-text text-end">
            <span id="commentUsed">0</span>/1000
        </div>
    </div>

    <div class="sticky-submit d-flex gap-2 align-items-center">
        <a href="{{ route('purchase_history') }}" class="btn btn-outline-secondary">Not now</a>
        <button type="submit" class="btn btn-primary flex-fill" id="rateSubmit">
            <i class="fas fa-paper-plane me-1"></i>
            {{ $existingBehavior ? 'Update my rating' : 'Submit rating' }}
        </button>
    </div>
</form>
@endsection

@section('scripts')
<script>
(function () {
    'use strict';

    var WORDS = { 1: 'Poor', 2: 'Below average', 3: 'Okay', 4: 'Good', 5: 'Excellent' };

    // Echo the chosen rating in words: the star count alone is easy to misread,
    // and aria-live announces the change to screen readers.
    document.querySelectorAll('.rating-word').forEach(function (out) {
        var field = out.getAttribute('data-word-for');

        function sync() {
            var picked = document.querySelector('input[name="' + field + '"]:checked');
            out.textContent = picked ? WORDS[picked.value] : '';
        }

        document.querySelectorAll('input[name="' + field + '"]').forEach(function (input) {
            input.addEventListener('change', sync);
        });

        sync();
    });

    var comment = document.getElementById('tripComment');
    var used    = document.getElementById('commentUsed');
    if (comment && used) {
        var sync = function () { used.textContent = comment.value.length; };
        comment.addEventListener('input', sync);
        sync();
    }

    // Point the user at the first unanswered dimension instead of letting the
    // browser scroll to a visually-hidden radio.
    document.getElementById('rateForm').addEventListener('submit', function (e) {
        var missing = null;

        document.querySelectorAll('.dim-block').forEach(function (block) {
            if (missing) return;
            var input = block.querySelector('input[type="radio"]');
            if (!input) return;
            if (!document.querySelector('input[name="' + input.name + '"]:checked')) {
                missing = block;
            }
        });

        if (missing) {
            e.preventDefault();
            missing.scrollIntoView({ behavior: 'smooth', block: 'center' });
            missing.querySelector('.rating-word').textContent = 'Please choose a rating';
            missing.querySelector('.rating-word').style.color = 'var(--danger)';
            return;
        }

        var btn = document.getElementById('rateSubmit');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Saving&hellip;';
    });
})();
</script>
@endsection
