<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class buslist extends Model
{
    use HasFactory;

    protected $fillable = [
        'bus_name',
        'departing_time',
        'coach_no',
        'starting_point',
        'ending_point',
        'fare',
        'coach_type',
        'seats_available',
        'view',
    ];

    public function seatRatings()
    {
        return $this->hasMany(SeatRating::class, 'bus_id');
    }

    public function busPosts()
    {
        return $this->hasMany(BusPost::class, 'bus_id');
    }

    public function behaviorScores()
    {
        return $this->hasMany(BusBehaviorScore::class, 'bus_id');
    }

    /**
     * Conduct score aggregate for the detail page. Reads from the model's static
     * summary method so the shape is consistent across search and detail views.
     */
    public function getBehaviorSummaryAttribute(): array
    {
        return BusBehaviorScore::summaryFor($this->id);
    }
}
