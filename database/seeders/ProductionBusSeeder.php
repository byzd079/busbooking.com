<?php

namespace Database\Seeders;

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
                'ending_point' => 'Chittagong',
                'fare' => 650,
                'coach_type' => 'AC',
            ],
            [
                'bus_name' => 'Shyamoli',
                'departing_time' => '14:30',
                'coach_no' => 'DH-1002',
                'starting_point' => 'Dhaka',
                'ending_point' => 'Chittagong',
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
                'starting_point' => 'Chittagong',
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

        $this->call(BulkBusSeeder::class);
    }
}
