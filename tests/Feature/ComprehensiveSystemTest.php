<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Bus;
use App\Models\buslist;
use App\Models\Order;
use App\Models\Admin;
use Database\Seeders\ProductionBusSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ComprehensiveSystemTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ProductionBusSeeder::class);
    }

    /** @test */
    public function homepage_loads_successfully()
    {
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('JatraPoth');
    }

    /** @test */
    public function search_bus_returns_buses_for_valid_route()
    {
        $response = $this->get('/search_bus?starting_point=Dhaka&ending_point=Chattogram&date=' . date('Y-m-d'));
        $response->assertStatus(200);
    }

    /** @test */
    public function seat_view_loads_for_valid_bus()
    {
        $bus = Bus::first() ?? Bus::create([
            'date' => date('Y-m-d'),
            'bus_name' => 'Express Test',
            'departing_time' => '10:00 AM',
            'coach_no' => '999',
            'starting_point' => 'Dhaka',
            'ending_point' => 'Sylhet',
            'fare' => 500,
            'coach_type' => 'AC',
            'seats_available' => 40,
            'view' => str_repeat('0', 40),
            'total_seats' => 40
        ]);

        $response = $this->get('/seat_view/' . $bus->id);
        $response->assertStatus(200);
        $response->assertSee($bus->bus_name);
    }

    /** @test */
    public function payment_details_calculates_fare_and_shows_checkout()
    {
        $bus = Bus::first();
        $response = $this->get('/payment_details?id=' . $bus->id . '&A1=1&A2=1');
        $response->assertStatus(200);
        $response->assertSee('Checkout');
    }

    /** @test */
    public function user_can_register_login_and_logout()
    {
        $email = 'qa_test_' . rand(1000, 9999) . '@example.com';
        
        // Registration
        $registerResponse = $this->post('/register', [
            'name' => 'QA Tester',
            'email' => $email,
            'mobile_no' => '017' . rand(10000000, 99999999),
            'password' => 'password123',
        ]);
        $registerResponse->assertRedirect(route('home'));

        // Logout
        $this->get('/log_out')->assertRedirect('/');

        // Login
        $loginResponse = $this->post('/log_in', [
            'email' => $email,
            'password' => 'password123',
        ]);
        $loginResponse->assertRedirect(route('home'));
    }

    /** @test */
    public function bus_reviews_api_returns_json_reviews()
    {
        $bus = Bus::first();
        $response = $this->get('/bus-reviews?bus_id=' . $bus->id);
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'reviews',
            'total_reviews'
        ]);
    }

    /** @test */
    public function pdf_ticket_download_generates_pdf_document()
    {
        $bus = Bus::first();
        $order = Order::first() ?? Order::create([
            'name' => 'Test Passenger',
            'email' => 'passenger@example.com',
            'phone' => '01711112222',
            'amount' => 1000,
            'status' => 'Processing',
            'address' => 'Dhaka',
            'transaction_id' => 'TEST_TRAN_' . rand(1000, 9999),
            'currency' => 'BDT',
            'bus_id' => $bus->id,
            'ticketlist' => json_encode(['A1', 'A2'])
        ]);

        $response = $this->get('/downloadTicket?order_id=' . $order->id . '&token=' . $order->downloadToken());
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }
}
