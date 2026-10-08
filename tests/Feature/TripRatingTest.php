<?php

namespace Tests\Feature;

use App\Models\Bus;
use App\Models\buslist;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class TripRatingTest extends TestCase
{
    use RefreshDatabase;

    public function test_rate_my_trip_form_loads_successfully_with_booked_seats(): void
    {
        $user = User::create([
            'name'      => 'Passenger One',
            'email'     => 'passenger@example.com',
            'mobile_no' => '01712345678',
            'password'  => Hash::make('secret123456'),
        ]);

        $buslist = buslist::create([
            'bus_name'       => 'Green Line Paribahan',
            'departing_time' => '08:00 AM',
            'coach_no'       => 'GL-101',
            'starting_point' => 'Dhaka',
            'ending_point'   => 'Chittagong',
            'fare'           => 1200,
            'coach_type'     => 'AC',
        ]);

        $bus = Bus::create([
            'date'            => '2026-10-10',
            'bus_name'        => 'Green Line Paribahan',
            'departing_time'  => '08:00 AM',
            'coach_no'        => 'GL-101',
            'starting_point'  => 'Dhaka',
            'ending_point'    => 'Chittagong',
            'fare'            => 1200,
            'coach_type'      => 'AC',
            'seats_available' => 38,
            'total_seats'     => 40,
            'view'            => '0000000000000000000000000000000000000000',
        ]);

        $order = Order::create([
            'user_id'         => $user->id,
            'name'            => 'Passenger One',
            'email'           => 'passenger@example.com',
            'phone'           => '01700000000',
            'amount'          => 2400,
            'address'         => 'Dhaka',
            'status'          => 'Processing',
            'transaction_id'  => Str::random(30),
            'currency'        => 'BDT',
            'bus_id'          => $bus->id,
            'bus_name'        => 'Green Line Paribahan',
            'ticketlist'      => 'A1, A2',
            'starting_point'  => 'Dhaka',
            'ending_point'    => 'Chittagong',
            'date'            => '2026-10-10',
            'time'            => '08:00 AM',
        ]);

        $response = $this->actingAs($user)->get(route('trip.rating.form', $order->id));

        $response->assertOk();
        $response->assertSee('Seat A1');
        $response->assertSee('Seat A2');
        $response->assertSee('Green Line Paribahan');
    }

    public function test_submitting_trip_rating_stores_behavior_and_seat_ratings(): void
    {
        $user = User::create([
            'name'      => 'Passenger Two',
            'email'     => 'passenger2@example.com',
            'mobile_no' => '01712345679',
            'password'  => Hash::make('secret123456'),
        ]);

        $buslist = buslist::create([
            'bus_name'       => 'Shohagh Paribahan',
            'departing_time' => '09:00 AM',
            'coach_no'       => 'SH-202',
            'starting_point' => 'Dhaka',
            'ending_point'   => 'Coxs Bazar',
            'fare'           => 1500,
            'coach_type'     => 'Scania',
        ]);

        $bus = Bus::create([
            'date'            => '2026-10-10',
            'bus_name'        => 'Shohagh Paribahan',
            'departing_time'  => '09:00 AM',
            'coach_no'        => 'SH-202',
            'starting_point'  => 'Dhaka',
            'ending_point'    => 'Coxs Bazar',
            'fare'            => 1500,
            'coach_type'      => 'Scania',
            'seats_available' => 38,
            'total_seats'     => 40,
            'view'            => '0000000000000000000000000000000000000000',
        ]);

        $order = Order::create([
            'user_id'         => $user->id,
            'name'            => 'Passenger Two',
            'email'           => 'passenger2@example.com',
            'phone'           => '01700000001',
            'amount'          => 1500,
            'address'         => 'Dhaka',
            'status'          => 'Processing',
            'transaction_id'  => Str::random(30),
            'currency'        => 'BDT',
            'bus_id'          => $bus->id,
            'bus_name'        => 'Shohagh Paribahan',
            'ticketlist'      => 'B1',
            'starting_point'  => 'Dhaka',
            'ending_point'    => 'Coxs Bazar',
            'date'            => '2026-10-10',
            'time'            => '09:00 AM',
        ]);

        $payload = [
            'order_id'              => $order->id,
            'trip_date'             => '2026-10-10',
            'route_adherence_score' => 5,
            'punctuality_score'     => 4,
            'cleanliness_score'     => 5,
            'driver_behavior_score' => 5,
            'overall_score'         => 5,
            'comment'               => 'Smooth and on time.',
            'seat_ratings'          => [
                'B1' => 5,
            ],
        ];

        $postResponse = $this->actingAs($user)->post(route('trip.rating.store'), $payload);

        $postResponse->assertRedirect(route('purchase_history'));
        $postResponse->assertSessionHas('success');

        $this->assertDatabaseHas('bus_behavior_scores', [
            'user_id'   => $user->id,
            'bus_id'    => $buslist->id,
            'trip_date' => '2026-10-10 00:00:00',
            'overall_score' => 5,
        ]);

        $this->assertDatabaseHas('seat_ratings', [
            'user_id'   => $user->id,
            'bus_id'    => $buslist->id,
            'seat_name' => 'B1',
            'rating'    => 5,
        ]);
    }
}
