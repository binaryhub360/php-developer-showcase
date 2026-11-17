<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class ProjectTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_a_project(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $payload = [
            'name' => 'Mobile App Rebuild',
            'description' => 'Rebuilding the mobile app with Flutter.',
            'status' => 'active',
        ];

        $response = $this->postJson('/api/v1/projects', $payload);

        $response->assertStatus(Response::HTTP_CREATED)
            ->assertJson([
                'success' => true,
                'message' => 'Project created successfully',
                'data' => [
                    'name' => 'Mobile App Rebuild',
                    'description' => 'Rebuilding the mobile app with Flutter.',
                    'status' => 'active',
                    'user_id' => $user->id,
                ],
            ]);

        $this->assertDatabaseHas('projects', [
            'name' => 'Mobile App Rebuild',
            'user_id' => $user->id,
        ]);
    }

    public function test_user_can_list_their_projects(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        Project::factory()->count(3)->create(['user_id' => $user->id]);
        Project::factory()->count(2)->create(['user_id' => $otherUser->id]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/projects');

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => ['id', 'user_id', 'name', 'description', 'status', 'created_at', 'updated_at'],
                ],
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ])
            ->assertJson([
                'meta' => [
                    'total' => 3,
                ],
            ]);
    }

    public function test_user_can_retrieve_their_own_project(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $response = $this->getJson("/api/v1/projects/{$project->id}");

        $response->assertStatus(Response::HTTP_OK)
            ->assertJson([
                'success' => true,
                'message' => 'Project retrieved successfully',
                'data' => [
                    'id' => $project->id,
                    'name' => $project->name,
                ],
            ]);
    }

    public function test_user_can_update_their_own_project(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $user->id,
            'name' => 'Old Name',
            'status' => ProjectStatus::ACTIVE,
        ]);

        Sanctum::actingAs($user);

        $response = $this->putJson("/api/v1/projects/{$project->id}", [
            'name' => 'New Updated Name',
            'status' => 'completed',
        ]);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJson([
                'success' => true,
                'message' => 'Project updated successfully',
                'data' => [
                    'id' => $project->id,
                    'name' => 'New Updated Name',
                    'status' => 'completed',
                ],
            ]);

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'name' => 'New Updated Name',
            'status' => 'completed',
        ]);
    }

    public function test_user_can_delete_their_own_project(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $response = $this->deleteJson("/api/v1/projects/{$project->id}");

        $response->assertStatus(Response::HTTP_NO_CONTENT);

        $this->assertSoftDeleted('projects', [
            'id' => $project->id,
        ]);
    }

    public function test_user_cannot_access_another_users_project(): void
    {
        $owner = User::factory()->create();
        $attacker = User::factory()->create();

        $project = Project::factory()->create(['user_id' => $owner->id]);

        Sanctum::actingAs($attacker);

        // Attempt GET
        $this->getJson("/api/v1/projects/{$project->id}")
            ->assertStatus(Response::HTTP_FORBIDDEN)
            ->assertJson([
                'success' => false,
                'message' => 'This action is unauthorized.',
            ]);

        // Attempt PUT
        $this->putJson("/api/v1/projects/{$project->id}", ['name' => 'Hacked Project'])
            ->assertStatus(Response::HTTP_FORBIDDEN);

        // Attempt DELETE
        $this->deleteJson("/api/v1/projects/{$project->id}")
            ->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_invalid_project_data_returns_validation_errors(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/projects', [
            'name' => '',
            'status' => 'invalid_status_enum',
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['name', 'status']);
    }

    public function test_project_filtering_and_pagination_work(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        Project::factory()->count(5)->create([
            'user_id' => $user->id,
            'status' => ProjectStatus::ACTIVE,
            'name' => 'Backend Feature',
        ]);

        Project::factory()->count(2)->create([
            'user_id' => $user->id,
            'status' => ProjectStatus::COMPLETED,
            'name' => 'Frontend Feature',
        ]);

        // Filter by status
        $statusResponse = $this->getJson('/api/v1/projects?status=active');
        $statusResponse->assertStatus(Response::HTTP_OK)
            ->assertJson([
                'meta' => [
                    'total' => 5,
                ],
            ]);

        // Search by name
        $searchResponse = $this->getJson('/api/v1/projects?search=Frontend');
        $searchResponse->assertStatus(Response::HTTP_OK)
            ->assertJson([
                'meta' => [
                    'total' => 2,
                ],
            ]);

        // Pagination limit
        $pagedResponse = $this->getJson('/api/v1/projects?per_page=3');
        $pagedResponse->assertStatus(Response::HTTP_OK)
            ->assertJson([
                'meta' => [
                    'per_page' => 3,
                    'total' => 7,
                    'last_page' => 3,
                ],
            ]);
    }
}
