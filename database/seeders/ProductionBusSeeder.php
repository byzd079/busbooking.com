<?php

namespace Database\Seeders;

use App\Services\RouteInsights;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductionBusSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'bus_name' => 'Green Line',
                'departing_time' => '08:00',
                'coach_no' => 'DH-1001',
                'starting_point' => 'Dhaka',
                'ending_point' => 'Chattogram',
                'fare' => 650,
                'coach_type' => 'AC',
            ],
            [
                'bus_name' => 'Shyamoli',
                'departing_time' => '14:30',
                'coach_no' => 'DH-1002',
                'starting_point' => 'Dhaka',
                'ending_point' => 'Chattogram',
                'fare' => 550,
                'coach_type' => 'Non-AC',
            ],
            [
                'bus_name' => 'Ena Transport',
                'departing_time' => '22:00',
                'coach_no' => 'DH-1003',
                'starting_point' => 'Dhaka',
                'ending_point' => 'Sylhet',
                'fare' => 700,
                'coach_type' => 'AC',
            ],
            [
                'bus_name' => 'Hanif Enterprise',
                'departing_time' => '23:30',
                'coach_no' => 'CH-2001',
                'starting_point' => 'Chattogram',
                'ending_point' => 'Dhaka',
                'fare' => 680,
                'coach_type' => 'AC',
            ],
            [
                'bus_name' => 'Shohagh Paribahan',
                'departing_time' => '07:15',
                'coach_no' => 'DH-1004',
                'starting_point' => 'Dhaka',
                'ending_point' => 'Khulna',
                'fare' => 800,
                'coach_type' => 'AC',
            ],
            [
                'bus_name' => 'Saint Martin Paribahan',
                'departing_time' => '20:30',
                'coach_no' => 'DH-1005',
                'starting_point' => 'Dhaka',
                'ending_point' => "Cox's Bazar",
                'fare' => 1200,
                'coach_type' => 'AC',
            ],
            [
                'bus_name' => 'Desh Travels',
                'departing_time' => '09:45',
                'coach_no' => 'DH-1006',
                'starting_point' => 'Dhaka',
                'ending_point' => 'Rajshahi',
                'fare' => 750,
                'coach_type' => 'AC',
            ],
        ];

        foreach ($templates as $template) {
            $values = $template + [
                'seats_available' => 40,
                'view' => str_repeat('0', 40),
                'updated_at' => now(),
            ];

            if (!DB::table('buslists')->where('coach_no', $template['coach_no'])->exists()) {
                $values['created_at'] = now();
            }

            DB::table('buslists')->updateOrInsert(
                ['coach_no' => $template['coach_no']],
                $values
            );
        }

        // Canonicalise the legacy "Chittagong" spelling to "Chattogram" across
        // inventory already materialised on earlier seeds, so the master
        // schedule, the dated buses, the popular-route chips and the search
        // form all agree on one spelling. Idempotent: a re-run matches nothing.
        foreach (['buslists', 'buses'] as $table) {
            DB::table($table)->where('starting_point', 'Chittagong')
                ->update(['starting_point' => 'Chattogram']);
            DB::table($table)->where('ending_point', 'Chittagong')
                ->update(['ending_point' => 'Chattogram']);
        }

        $this->call(BulkBusSeeder::class);

        // The schedule changed; drop the memoised route aggregates so the home
        // page reflects the new routes on the next request instead of after TTL.
        RouteInsights::flush();
    }
}
