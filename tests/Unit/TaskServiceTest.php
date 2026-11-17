<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Services\TaskService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskServiceTest extends TestCase
{
    use RefreshDatabase;

    protected TaskService $taskService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->taskService = new TaskService();
    }

    public function test_updating_status_to_completed_populates_completed_at(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);
        $task = Task::factory()->create([
            'project_id' => $project->id,
            'status' => TaskStatus::PENDING,
            'completed_at' => null,
        ]);

        $updatedTask = $this->taskService->updateStatus($task, TaskStatus::COMPLETED);

        $this->assertSame(TaskStatus::COMPLETED, $updatedTask->status);
        $this->assertNotNull($updatedTask->completed_at);
    }

    public function test_reopening_a_completed_task_clears_completed_at(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);
        $task = Task::factory()->create([
            'project_id' => $project->id,
            'status' => TaskStatus::COMPLETED,
            'completed_at' => now(),
        ]);

        $updatedTask = $this->taskService->updateStatus($task, TaskStatus::IN_PROGRESS);

        $this->assertSame(TaskStatus::IN_PROGRESS, $updatedTask->status);
        $this->assertNull($updatedTask->completed_at);

        $reopenedPending = $this->taskService->updateStatus($updatedTask, TaskStatus::PENDING);
        $this->assertSame(TaskStatus::PENDING, $reopenedPending->status);
        $this->assertNull($reopenedPending->completed_at);
    }

    public function test_creating_task_with_completed_status_sets_completed_at(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);

        $task = $this->taskService->createTask($project, [
            'title' => 'Immediately Completed Task',
            'status' => TaskStatus::COMPLETED,
        ]);

        $this->assertSame(TaskStatus::COMPLETED, $task->status);
        $this->assertNotNull($task->completed_at);
    }
}
