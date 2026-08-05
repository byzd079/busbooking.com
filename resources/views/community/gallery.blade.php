@extends('layout')

@section('title', $buslist->bus_name . ' — Photos & Passenger Reports')

@section('styles')
<style>
    /* ---- Gallery header ------------------------------------------------ */
    .gallery-head {
        background: var(--card-bg);
        border: 1px solid var(--card-border);
        border-radius: var(--border-radius-lg);
        box-shadow: var(--shadow-lg);
        padding: 1.5rem;
        margin-bottom: 1.25rem;
    }

    .bus-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--text-dark);
        margin-bottom: .25rem;
    }

    .bus-route {
        color: var(--text-muted);
        font-size: .95rem;
    }

    /* ---- Score card ---------------------------------------------------- */
    .score-card {
        background: var(--card-bg);
        border: 1px solid var(--card-border);
        border-radius: var(--border-radius-lg);
        box-shadow: var(--shadow-md);
        padding: 1.25rem;
        height: 100%;
    }

    .score-hero {
        display: flex;
        align-items: baseline;
        gap: .5rem;
    }

    .score-hero .value {
        font-size: 2.5rem;
        font-weight: 700;
        line-height: 1;
    }

    .score-hero .out-of {
        color: var(--text-muted);
        font-size: 1rem;
    }

    .score-band {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        font-size: .8rem;
        font-weight: 600;
        padding: .2rem .6rem;
        border-radius: 999px;
        color: #fff;
    }

    .dim-row {
        display: grid;
        grid-template-columns: 1fr auto;
        gap: .35rem .75rem;
        align-items: center;
        padding: .5rem 0;
        border-bottom: 1px solid rgba(226, 232, 240, .7);
    }

    .dim-row:last-child { border-bottom: 0; }

    .dim-label {
        font-size: .9rem;
        font-weight: 500;
        color: var(--text-dark);
        display: flex;
        align-items: center;
        gap: .45rem;
    }

    .dim-value {
        font-variant-numeric: tabular-nums;
        font-weight: 600;
        font-size: .9rem;
    }

    .dim-bar {
        grid-column: 1 / -1;
        height: 8px;
        border-radius: 999px;
        background: #e2e8f0;
        overflow: hidden;
    }

    .dim-bar > span {
        display: block;
        height: 100%;
        border-radius: 999px;
        transition: width .4s ease;
    }

    /* Trend is never colour-only: each state carries its own glyph and text. */
    .trend {
        font-size: .72rem;
        font-weight: 600;
        padding: .1rem .4rem;
        border-radius: var(--border-radius-sm);
    }

    .trend-up   { color: #047857; background: #d1fae5; }
    .trend-down { color: #b91c1c; background: #fee2e2; }
    .trend-flat { color: var(--text-muted); background: #f1f5f9; }

    /* ---- Filter chips -------------------------------------------------- */
    .filter-bar {
        display: flex;
        gap: .5rem;
        overflow-x: auto;
        padding-bottom: .35rem;
        scrollbar-width: thin;
    }

    .filter-chip {
        flex: 0 0 auto;
        min-height: 40px;
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        padding: .45rem .9rem;
        border-radius: 999px;
        border: 1px solid var(--card-border);
        background: rgba(255,255,255,.9);
        color: var(--text-dark);
        font-size: .875rem;
        font-weight: 500;
        text-decoration: none;
        white-space: nowrap;
        transition: all .15s ease;
    }

    .filter-chip:hover { background: var(--primary-light); color: var(--primary); }

    .filter-chip.active {
        background: var(--primary);
        border-color: var(--primary);
        color: #fff;
        box-shadow: var(--shadow-primary);
    }

    .filter-chip:focus-visible {
        outline: 3px solid var(--accent);
        outline-offset: 2px;
    }

    /* ---- Photo grid ---------------------------------------------------- */
    .photo-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
        gap: 1rem;
    }

    .photo-card {
        background: var(--card-bg);
        border: 1px solid var(--card-border);
        border-radius: var(--border-radius-md);
        overflow: hidden;
        box-shadow: var(--shadow-sm);
        transition: transform .15s ease, box-shadow .15s ease;
        display: flex;
        flex-direction: column;
    }

    .photo-card:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-lg);
    }

    .photo-thumb {
        position: relative;
        width: 100%;
        aspect-ratio: 4 / 3;
        background: #e2e8f0;
        border: 0;
        padding: 0;
        cursor: pointer;
        display: block;
    }

    .photo-thumb img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    .photo-thumb:focus-visible {
        outline: 3px solid var(--primary);
        outline-offset: -3px;
    }

    .type-tag {
        position: absolute;
        top: .5rem;
        left: .5rem;
        background: rgba(15, 23, 42, .78);
        color: #fff;
        font-size: .7rem;
        font-weight: 600;
        padding: .2rem .55rem;
        border-radius: var(--border-radius-sm);
        backdrop-filter: blur(4px);
    }

    .photo-body { padding: .75rem .85rem .85rem; flex: 1; display: flex; flex-direction: column; }

    .photo-caption {
        font-size: .9rem;
        color: var(--text-dark);
        margin-bottom: .5rem;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .photo-meta {
        display: flex;
        align-items: center;
        gap: .4rem;
        font-size: .78rem;
        color: var(--text-muted);
        margin-top: auto;
    }

    .verified-badge {
        display: inline-flex;
        align-items: center;
        gap: .25rem;
        font-size: .7rem;
        font-weight: 600;
        color: #047857;
        background: #d1fae5;
        padding: .1rem .4rem;
        border-radius: var(--border-radius-sm);
    }

    .photo-actions {
        display: flex;
        gap: .4rem;
        padding: .5rem .85rem .75rem;
        border-top: 1px solid rgba(226,232,240,.7);
    }

    .act-btn {
        flex: 1;
        min-height: 40px;
        border: 1px solid var(--card-border);
        background: #fff;
        border-radius: var(--border-radius-sm);
        font-size: .8rem;
        font-weight: 500;
        color: var(--text-muted);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: .35rem;
        cursor: pointer;
        transition: all .15s ease;
    }

    .act-btn:hover { background: var(--primary-light); color: var(--primary); border-color: var(--primary); }
    .act-btn:focus-visible { outline: 3px solid var(--primary); outline-offset: 2px; }
    .act-btn.is-marked { background: var(--primary-light); color: var(--primary); border-color: var(--primary); font-weight: 600; }

    /* ---- Empty state --------------------------------------------------- */
    .empty-state {
        background: var(--card-bg);
        border: 2px dashed var(--card-border);
        border-radius: var(--border-radius-lg);
        padding: 3rem 1.5rem;
        text-align: center;
    }

    .empty-state i {
        font-size: 3rem;
        color: var(--primary);
        opacity: .35;
        margin-bottom: 1rem;
    }

    /* ---- Upload FAB ---------------------------------------------------- */
    .upload-fab {
        position: fixed;
        right: 1.25rem;
        bottom: 88px;
        z-index: 900;
        min-height: 56px;
        padding: 0 1.25rem;
        border-radius: 999px;
        border: 0;
        background: var(--primary);
        color: #fff;
        font-weight: 600;
        box-shadow: var(--shadow-primary);
        display: inline-flex;
        align-items: center;
        gap: .5rem;
    }

    .upload-fab:hover { background: var(--primary-hover); }
    .upload-fab:focus-visible { outline: 3px solid var(--accent); outline-offset: 3px; }

    @media (min-width: 769px) {
        .upload-fab { bottom: 1.5rem; }
    }

    /* ---- Lightbox ------------------------------------------------------ */
    .lightbox-img {
        width: 100%;
        max-height: 65vh;
        object-fit: contain;
        background: #0f172a;
        border-radius: var(--border-radius-md);
    }

    .comment-item {
        padding: .65rem 0;
        border-bottom: 1px solid rgba(226,232,240,.7);
    }

    .comment-item:last-child { border-bottom: 0; }

    .comment-author {
        font-weight: 600;
        font-size: .85rem;
        color: var(--text-dark);
    }

    .comment-body { font-size: .9rem; color: var(--text-dark); }

    .drop-zone {
        border: 2px dashed var(--card-border);
        border-radius: var(--border-radius-md);
        padding: 1.5rem;
        text-align: center;
        cursor: pointer;
        transition: all .15s ease;
    }

    .drop-zone.dragover { border-color: var(--primary); background: var(--primary-light); }
    .drop-zone:focus-within { border-color: var(--primary); }

    #uploadPreview {
        max-height: 220px;
        border-radius: var(--border-radius-sm);
        object-fit: contain;
    }

    @media (prefers-reduced-motion: reduce) {
        .photo-card, .dim-bar > span, .act-btn { transition: none; }
    }
