<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    // The home page ("/") now renders live popular-route data via RouteInsights,
    // which queries the orders/buses tables. This smoke test therefore needs a
    // migrated schema, same as the other feature tests. With an empty (but
    // present) database RouteInsights returns its fallbacks and "/" still 200s.
    use RefreshDatabase;

    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }
}
