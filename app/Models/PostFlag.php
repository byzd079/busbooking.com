<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PostFlag extends Model
{
    protected $fillable = [
        'post_id',
        'user_id',
    ];

    public function post()
    {
        return $this->belongsTo(BusPost::class, 'post_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
