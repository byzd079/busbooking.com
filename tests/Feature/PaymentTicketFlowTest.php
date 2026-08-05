<?php

namespace Tests\Feature;

use App\Models\Bus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentTicketFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_success_callback_without_transaction_id_fails_safely(): void
    {
        $this->get('/success')
            ->assertRedirect(route('home'))
            ->assertSessionHas('error');
    }

    public function test_success_callback_with_unknown_transaction_fails_safely(): void
    {
        $this->get('/success?tran_id=missing-transaction')
            ->assertRedirect(route('home'))
            ->assertSessionHas('error');
    }

    public function test_fail_callback_without_transaction_id_fails_safely(): void
    {
        $this->post('/fail')
            ->assertRedirect(route('home'))
            ->assertSessionHas('error');
    }

    public function test_cancel_callback_without_transaction_id_fails_safely(): void
    {
        $this->get('/cancel')
            ->assertRedirect(route('home'))
            ->assertSessionHas('error');
    }

    public function test_ipn_without_transaction_id_returns_validation_error(): void
    {
        $this->post('/ipn')
            ->assertStatus(422)
            ->assertSee('Invalid data');
    }

    public function test_pdf_download_rejects_a_guessed_order_id(): void
    {
        [$order] = $this->ticketOrder();

        $this->get('/downloadTicket?order_id=' . $order->id)
            ->assertForbidden();
    }

    public function test_pdf_download_works_for_the_order_owner(): void
    {
        [$order, $user] = $this->ticketOrder();

        $this->actingAs($user)
            ->get('/downloadTicket?order_id=' . $order->id)
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertDownload('JatraPoth-ticket-' . $order->transaction_id . '.pdf');
    }

    public function test_pdf_download_works_with_the_secure_ticket_token(): void
    {
        [$order] = $this->ticketOrder();

        $this->get('/downloadTicket?order_id=' . $order->id . '&token=' . $order->downloadToken())
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_payment_rejects_an_unavailable_seat_before_contacting_gateway(): void
    {
        $bus = $this->bus(['view' => '1' . str_repeat('0', 39)]);

        $this->from('/payment_details')
            ->post('/pay', [
                'bus_id' => $bus->id,
                'ticketlist' => ['A1'],
                'amount' => 1,
                'customer_name' => 'Test Passenger',
                'customer_email' => 'passenger@example.com',
                'customer_mobile' => '01711112222',
                'address' => 'Dhaka',
            ])
            ->assertRedirect('/payment_details')
            ->assertSessionHas('error');

        $this->assertDatabaseCount('orders', 0);
    }

    private function ticketOrder(): array
    {
        $user = User::factory()->create(['email' => 'owner@example.com']);
        $bus = $this->bus();
        $order = Order::create([
            'name' => $user->name,
            'email' => $user->email,
            'phone' => '01711112222',
            'amount' => 1300,
            'status' => 'Processing',
            'address' => 'Dhaka',
            'transaction_id' => 'TEST-TRANSACTION-123',
            'currency' => 'BDT',
            'bus_id' => $bus->id,
            'ticketlist' => json_encode(['A1', 'A2']),
        ]);

        return [$order, $user, $bus];
    }

    private function bus(array $overrides = []): Bus
    {
        return Bus::create($overrides + [
            'date' => now()->toDateString(),
            'bus_name' => 'Green Line',
            'departing_time' => '08:00',
            'coach_no' => 'TEST-1001',
            'starting_point' => 'Dhaka',
            'ending_point' => 'Chittagong',
            'fare' => 650,
            'coach_type' => 'AC',
            'seats_available' => 40,
            'view' => str_repeat('0', 40),
            'total_seats' => 40,
        ]);
    }
}
