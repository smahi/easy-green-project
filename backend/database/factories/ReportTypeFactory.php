<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ReportType>
 */
class ReportTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => [
                'en' => fake()->word(),
                'ar' => fake('ar_SA')->word(),
                'fr' => fake('fr_FR')->word(),
            ],
            'description' => [
                'en' => fake()->sentence(),
                'ar' => fake('ar_SA')->sentence(),
                'fr' => fake('fr_FR')->sentence(),
            ],
            'severity_level' => fake()->numberBetween(1, 5),
            'color' => fake()->hexColor(),
            'icon' => null,
            'is_active' => true,
        ];
    }
}
