<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TimetablePeriod;

class TimetablePeriodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $periods = [
            [
                'name' => 'Period 1',
                'period_number' => 1,
                'start_time' => '08:00 AM',
                'end_time' => '08:45 AM',
                'is_break' => false,
                'order' => 1,
            ],
            [
                'name' => 'Period 2',
                'period_number' => 2,
                'start_time' => '08:45 AM',
                'end_time' => '09:30 AM',
                'is_break' => false,
                'order' => 2,
            ],
            [
                'name' => 'Period 3',
                'period_number' => 3,
                'start_time' => '09:30 AM',
                'end_time' => '10:15 AM',
                'is_break' => false,
                'order' => 3,
            ],
            [
                'name' => 'Period 4',
                'period_number' => 4,
                'start_time' => '10:15 AM',
                'end_time' => '11:00 AM',
                'is_break' => false,
                'order' => 4,
            ],
            [
                'name' => 'School Break',
                'period_number' => null,
                'start_time' => '12:00 PM',
                'end_time' => '01:00 PM',
                'is_break' => true,
                'order' => 5,
            ],
            [
                'name' => 'Period 5',
                'period_number' => 5,
                'start_time' => '12:30 PM',
                'end_time' => '01:15 PM',
                'is_break' => false,
                'order' => 6,
            ],
            [
                'name' => 'Period 6',
                'period_number' => 6,
                'start_time' => '01:15 PM',
                'end_time' => '02:00 PM',
                'is_break' => false,
                'order' => 7,
            ],
        ];

        foreach ($periods as $period) {
            TimetablePeriod::firstOrCreate(
                ['name' => $period['name']],
                $period
            );
        }
    }
}
