<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class TaskTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_a_task_inside_their_project(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $payload = [
            'title' => 'Design database schema',
            'description' => 'Create ERD diagram and migration plan.',
            'status' => 'pending',
            'priority' => 'high',
            'due_date' => '2026-12-31',
        ];

        $response = $this->postJson("/api/v1/projects/{$project->id}/tasks", $payload);

        $response->assertStatus(Response::HTTP_CREATED)
            ->assertJson([
                'success' => true,
                'message' => 'Task created successfully',
                'data' => [
                    'project_id' => $project->id,
                    'title' => 'Design database schema',
                    'status' => 'pending',
                    'priority' => 'high',
                    'due_date' => '2026-12-31',
                    'completed_at' => null,
                ],
            ]);

        $this->assertDatabaseHas('tasks', [
            'project_id' => $project->id,
            'title' => 'Design database schema',
        ]);
    }

    public function test_user_can_list_project_tasks(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);
        Task::factory()->count(4)->create(['project_id' => $project->id]);

        Sanctum::actingAs($user);

        $response = $this->getJson("/api/v1/projects/{$project->id}/tasks");

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => ['id', 'project_id', 'title', 'status', 'priority', 'due_date', 'completed_at'],
                ],
                'meta' => ['total', 'per_page', 'current_page', 'last_page'],
            ])
            ->assertJson([
                'meta' => [
                    'total' => 4,
                ],
            ]);
    }

    public function test_user_can_update_a_task(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);
        $task = Task::factory()->create([
            'project_id' => $project->id,
            'title' => 'Initial Title',
            'priority' => TaskPriority::LOW,
        ]);

        Sanctum::actingAs($user);

        $response = $this->putJson("/api/v1/tasks/{$task->id}", [
            'title' => 'Updated Task Title',
            'priority' => 'high',
        ]);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJson([
                'success' => true,
                'message' => 'Task updated successfully',
                'data' => [
                    'id' => $task->id,
                    'title' => 'Updated Task Title',
                    'priority' => 'high',
                ],
            ]);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => 'Updated Task Title',
            'priority' => 'high',
        ]);
    }

    public function test_user_can_delete_a_task(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);
        $task = Task::factory()->create(['project_id' => $project->id]);

        Sanctum::actingAs($user);

        $response = $this->deleteJson("/api/v1/tasks/{$task->id}");

        $response->assertStatus(Response::HTTP_NO_CONTENT);

        $this->assertSoftDeleted('tasks', [
            'id' => $task->id,
        ]);
    }

    public function test_user_cannot_access_another_users_task(): void
    {
        $owner = User::factory()->create();
        $attacker = User::factory()->create();

        $project = Project::factory()->create(['user_id' => $owner->id]);
        $task = Task::factory()->create(['project_id' => $project->id]);

        Sanctum::actingAs($attacker);

        // Cannot view
        $this->getJson("/api/v1/tasks/{$task->id}")
            ->assertStatus(Response::HTTP_FORBIDDEN);

        // Cannot update
        $this->putJson("/api/v1/tasks/{$task->id}", ['title' => 'Hijacked Title'])
            ->assertStatus(Response::HTTP_FORBIDDEN);

        // Cannot change status
        $this->patchJson("/api/v1/tasks/{$task->id}/status", ['status' => 'completed'])
            ->assertStatus(Response::HTTP_FORBIDDEN);

        // Cannot delete
        $this->deleteJson("/api/v1/tasks/{$task->id}")
            ->assertStatus(Response::HTTP_FORBIDDEN);

        // Cannot add task to another user's project
        $this->postJson("/api/v1/projects/{$project->id}/tasks", [
            'title' => 'Malicious Task',
        ])->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_task_filtering_works(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);

        Task::factory()->create([
            'project_id' => $project->id,
            'status' => TaskStatus::PENDING,
            'priority' => TaskPriority::HIGH,
            'due_date' => '2026-06-01',
            'title' => 'High Priority Searchable Task',
        ]);

        Task::factory()->create([
            'project_id' => $project->id,
            'status' => TaskStatus::COMPLETED,
            'priority' => TaskPriority::LOW,
            'due_date' => '2026-07-01',
            'title' => 'Low Priority Other Task',
        ]);

        Sanctum::actingAs($user);

        // Filter by status
        $this->getJson("/api/v1/projects/{$project->id}/tasks?status=pending")
            ->assertStatus(Response::HTTP_OK)
            ->assertJson(['meta' => ['total' => 1]]);

        // Filter by priority
        $this->getJson("/api/v1/projects/{$project->id}/tasks?priority=high")
            ->assertStatus(Response::HTTP_OK)
            ->assertJson(['meta' => ['total' => 1]]);

        // Filter by due_date
        $this->getJson("/api/v1/projects/{$project->id}/tasks?due_date=2026-06-01")
            ->assertStatus(Response::HTTP_OK)
            ->assertJson(['meta' => ['total' => 1]]);

        // Search by title
        $this->getJson("/api/v1/projects/{$project->id}/tasks?search=Searchable")
            ->assertStatus(Response::HTTP_OK)
            ->assertJson(['meta' => ['total' => 1]]);
    }

    public function test_task_pagination_works(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);
        Task::factory()->count(10)->create(['project_id' => $project->id]);

        Sanctum::actingAs($user);

        $response = $this->getJson("/api/v1/projects/{$project->id}/tasks?per_page=4");

        $response->assertStatus(Response::HTTP_OK)
            ->assertJson([
                'meta' => [
                    'current_page' => 1,
                    'per_page' => 4,
                    'total' => 10,
                    'last_page' => 3,
                ],
            ]);
    }

    public function test_completed_tasks_receive_completed_at(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);
        $task = Task::factory()->create([
            'project_id' => $project->id,
            'status' => TaskStatus::PENDING,
            'completed_at' => null,
        ]);

        Sanctum::actingAs($user);

        $response = $this->patchJson("/api/v1/tasks/{$task->id}/status", [
            'status' => 'completed',
        ]);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => 'completed',
                ],
            ]);

        $task->refresh();
        $this->assertSame(TaskStatus::COMPLETED, $task->status);
        $this->assertNotNull($task->completed_at);
    }

    public function test_reopened_tasks_clear_completed_at(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);
        $task = Task::factory()->create([
            'project_id' => $project->id,
            'status' => TaskStatus::COMPLETED,
            'completed_at' => now(),
        ]);

        Sanctum::actingAs($user);

        $response = $this->patchJson("/api/v1/tasks/{$task->id}/status", [
            'status' => 'in_progress',
        ]);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => 'in_progress',
                    'completed_at' => null,
                ],
            ]);

        $task->refresh();
        $this->assertSame(TaskStatus::IN_PROGRESS, $task->status);
        $this->assertNull($task->completed_at);
    }

    public function test_invalid_task_data_returns_validation_errors(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/projects/{$project->id}/tasks", [
            'title' => '',
            'status' => 'not_a_valid_status',
            'priority' => 'not_a_valid_priority',
            'due_date' => 'invalid-date',
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['title', 'status', 'priority', 'due_date']);
    }
}
