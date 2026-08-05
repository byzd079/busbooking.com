<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\CanResetPassword;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'mobile_no',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // Relationships
    public function seatRatings()
    {
        return $this->hasMany(SeatRating::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function busPosts()
    {
        return $this->hasMany(BusPost::class);
    }

    public function postComments()
    {
        return $this->hasMany(PostComment::class);
    }

    public function behaviorScores()
    {
        return $this->hasMany(BusBehaviorScore::class);
    }

    public function helpfulMarks()
    {
        return $this->hasMany(PostHelpfulMark::class);
    }

    /**
     * Check whether this user has a completed order for the given bus (matched
     * by email, since orders.user_id does not exist). Used to set the verified-
     * passenger badge when submitting a post or behavior score.
     *
     * orders.bus_id holds buses.id (validated `exists:buses,id`), but the caller
     * passes a buslists.id. Resolve via coach_no, exactly as SeatRatingController
     * does to avoid false negatives (real passenger not badged) and false positives
     * (badge granted for the wrong coach when IDs collide in the low range).
     */
    public function hasCompletedOrderFor(int $buslistId): bool
    {
        $buslist = buslist::find($buslistId);

        if (!$buslist) {
            return false;
        }

        // orders.bus_id is a buses.id, but buslists and buses are separate tables.
        // Match on coach_no to bridge the two domains.
        $busIds = Bus::where('coach_no', $buslist->coach_no)->pluck('id');

        if ($busIds->isEmpty()) {
            return false;
        }

        return Order::where('email', $this->email)
            ->whereIn('bus_id', $busIds)
            ->where('status', 'Processing')  // 'Processing' = completed trip
            ->exists();
    }
}
