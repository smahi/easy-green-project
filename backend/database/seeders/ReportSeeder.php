<?php

namespace Database\Seeders;

use App\Models\Report;
use App\Models\ReportType;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReportSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure we have some users
        if (User::count() < 5) {
            User::factory(5)->create();
        }
        $users = User::all();

        // Ensure we have some report types
        if (ReportType::count() < 3) {
            ReportType::factory(3)->create();
        }
        $reportTypes = ReportType::all();

        $locations = [
            ['lat' => 30.5858, 'lng' => 2.8756, 'name' => 'El Menia (Golea)'],
            ['lat' => 27.8742, 'lng' => -0.2939, 'name' => 'Adrar'],
            ['lat' => 31.9493, 'lng' => 5.3250, 'name' => 'Ouargla'],
        ];

        // Create 10 reports
        for ($i = 0; $i < 10; $i++) {
            $location = $locations[array_rand($locations)];
            // Add small jitter to coordinates
            $lat = $location['lat'] + (rand(-100, 100) / 10000);
            $lng = $location['lng'] + (rand(-100, 100) / 10000);

            Report::factory()->create([
                'user_id' => $users->random()->id,
                'report_type_id' => $reportTypes->random()->id,
                'latitude' => $lat,
                'longitude' => $lng,
                'description' => "Report from {$location['name']} area. " . fake()->sentence(),
                'created_at' => now()->subDays(rand(0, 30)),
                'updated_at' => now()->subDays(rand(0, 30)),
            ]);
        }
    }
}

