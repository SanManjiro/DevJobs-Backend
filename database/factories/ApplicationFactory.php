<?php

namespace Database\Factories;

use App\Models\Application;
use App\Models\JobListing;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Application>
 */
class ApplicationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'developer_id' => User::factory(),
            'job_id'       => JobListing::factory(),
            'cover_letter' => fake()->paragraph(4),
            'status'       => fake()->randomElement(['pending', 'viewed', 'accepted', 'rejected']),
        ];
    }
}