</style>
@endsection

@section('content')
<div class="mb-3">
    <a href="{{ url()->previous() }}" class="btn btn-sm btn-outline-light">
        <i class="fas fa-arrow-left me-1"></i> Back
    </a>
</div>

{{-- Bus identity ------------------------------------------------------- --}}
<div class="gallery-head">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
        <div>
            <h1 class="bus-title">{{ $buslist->bus_name }}</h1>
            <p class="bus-route mb-0">
                <i class="fas fa-route me-1"></i>
                {{ $buslist->starting_point }} &rarr; {{ $buslist->ending_point }}
                <span class="mx-2">&middot;</span>
                <i class="fas fa-bus me-1"></i> Coach {{ $buslist->coach_no }}
                <span class="mx-2">&middot;</span>
                {{ $buslist->coach_type }}
            </p>
        </div>
        <div class="text-md-end">
            <div class="fw-semibold" style="font-size:1.1rem">
                <i class="fas fa-images text-primary me-1"></i>
                {{ $totalPosts }} {{ Str::plural('photo', $totalPosts) }}
            </div>
            @if($seatRating['total_reviews'] > 0)
            <div class="text-muted small mt-1">
                <i class="fas fa-star text-warning"></i>
                {{ $seatRating['average_rating'] }} seat rating
                ({{ $seatRating['total_reviews'] }} {{ Str::plural('review', $seatRating['total_reviews']) }})
            </div>
            @endif
        </div>
    </div>
