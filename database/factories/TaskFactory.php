<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    protected $model = Task::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $status = fake()->randomElement(TaskStatus::cases());

        return [
            'project_id' => Project::factory(),
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'status' => $status,
            'priority' => fake()->randomElement(TaskPriority::cases()),
            'due_date' => fake()->optional()->dateTimeBetween('+1 day', '+1 month')?->format('Y-m-d'),
            'completed_at' => $status === TaskStatus::COMPLETED ? now() : null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => [
            'status' => TaskStatus::PENDING,
            'completed_at' => null,
        ]);
    }

    public function inProgress(): static
    {
        return $this->state(fn () => [
            'status' => TaskStatus::IN_PROGRESS,
            'completed_at' => null,
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'status' => TaskStatus::COMPLETED,
            'completed_at' => now(),
        ]);
    }

    public function highPriority(): static
    {
        return $this->state(fn () => [
            'priority' => TaskPriority::HIGH,
        ]);
    }
}
