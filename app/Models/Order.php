<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_id',
        'name',
        'email',
        'phone',
        'amount',
        'status',
        'address',
        'currency',
        'bus_id',
        'ticketlist',
        'card_issuer',
        'refund_amount',
        'refund_status',
        'refund_method',
        'refund_mobile',
        'refund_reason',
        'refund_requested_at',
        'refund_processed_at',
        // Removed: 'refund_processed_by' - admins must set this explicitly, not via mass assignment
    ];

    public function downloadToken(): string
    {
        return hash_hmac('sha256', (string) $this->getKey(), (string) config('app.key'));
    }

    // Calculate refund amount based on time policy
    public function calculateRefundAmount()
    {
        $bus = Bus::find($this->bus_id);
        if (!$bus) return 0;

        $tripDateTime = Carbon::parse($bus->date . ' ' . $bus->departing_time);
        $now = Carbon::now();
        $hoursUntilTrip = $now->diffInHours($tripDateTime, false);

        if ($hoursUntilTrip >= 4) {
            return $this->amount; // Full refund
        } elseif ($hoursUntilTrip >= 2) {
            return $this->amount * 0.5; // 50% refund
        } else {
            return 0; // No refund
        }
    }

    // Get refund policy message
    public function getRefundPolicy()
    {
        $bus = Bus::find($this->bus_id);
        if (!$bus) return 'Bus not found';

        $tripDateTime = Carbon::parse($bus->date . ' ' . $bus->departing_time);
        $now = Carbon::now();
        $hoursUntilTrip = $now->diffInHours($tripDateTime, false);

        if ($hoursUntilTrip >= 4) {
            return "Full refund available (more than 4 hours before trip)";
        } elseif ($hoursUntilTrip >= 2) {
            return "50% refund available (2-4 hours before trip)";
        } else {
            return "No refund available (less than 2 hours before trip)";
        }
    }

    // Check if refund is possible
    public function canRefund()
    {
        $bus = Bus::find($this->bus_id);
        if (!$bus) return false;

        $tripDateTime = Carbon::parse($bus->date . ' ' . $bus->departing_time);
        $now = Carbon::now();
        $hoursUntilTrip = $now->diffInHours($tripDateTime, false);

        return $hoursUntilTrip >= 2 && $this->status === 'Processing';
    }

    // Get hours until trip
    public function getHoursUntilTrip()
    {
        $bus = Bus::find($this->bus_id);
        if (!$bus) return 0;

        $tripDateTime = Carbon::parse($bus->date . ' ' . $bus->departing_time);
        $now = Carbon::now();
        return $now->diffInHours($tripDateTime, false);
    }
}