</div>

<div class="row g-3">
    {{-- Conduct score --------------------------------------------------- --}}
    <div class="col-lg-4">
        <div class="score-card">
            <h2 class="h6 fw-bold mb-3">
                <i class="fas fa-clipboard-check text-primary me-1"></i>
                Passenger reports
            </h2>

            @if($behavior['published'])
                <div class="score-hero mb-1">
                    <span class="value" style="color: {{ $behavior['dimensions']['overall']['band']['color'] }}">
                        {{ number_format($behavior['overall'], 1) }}
                    </span>
                    <span class="out-of">/ 5</span>
                </div>
                <div class="mb-3">
                    <span class="score-band" style="background: {{ $behavior['dimensions']['overall']['band']['color'] }}">
                        <i class="fas fa-circle-check"></i>
                        {{ $behavior['dimensions']['overall']['band']['label'] }}
                    </span>
                    <span class="text-muted small ms-2">
                        {{ $behavior['total_scores'] }} {{ Str::plural('report', $behavior['total_scores']) }}, last 90 days
                    </span>
                </div>

                @foreach($behavior['dimensions'] as $key => $dim)
                <div class="dim-row">
                    <span class="dim-label">
                        <i class="fas {{ $dim['icon'] }}" style="color: var(--text-muted); width:16px" aria-hidden="true"></i>
                        {{ $dim['label'] }}
                    </span>
                    <span class="dim-value" style="color: {{ $dim['band']['color'] }}">
                        {{ number_format($dim['average'], 1) }}
                        @if($dim['trend'] === 'up')
                            <span class="trend trend-up"><i class="fas fa-arrow-up"></i> improving</span>
                        @elseif($dim['trend'] === 'down')
                            <span class="trend trend-down"><i class="fas fa-arrow-down"></i> declining</span>
                        @endif
                    </span>
                    <div class="dim-bar">
                        <span style="width: {{ $dim['percent'] }}%; background: {{ $dim['band']['color'] }}"
                              role="img"
                              aria-label="{{ $dim['label'] }}: {{ number_format($dim['average'], 1) }} out of 5"></span>
                    </div>
                </div>
                @endforeach
            @else
                {{-- Not enough data: say so plainly rather than showing a
                     number that a single rating could swing. --}}
                <div class="text-center py-3">
                    <i class="fas fa-hourglass-half text-muted mb-2" style="font-size:2rem; opacity:.4"></i>
                    <p class="mb-1 fw-semibold">Not enough reports yet</p>
                    <p class="text-muted small mb-0">
                        @if($behavior['total_scores'] > 0)
                            {{ $behavior['total_scores'] }} passenger {{ Str::plural('report', $behavior['total_scores']) }} so far.
                            {{ $behavior['needed'] }} more and the score goes live.
                        @else
                            Ratings appear once {{ $behavior['needed'] }} passengers have reported on this bus.
                        @endif
                    </p>
                </div>
            @endif

            <hr class="my-3">
            <p class="text-muted mb-0" style="font-size:.78rem">
                <i class="fas fa-circle-info me-1"></i>
                Reports come from passengers with a confirmed booking, covering the last 90 days.
            </p>
        </div>
    </div>

    {{-- Photo grid ------------------------------------------------------ --}}
    <div class="col-lg-8">
        {{-- Filters --}}
        <div class="filter-bar mb-3" role="group" aria-label="Filter photos by subject">
            <a href="{{ route('bus.gallery', ['busId' => request()->route('busId'), 'sort' => $sort]) }}"
               class="filter-chip {{ $activeFilter === null ? 'active' : '' }}">
                All <span class="opacity-75">{{ $totalPosts }}</span>
            </a>
            @foreach(\App\Models\BusPost::TYPES as $key => $label)
                @if(($counts[$key] ?? 0) > 0)
                <a href="{{ route('bus.gallery', ['busId' => request()->route('busId'), 'type' => $key, 'sort' => $sort]) }}"
                   class="filter-chip {{ $activeFilter === $key ? 'active' : '' }}">
                    {{ $label }} <span class="opacity-75">{{ $counts[$key] }}</span>
                </a>
                @endif
            @endforeach
        </div>

        {{-- Sort --}}
        @if($totalPosts > 1)
        <div class="d-flex justify-content-end mb-2">
            <div class="btn-group btn-group-sm" role="group" aria-label="Sort photos">
                <a href="{{ route('bus.gallery', array_filter(['busId' => request()->route('busId'), 'type' => $activeFilter, 'sort' => 'recent'])) }}"
                   class="btn {{ $sort === 'recent' ? 'btn-primary' : 'btn-outline-light' }}">Newest</a>
                <a href="{{ route('bus.gallery', array_filter(['busId' => request()->route('busId'), 'type' => $activeFilter, 'sort' => 'helpful'])) }}"
                   class="btn {{ $sort === 'helpful' ? 'btn-primary' : 'btn-outline-light' }}">Most helpful</a>
            </div>
        </div>
        @endif

        @if($posts->isEmpty())
            <div class="empty-state">
                <i class="fas fa-camera-retro" aria-hidden="true"></i>
                <h2 class="h5 fw-bold">No photos of this bus yet</h2>
                <p class="text-muted mb-3">
                    Travelled on it? Share a photo of the seats or the bus so other passengers know what to expect.
                </p>
                @auth
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadModal">
                        <i class="fas fa-camera me-1"></i> Add the first photo
                    </button>
                @else
                    <a href="{{ route('login') }}" class="btn btn-primary">
                        <i class="fas fa-sign-in-alt me-1"></i> Sign in to add a photo
                    </a>
                @endauth
            </div>
        @else
            <div class="photo-grid">
                @foreach($posts as $post)
                <article class="photo-card">
                    <button type="button"
                            class="photo-thumb"
                            data-bs-toggle="modal"
                            data-bs-target="#photoModal"
                            data-post-id="{{ $post->id }}"
                            data-img="{{ route('bus.post.image', $post->id) }}"
                            data-caption="{{ $post->caption }}"
                            data-author="{{ $post->user->name ?? 'Passenger' }}"
                            data-verified="{{ $post->is_verified_passenger ? '1' : '0' }}"
                            data-date="{{ $post->created_at->diffForHumans() }}"
                            data-type="{{ $post->type_label }}"
                            aria-label="Open photo: {{ $post->alt_text }}">
                        <img src="{{ route('bus.post.image', $post->id) }}"
                             alt="{{ $post->alt_text }}"
                             loading="lazy"
                             width="{{ $post->image_width }}"
                             height="{{ $post->image_height }}">
                        <span class="type-tag">{{ $post->type_label }}</span>
                    </button>

                    <div class="photo-body">
                        @if($post->caption)
                            <p class="photo-caption">{{ $post->caption }}</p>
                        @endif
                        <div class="photo-meta">
                            <i class="fas fa-user-circle" aria-hidden="true"></i>
                            <span>{{ $post->user->name ?? 'Passenger' }}</span>
                            @if($post->is_verified_passenger)
                                <span class="verified-badge" title="This passenger had a confirmed booking on this bus">
                                    <i class="fas fa-circle-check"></i> Verified rider
                                </span>
                            @endif
                            <span class="ms-auto">{{ $post->created_at->diffForHumans(null, true) }}</span>
                        </div>
                    </div>

                    <div class="photo-actions">
                        @auth
                        <form method="POST" action="{{ route('bus.post.helpful', $post->id) }}" class="flex-fill">
                            @csrf
                            <button type="submit"
                                    class="act-btn w-100 {{ isset($markedHelpful[$post->id]) ? 'is-marked' : '' }}"
                                    aria-pressed="{{ isset($markedHelpful[$post->id]) ? 'true' : 'false' }}">
                                <i class="{{ isset($markedHelpful[$post->id]) ? 'fas' : 'far' }} fa-thumbs-up"></i>
                                Helpful{{ $post->helpful_count > 0 ? ' (' . $post->helpful_count . ')' : '' }}
                            </button>
                        </form>
                        @else
                        <a href="{{ route('login') }}" class="act-btn">
                            <i class="far fa-thumbs-up"></i>
                            Helpful{{ $post->helpful_count > 0 ? ' (' . $post->helpful_count . ')' : '' }}
                        </a>
                        @endauth

                        <button type="button"
                                class="act-btn"
                                data-bs-toggle="modal"
                                data-bs-target="#photoModal"
                                data-post-id="{{ $post->id }}"
                                data-img="{{ route('bus.post.image', $post->id) }}"
                                data-caption="{{ $post->caption }}"
                                data-author="{{ $post->user->name ?? 'Passenger' }}"
                                data-verified="{{ $post->is_verified_passenger ? '1' : '0' }}"
                                data-date="{{ $post->created_at->diffForHumans() }}"
                                data-type="{{ $post->type_label }}">
                            <i class="far fa-comment"></i>
                            Reply{{ $post->comments_count > 0 ? ' (' . $post->comments_count . ')' : '' }}
                        </button>

                        @auth
                            @if($post->isOwnedBy(auth()->id()))
                            <form method="POST" action="{{ route('bus.post.destroy', $post->id) }}"
                                  onsubmit="return confirm('Remove this photo? This cannot be undone.')">
                                @csrf @method('DELETE')
                                <button type="submit" class="act-btn" aria-label="Delete your photo">
                                    <i class="far fa-trash-can"></i>
                                </button>
                            </form>
                            @else
                            <form method="POST" action="{{ route('bus.post.flag', $post->id) }}"
                                  onsubmit="return confirm('Report this photo for review?')">
                                @csrf
                                <button type="submit" class="act-btn" aria-label="Report this photo">
                                    <i class="far fa-flag"></i>
                                </button>
                            </form>
                            @endif
                        @endauth
                    </div>
                </article>
                @endforeach
            </div>

            <div class="mt-4 d-flex justify-content-center">
                {{ $posts->links() }}
            </div>
        @endif
    </div>
