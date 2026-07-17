<?php

namespace Database\Factories;

use App\Models\DeveloperProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DeveloperProfile>
 */
class DeveloperProfileFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id'          => User::factory(),
            'bio'              => fake()->paragraph(2),
            'location'         => fake()->randomElement([
                'Dakar, Senegal', 'Lagos, Nigeria', 'Nairobi, Kenya', 'Accra, Ghana',
            ]),
            'github_url'       => 'https://github.com/'.fake()->unique()->userName(),
            'portfolio_url'    => 'https://'.fake()->unique()->domainName(),
            'cv_path'          => null,
            'years_experience' => fake()->numberBetween(0, 15),
        ];
    }
}
