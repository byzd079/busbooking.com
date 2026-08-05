<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PostComment extends Model
{
    use HasFactory, SoftDeletes;

    /** Window during which a poster can still edit their own comment. */
    public const EDIT_WINDOW_MINUTES = 15;

    protected $fillable = [
        'post_id',
        'user_id',
        'parent_comment_id',
        'comment_text',
        'is_verified_passenger',
    ];

    protected $casts = [
        'is_verified_passenger' => 'boolean',
        'is_hidden'             => 'boolean',
        'flag_count'            => 'integer',
    ];

    public function post()
    {
        return $this->belongsTo(BusPost::class, 'post_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function parent()
    {
        return $this->belongsTo(PostComment::class, 'parent_comment_id');
    }

    public function replies()
    {
        return $this->hasMany(PostComment::class, 'parent_comment_id');
    }

    public function scopeVisible($query)
    {
        return $query->where('is_hidden', false);
    }

    /** Top-level comments only; replies are loaded through the replies relation. */
    public function scopeTopLevel($query)
    {
        return $query->whereNull('parent_comment_id');
    }

    public function isOwnedBy(?int $userId): bool
    {
        return $userId !== null && $this->user_id === $userId;
    }

    public function isEditable(): bool
    {
        return $this->created_at->diffInMinutes(now()) < self::EDIT_WINDOW_MINUTES;
    }
}