</div>

{{-- Upload trigger ---------------------------------------------------- --}}
@auth
<button type="button" class="upload-fab" data-bs-toggle="modal" data-bs-target="#uploadModal">
    <i class="fas fa-camera"></i> Add photo
</button>
@endauth

{{-- Upload modal ------------------------------------------------------ --}}
@auth
<div class="modal fade" id="uploadModal" tabindex="-1" aria-labelledby="uploadModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: var(--border-radius-lg)">
            <form method="POST" action="{{ route('bus.post.store') }}" enctype="multipart/form-data" id="uploadForm">
                @csrf
                <input type="hidden" name="bus_id" value="{{ request()->route('busId') }}">

                <div class="modal-header">
                    <h2 class="modal-title h5" id="uploadModalLabel">
                        <i class="fas fa-camera text-primary me-1"></i> Share a photo
                    </h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    @if($errors->any())
                    <div class="alert alert-danger py-2">
                        <ul class="mb-0 ps-3">
                            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                        </ul>
                    </div>
                    @endif

                    <div class="mb-3">
                        <label for="imageInput" class="form-label fw-semibold">Photo</label>
                        <div class="drop-zone" id="dropZone">
                            <img id="uploadPreview" class="img-fluid d-none mb-2" alt="Preview of the photo you selected">
                            <div id="dropPrompt">
                                <i class="fas fa-cloud-arrow-up text-primary mb-2" style="font-size:1.75rem"></i>
                                <p class="mb-1 fw-semibold">Tap to choose a photo</p>
                                <p class="text-muted small mb-0">JPG, PNG or WebP, up to 8 MB</p>
                            </div>
                            <input type="file" id="imageInput" name="image"
                                   accept="image/jpeg,image/png,image/webp"
                                   class="form-control mt-2"
                                   required
                                   aria-describedby="imageHelp">
                        </div>
                        <div id="imageHelp" class="form-text">
                            Photos are resized automatically. Please do not include other passengers' faces.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="postType" class="form-label fw-semibold">What does it show?</label>
                        <select name="post_type" id="postType" class="form-select" required>
                            @foreach(\App\Models\BusPost::TYPES as $key => $label)
                                <option value="{{ $key }}" {{ $key === 'seat' ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-2">
                        <label for="caption" class="form-label fw-semibold">
                            Caption <span class="text-muted fw-normal">(optional)</span>
                        </label>
                        <textarea name="caption" id="caption" rows="3" maxlength="500"
                                  class="form-control"
                                  placeholder="e.g. Seat 4B has good legroom and the AC vent works."
                                  aria-describedby="captionCount"></textarea>
                        <div id="captionCount" class="form-text text-end">
                            <span id="captionUsed">0</span>/500
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="uploadSubmit">
                        <i class="fas fa-paper-plane me-1"></i> Post photo
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endauth

{{-- Photo lightbox + comments ----------------------------------------- --}}
<div class="modal fade" id="photoModal" tabindex="-1" aria-labelledby="photoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content" style="border-radius: var(--border-radius-lg)">
            <div class="modal-header">
                <h2 class="modal-title h6" id="photoModalLabel">Photo</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <img id="lightboxImg" class="lightbox-img mb-3" alt="">

                <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
                    <i class="fas fa-user-circle text-muted"></i>
                    <span id="lightboxAuthor" class="fw-semibold"></span>
                    <span id="lightboxVerified" class="verified-badge d-none">
                        <i class="fas fa-circle-check"></i> Verified rider
                    </span>
                    <span class="text-muted small ms-auto" id="lightboxDate"></span>
                </div>

                <p id="lightboxCaption" class="mb-3"></p>

                <hr>

                <h3 class="h6 fw-bold mb-2">
                    <i class="far fa-comments me-1"></i> Replies
                </h3>
                <div id="commentThread" aria-live="polite">
                    <p class="text-muted small mb-0">Loading replies&hellip;</p>
                </div>

                @auth
                <form method="POST" id="commentForm" class="mt-3">
                    @csrf
                    <label for="commentText" class="form-label visually-hidden">Write a reply</label>
                    <div class="input-group">
                        <textarea name="comment_text" id="commentText" rows="2" maxlength="1000"
                                  class="form-control" placeholder="Ask something or add what you saw&hellip;" required></textarea>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane"></i>
                            <span class="visually-hidden">Post reply</span>
                        </button>
                    </div>
                </form>
                @else
                <p class="text-muted small mt-3 mb-0">
                    <a href="{{ route('login') }}">Sign in</a> to reply to this photo.
                </p>
                @endauth
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
(function () {
    'use strict';

    // ---- Upload: preview + caption counter ---------------------------
    var imageInput = document.getElementById('imageInput');
    var preview    = document.getElementById('uploadPreview');
    var prompt     = document.getElementById('dropPrompt');
    var dropZone   = document.getElementById('dropZone');

    if (imageInput) {
        imageInput.addEventListener('change', function () {
            var file = this.files && this.files[0];
            if (!file) return;

            var reader = new FileReader();
            reader.onload = function (e) {
                preview.src = e.target.result;
                preview.classList.remove('d-none');
                prompt.classList.add('d-none');
            };
            reader.readAsDataURL(file);
        });

        // Drag and drop, with the same handler the file picker uses.
        ['dragenter', 'dragover'].forEach(function (evt) {
            dropZone.addEventListener(evt, function (e) {
                e.preventDefault();
                dropZone.classList.add('dragover');
            });
        });

        ['dragleave', 'drop'].forEach(function (evt) {
            dropZone.addEventListener(evt, function (e) {
                e.preventDefault();
                dropZone.classList.remove('dragover');
            });
        });

        dropZone.addEventListener('drop', function (e) {
            if (e.dataTransfer.files.length) {
                imageInput.files = e.dataTransfer.files;
                imageInput.dispatchEvent(new Event('change'));
            }
        });
    }

    var caption = document.getElementById('caption');
    var used    = document.getElementById('captionUsed');
    if (caption && used) {
        caption.addEventListener('input', function () {
            used.textContent = this.value.length;
        });
    }

    // Guard against a double submit while the image uploads.
    var uploadForm = document.getElementById('uploadForm');
    if (uploadForm) {
        uploadForm.addEventListener('submit', function () {
            var btn = document.getElementById('uploadSubmit');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Uploading&hellip;';
        });
    }

    // If validation failed server-side, reopen the modal so the user sees why.
    @if($errors->any() && auth()->check())
    var reopen = new bootstrap.Modal(document.getElementById('uploadModal'));
    reopen.show();
    @endif

    // ---- Lightbox ----------------------------------------------------
    var photoModal = document.getElementById('photoModal');
    if (!photoModal) return;

    photoModal.addEventListener('show.bs.modal', function (event) {
        var trigger = event.relatedTarget;
        if (!trigger) return;

        var postId = trigger.getAttribute('data-post-id');

        document.getElementById('lightboxImg').src = trigger.getAttribute('data-img');
        document.getElementById('lightboxImg').alt = trigger.getAttribute('data-caption') || 'Bus photo';
        document.getElementById('lightboxAuthor').textContent = trigger.getAttribute('data-author');
        document.getElementById('lightboxDate').textContent = trigger.getAttribute('data-date');
        document.getElementById('lightboxCaption').textContent = trigger.getAttribute('data-caption') || '';
        document.getElementById('photoModalLabel').textContent = trigger.getAttribute('data-type') || 'Photo';

        var verified = document.getElementById('lightboxVerified');
        verified.classList.toggle('d-none', trigger.getAttribute('data-verified') !== '1');

        var form = document.getElementById('commentForm');
        if (form) {
            form.action = '{{ url('/bus-post') }}/' + postId + '/comment';
        }

        var thread = document.getElementById('commentThread');
        thread.innerHTML = '<p class="text-muted small mb-0">Loading replies&hellip;</p>';

        fetch('{{ url('/bus-post') }}/' + postId + '/comments', {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(function (r) { return r.ok ? r.text() : Promise.reject(r.status); })
            .then(function (html) { thread.innerHTML = html; })
            .catch(function () {
                thread.innerHTML = '<p class="text-danger small mb-0">Could not load replies. Please try again.</p>';
            });
    });
})();
</script>
@endsection
