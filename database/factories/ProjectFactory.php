<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    protected $model = Project::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->words(3, true),
            'description' => fake()->paragraph(),
            'status' => fake()->randomElement(ProjectStatus::cases()),
        ];
    }

    public function active(): static
    {
        return $this->state(fn () => [
            'status' => ProjectStatus::ACTIVE,
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'status' => ProjectStatus::COMPLETED,
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn () => [
            'status' => ProjectStatus::ARCHIVED,
        ]);
    }
}
