<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BusPost extends Model
{
    use HasFactory, SoftDeletes;

    public const TYPES = [
        'bus_exterior' => 'Bus exterior',
        'bus_interior' => 'Inside the bus',
        'seat'         => 'Seat',
        'amenity'      => 'Facilities',
        'other'        => 'Other',
    ];

    /** Posts are auto-hidden from the public gallery at this many flags. */
    public const FLAG_HIDE_THRESHOLD = 5;

    protected $fillable = [
        'bus_id',
        'user_id',
        'order_id',
        'post_type',
        'image_data',
        'mime_type',
        'image_size',
        'image_width',
        'image_height',
        'caption',
        'is_verified_passenger',
    ];

    protected $casts = [
        'is_verified_passenger' => 'boolean',
        'is_hidden'             => 'boolean',
        'helpful_count'         => 'integer',
        'comment_count'         => 'integer',
        'flag_count'            => 'integer',
        'image_size'            => 'integer',
        'image_width'           => 'integer',
        'image_height'          => 'integer',
    ];

    /**
     * image_data holds raw bytes and is never sent to a view — the image is
     * served by its own controller route. Hiding it here keeps it out of
     * toArray()/toJson() so a stray dd() or JSON response can't dump megabytes.
     */
    protected $hidden = ['image_data'];

    public function buslist()
    {
        return $this->belongsTo(buslist::class, 'bus_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function comments()
    {
        return $this->hasMany(PostComment::class, 'post_id');
    }

    public function helpfulMarks()
    {
        return $this->hasMany(PostHelpfulMark::class, 'post_id');
    }

    /** Posts the public gallery should show. */
    public function scopeVisible($query)
    {
        return $query->where('is_hidden', false);
    }

    public function scopeForBus($query, $buslistId)
    {
        return $query->where('bus_id', $buslistId);
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->post_type] ?? 'Other';
    }

    public function getImageUrlAttribute(): string
    {
        return route('bus.post.image', $this->id);
    }

    public function isOwnedBy(?int $userId): bool
    {
        return $userId !== null && $this->user_id === $userId;
    }

    /**
     * Alt text for the gallery grid. Falls back to the category label when the
     * poster left the caption empty, so the image is never unlabelled.
     */
    public function getAltTextAttribute(): string
    {
        $caption = trim((string) $this->caption);

        if ($caption !== '') {
            return \Illuminate\Support\Str::limit($caption, 120);
        }

        $busName = $this->buslist->bus_name ?? 'bus';

        return $this->type_label . ' photo of ' . $busName;
    }
}
