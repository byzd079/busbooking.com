<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SeatHold extends Model
{
    use HasFactory;

    protected $fillable = [
        'bus_id',
        'seat_name',
        'order_id',
        'session_id',
        'user_id',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function bus()
    {
        return $this->belongsTo(Bus::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Delete expired holds across the database.
     */
    public static function cleanupExpired(): int
    {
        return self::where('expires_at', '<=', now())->delete();
    }
}
