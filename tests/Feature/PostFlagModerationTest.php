<?php

namespace Tests\Feature;

use App\Models\BusPost;
use App\Models\buslist;
use App\Models\PostComment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Flag/report moderation.
 *
 * Two bugs used to cancel each other out here: one account could inflate
 * flag_count without limit, and the auto-hide write was silently dropped
 * because is_hidden is not mass-assignable. Fixing only the second would have
 * turned the first into "any user can hide any post", so both are covered.
 */
class PostFlagModerationTest extends TestCase
{
    use RefreshDatabase;

    private function makePost(): BusPost
    {
        $buslist = buslist::create([
            'bus_name'        => 'Test Coach',
            'departing_time'  => '10:00',
            'coach_no'        => 'TEST-1',
            'starting_point'  => 'Dhaka',
            'ending_point'    => 'Khulna',
            'fare'            => 500,
            'coach_type'      => 'AC',
            'seats_available' => 40,
        ]);

        $owner = User::factory()->create();

        return BusPost::create([
            'bus_id'     => $buslist->id,
            'user_id'    => $owner->id,
            'post_type'  => 'seat',
            'image_data' => 'fake-bytes',
            'mime_type'  => 'image/webp',
            'caption'    => 'A seat',
        ]);
    }

    public function test_one_user_cannot_flag_the_same_post_twice(): void
    {
        $post = $this->makePost();
        $flagger = User::factory()->create();

        for ($i = 0; $i < 6; $i++) {
            $this->actingAs($flagger)
                ->post(route('bus.post.flag', $post->id))
                ->assertRedirect();
        }

        // Six requests, one distinct reporter: the tally must stop at 1 and the
        // post must remain visible.
        $this->assertSame(1, $post->fresh()->flag_count);
        $this->assertFalse((bool) $post->fresh()->is_hidden);
    }

    public function test_post_auto_hides_once_enough_distinct_users_flag_it(): void
    {
        $post = $this->makePost();

        for ($i = 0; $i < BusPost::FLAG_HIDE_THRESHOLD; $i++) {
            $this->actingAs(User::factory()->create())
                ->post(route('bus.post.flag', $post->id))
                ->assertRedirect();
        }

        // is_hidden is outside $fillable, so this only passes if the controller
        // assigns it directly rather than through update([...]).
        $this->assertSame(BusPost::FLAG_HIDE_THRESHOLD, $post->fresh()->flag_count);
        $this->assertTrue((bool) $post->fresh()->is_hidden);
    }

    public function test_one_user_cannot_flag_the_same_comment_twice(): void
    {
        $post = $this->makePost();
        $commenter = User::factory()->create();

        $comment = PostComment::create([
            'post_id'      => $post->id,
            'user_id'      => $commenter->id,
            'comment_text' => 'A reply',
        ]);

        $flagger = User::factory()->create();

        for ($i = 0; $i < 6; $i++) {
            $this->actingAs($flagger)
                ->post(route('post.comment.flag', $comment->id))
                ->assertRedirect();
        }

        $this->assertSame(1, $comment->fresh()->flag_count);
        $this->assertFalse((bool) $comment->fresh()->is_hidden);
    }
}
