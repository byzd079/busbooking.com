{{-- Comment thread, loaded into the photo lightbox over fetch(). --}}
@forelse($comments as $comment)
<div class="comment-item">
    <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
        <span class="comment-author">{{ $comment->user->name ?? 'Passenger' }}</span>
        @if($comment->is_verified_passenger)
            <span class="verified-badge"><i class="fas fa-circle-check"></i> Verified rider</span>
        @endif
        <span class="text-muted ms-auto" style="font-size:.75rem">
            {{ $comment->created_at->diffForHumans() }}
        </span>
    </div>

    <p class="comment-body mb-1">{{ $comment->comment_text }}</p>

    @auth
    <div class="d-flex gap-2">
        @if($comment->isOwnedBy(auth()->id()))
        <form method="POST" action="{{ route('post.comment.destroy', $comment->id) }}"
              onsubmit="return confirm('Remove this reply?')">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-link btn-sm p-0 text-muted text-decoration-none"
                    style="font-size:.75rem">
                <i class="far fa-trash-can"></i> Delete
            </button>
        </form>
        @else
        <form method="POST" action="{{ route('post.comment.flag', $comment->id) }}"
              onsubmit="return confirm('Report this reply for review?')">
            @csrf
            <button type="submit" class="btn btn-link btn-sm p-0 text-muted text-decoration-none"
                    style="font-size:.75rem">
                <i class="far fa-flag"></i> Report
            </button>
        </form>
        @endif
    </div>
    @endauth

    {{-- One level of nesting only, so a thread stays readable on a phone. --}}
    @if($comment->replies->isNotEmpty())
    <div class="ps-3 mt-2 border-start border-2">
        @foreach($comment->replies as $reply)
        <div class="py-2">
            <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                <span class="comment-author">{{ $reply->user->name ?? 'Passenger' }}</span>
                @if($reply->is_verified_passenger)
                    <span class="verified-badge"><i class="fas fa-circle-check"></i> Verified rider</span>
                @endif
                <span class="text-muted ms-auto" style="font-size:.75rem">
                    {{ $reply->created_at->diffForHumans() }}
                </span>
            </div>
            <p class="comment-body mb-0">{{ $reply->comment_text }}</p>
        </div>
        @endforeach
    </div>
    @endif
</div>
@empty
<p class="text-muted small mb-0">
    No replies yet. Ask the person who posted this anything about the bus.
</p>
@endforelse
