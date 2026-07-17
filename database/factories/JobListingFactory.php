<?php

namespace Database\Factories;

use App\Models\JobListing;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JobListing>
 */
class JobListingFactory extends Factory
{
    public function definition(): array
    {
        $min = fake()->numberBetween(3, 18) * 100_000;

        return [
            'company_id'       => User::factory()->company(),
            'title'            => fake()->randomElement([
                'Senior React Developer', 'Backend Laravel Engineer', 'Mobile Flutter Developer',
                'DevOps Engineer', 'Data Engineer', 'UI/UX Front-End Intern', 'Product Designer',
                'Python Developer', 'Full-Stack Engineer', 'QA Engineer',
            ]),
            'description'      => implode("\n\n", fake()->paragraphs(3)),
            'location'         => fake()->randomElement([
                'Dakar, Senegal', 'Lagos, Nigeria', 'Nairobi, Kenya', 'Casablanca, Morocco', null,
            ]),
            'type'             => fake()->randomElement(['full_time', 'part_time', 'freelance', 'stage']),
            'remote'           => fake()->randomElement(['on_site', 'remote', 'hybrid']),
            'salary_min'       => $min,
            'salary_max'       => $min + fake()->numberBetween(2, 8) * 100_000,
            'experience_level' => fake()->randomElement(['junior', 'intermediaire', 'senior']),
            'status'           => 'published',
            'expires_at'       => null,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => ['status' => 'draft']);
    }

    /** Offre publiée mais périmée : doit rester invisible du public. */
    public function expired(): static
    {
        return $this->state(fn () => [
            'status'     => 'published',
            'expires_at' => now()->subDays(fake()->numberBetween(1, 30)),
        ]);
    }

    /** Salaire non communiqué — la colonne est nullable, le front doit tenir. */
    public function withoutSalary(): static
    {
        return $this->state(fn () => ['salary_min' => null, 'salary_max' => null]);
    }
}
