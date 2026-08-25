<?php

namespace Database\Factories;

use App\Models\CompanyProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CompanyProfile>
 */
class CompanyProfileFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id'     => User::factory()->company(),
            'description' => fake()->paragraph(3),
            'industry'    => fake()->randomElement([
                'Fintech', 'Mobility', 'Talent', 'Retail Tech', 'Payments', 'Healthtech',
            ]),
            'website'     => 'https://'.fake()->unique()->domainName(),
            'logo_path'   => null,
            'country'     => fake()->randomElement([
                'Senegal', 'Nigeria', 'Kenya', 'Ghana', 'Algeria', 'Rwanda',
            ]),
            'size'        => fake()->randomElement(['startup', 'pme', 'grande_entreprise']),
        ];
    }
}
