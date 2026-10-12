<?php

namespace Database\Factories;

use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => rtrim(fake()->unique()->sentence(3), '.'),
            'summary' => fake()->sentence(18),
            'body' => implode("\n\n", fake()->paragraphs(4)),
            'client' => fake()->company(),
            'role' => fake()->randomElement(['Lead developer', 'Full-stack developer', 'Frontend developer', 'Backend developer']),
            'year' => fake()->numberBetween(2019, (int) date('Y')),
            'live_url' => fake()->url(),
            'repo_url' => null,
            'is_featured' => false,
            'is_published' => true,
            'sort_order' => 0,
        ];
    }

    public function featured(): static
    {
        return $this->state(fn () => ['is_featured' => true]);
    }

    public function unpublished(): static
    {
        return $this->state(fn () => ['is_published' => false]);
    }
}
