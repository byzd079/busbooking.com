<?php

namespace Tests\Feature;

use App\Models\Bus;
use App\Models\Order;
use App\Models\SeatSwap;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class SeatSwapDeadlineTest extends TestCase
{
    use RefreshDatabase;

    private function createScenario(string $departureDate, string $departureTime): array
    {
        $user1 = User::create([
            'name'      => 'User One',
            'email'     => 'user1@example.com',
            'mobile_no' => '01711111111',
            'password'  => Hash::make('password123'),
        ]);

        $user2 = User::create([
            'name'      => 'User Two',
            'email'     => 'user2@example.com',
            'mobile_no' => '01722222222',
            'password'  => Hash::make('password123'),
        ]);

        $bus = Bus::create([
            'date'            => $departureDate,
            'bus_name'        => 'Hanif Enterprise',
            'departing_time'  => $departureTime,
            'coach_no'        => 'HN-555',
            'starting_point'  => 'Dhaka',
            'ending_point'    => 'Bogura',
            'fare'            => 800,
            'coach_type'      => 'Non-AC',
            'seats_available' => 38,
            'total_seats'     => 40,
            'view'            => '0000000000000000000000000000000000000000',
        ]);

        $order1 = Order::create([
            'user_id'        => $user1->id,
            'name'           => 'User One',
            'email'          => 'user1@example.com',
            'phone'          => '01711111111',
            'amount'         => 800,
            'address'        => 'Dhaka',
            'status'         => 'Processing',
            'transaction_id' => Str::random(30),
            'currency'       => 'BDT',
            'bus_id'         => $bus->id,
            'ticketlist'     => json_encode(['A1']),
        ]);

        $order2 = Order::create([
            'user_id'        => $user2->id,
            'name'           => 'User Two',
            'email'          => 'user2@example.com',
            'phone'          => '01722222222',
            'amount'         => 800,
            'address'        => 'Dhaka',
            'status'         => 'Processing',
            'transaction_id' => Str::random(30),
            'currency'       => 'BDT',
            'bus_id'         => $bus->id,
            'ticketlist'     => json_encode(['A2']),
        ]);

        return [$user1, $user2, $order1, $order2, $bus];
    }

    public function test_swap_is_allowed_within_one_hour_after_departure(): void
    {
        // Departure was 30 minutes ago -> within 1 hour limit
        $departure = Carbon::now()->subMinutes(30);
        [$user1, $user2, $order1, $order2, $bus] = $this->createScenario(
            $departure->format('Y-m-d'),
            $departure->format('H:i')
        );

        $this->assertTrue($order1->canSwapSeats());

        // Can access form
        $response = $this->actingAs($user1)->get(route('seat.swap.form', $order1->id));
        $response->assertOk();

        // Can request swap
        $postResponse = $this->actingAs($user1)->post(route('seat.swap.request'), [
            'requester_order_id' => $order1->id,
            'requester_seat'     => 'A1',
            'target_order_id'    => $order2->id,
            'target_seat'        => 'A2',
        ]);
        $postResponse->assertRedirect(route('seat.swap.list'));
        $postResponse->assertSessionHas('success');
    }

    public function test_swap_is_denied_after_one_hour_past_departure(): void
    {
        // Departure was 2 hours ago -> exceeded 1 hour limit
        $departure = Carbon::now()->subHours(2);
        [$user1, $user2, $order1, $order2, $bus] = $this->createScenario(
            $departure->format('Y-m-d'),
            $departure->format('H:i')
        );

        $this->assertFalse($order1->canSwapSeats());

        // Accessing form is rejected
        $response = $this->actingAs($user1)->get(route('seat.swap.form', $order1->id));
        $response->assertRedirect(route('purchase_history'));
        $response->assertSessionHas('error');

        // Submitting request is rejected
        $postResponse = $this->actingAs($user1)->post(route('seat.swap.request'), [
            'requester_order_id' => $order1->id,
            'requester_seat'     => 'A1',
            'target_order_id'    => $order2->id,
            'target_seat'        => 'A2',
        ]);
        $postResponse->assertRedirect(route('purchase_history'));
        $postResponse->assertSessionHas('error');
    }

    public function test_accepting_swap_is_denied_if_one_hour_post_departure_passed(): void
    {
        // Departure was 2 hours ago
        $departure = Carbon::now()->subHours(2);
        [$user1, $user2, $order1, $order2, $bus] = $this->createScenario(
            $departure->format('Y-m-d'),
            $departure->format('H:i')
        );

        $swap = SeatSwap::create([
            'bus_id'             => $bus->id,
            'requester_id'       => $user1->id,
            'requester_order_id' => $order1->id,
            'requester_seat'     => 'A1',
            'target_user_id'     => $user2->id,
            'target_order_id'    => $order2->id,
            'target_seat'        => 'A2',
            'status'             => 'Pending',
        ]);

        $response = $this->actingAs($user2)->post(route('seat.swap.accept', $swap->id));
        $response->assertSessionHas('error');
        $this->assertEquals('Pending', $swap->fresh()->status);
    }
}
