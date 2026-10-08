<?php

namespace Tests\Feature;

use App\Models\Bus;
use App\Models\Order;
use App\Models\SeatHold;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class SeatHoldConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    private function createBus(): Bus
    {
        return Bus::create([
            'date'            => '2026-10-15',
            'bus_name'        => 'Green Line Express',
            'departing_time'  => '08:00 AM',
            'coach_no'        => 'GL-777',
            'starting_point'  => 'Dhaka',
            'ending_point'    => 'Coxs Bazar',
            'fare'            => 1000,
            'coach_type'      => 'Scania AC',
            'seats_available' => 40,
            'total_seats'     => 40,
            'view'            => str_repeat('0', 40),
        ]);
    }

    public function test_proceeding_to_payment_details_creates_active_seat_hold(): void
    {
        $bus = $this->createBus();

        $response = $this->get('/payment_details?id=' . $bus->id . '&A1=1');

        $response->assertOk();
        $response->assertViewHas('expiresAt');

        $this->assertDatabaseHas('seat_holds', [
            'bus_id'    => $bus->id,
            'seat_name' => 'A1',
        ]);

        $hold = SeatHold::where('bus_id', $bus->id)->where('seat_name', 'A1')->first();
        $this->assertNotNull($hold);
        $this->assertTrue($hold->expires_at->isFuture());
    }

    public function test_second_user_cannot_hold_or_checkout_currently_held_seat(): void
    {
        $bus = $this->createBus();

        // User 1 creates hold
        SeatHold::create([
            'bus_id'     => $bus->id,
            'seat_name'  => 'A1',
            'session_id' => 'session-user-1',
            'expires_at' => Carbon::now()->addMinutes(10),
        ]);

        // User 2 (different session) tries to select seat A1
        $response = $this->withSession(['_token' => 'token-user-2'])
            ->get('/payment_details?id=' . $bus->id . '&A1=1');

        $response->assertRedirect(route('seat_view', $bus->id));
        $response->assertSessionHas('error');

        // User 2 tries to POST /pay directly
        $payResponse = $this->withSession(['_token' => 'token-user-2'])
            ->post('/pay', [
                'bus_id'          => $bus->id,
                'ticketlist'      => ['A1'],
                'customer_name'   => 'Attacker / Racer',
                'customer_email'  => 'racer@example.com',
                'customer_mobile' => '01799999999',
                'address'         => 'Dhaka',
            ]);

        $payResponse->assertSessionHas('error');
    }

    public function test_cancelling_or_failing_payment_instantly_releases_hold(): void
    {
        $bus = $this->createBus();

        $order = Order::create([
            'bus_id'         => $bus->id,
            'name'           => 'Test Passenger',
            'email'          => 'passenger@example.com',
            'phone'          => '01711111111',
            'amount'         => 1000,
            'status'         => 'Pending',
            'transaction_id' => 'TRAN-HOLD-RELEASE-99',
            'ticketlist'     => json_encode(['A1']),
        ]);

        SeatHold::create([
            'bus_id'         => $bus->id,
            'seat_name'      => 'A1',
            'order_id'       => $order->id,
            'session_id'     => 'test-session-xyz',
            'expires_at'     => Carbon::now()->addMinutes(10),
        ]);

        $this->assertDatabaseHas('seat_holds', ['bus_id' => $bus->id, 'seat_name' => 'A1']);

        // Cancel callback is received
        $response = $this->get('/cancel?tran_id=' . $order->transaction_id);

        $response->assertRedirect(route('home'));
        $this->assertEquals('Canceled', $order->fresh()->status);

        // Hold should be instantly released!
        $this->assertDatabaseMissing('seat_holds', ['bus_id' => $bus->id, 'seat_name' => 'A1']);
    }

    public function test_expired_hold_is_automatically_cleaned_up_and_seat_becomes_free(): void
    {
        $bus = $this->createBus();

        // Create an expired hold (expired 5 minutes ago)
        SeatHold::create([
            'bus_id'     => $bus->id,
            'seat_name'  => 'A1',
            'session_id' => 'old-abandoned-session',
            'expires_at' => Carbon::now()->subMinutes(5),
        ]);

        // A new customer views seat_view
        $response = $this->get('/seat_view/' . $bus->id);
        $response->assertOk();

        // Expired hold was cleaned up
        $this->assertDatabaseMissing('seat_holds', ['bus_id' => $bus->id, 'seat_name' => 'A1']);

        // New customer can now successfully hold and checkout
        $checkoutResponse = $this->get('/payment_details?id=' . $bus->id . '&A1=1');
        $checkoutResponse->assertOk();

        $this->assertDatabaseHas('seat_holds', [
            'bus_id'    => $bus->id,
            'seat_name' => 'A1',
        ]);
    }
}
