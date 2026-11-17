<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\ProjectStatus;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Primary Demo User
        $demoUser = User::firstOrCreate(
            ['email' => 'demo@workboard.test'],
            [
                'name' => 'Alex Morgan',
                'password' => Hash::make('Password123!'),
                'email_verified_at' => now(),
            ]
        );

        // Secondary User for authorization/IDOR verification
        $otherUser = User::firstOrCreate(
            ['email' => 'sarah@workboard.test'],
            [
                'name' => 'Sarah Connor',
                'password' => Hash::make('Password123!'),
                'email_verified_at' => now(),
            ]
        );

        // Projects for Demo User
        $project1 = Project::create([
            'user_id' => $demoUser->id,
            'name' => 'Customer Portal Redesign',
            'description' => 'Overhaul the customer portal frontend with modern Tailwind UI and reactive components.',
            'status' => ProjectStatus::ACTIVE,
        ]);

        $project2 = Project::create([
            'user_id' => $demoUser->id,
            'name' => 'API Gateway v2 Migration',
            'description' => 'Migrate legacy microservices to unified REST endpoints with Sanctum token authentication.',
            'status' => ProjectStatus::ACTIVE,
        ]);

        $project3 = Project::create([
            'user_id' => $demoUser->id,
            'name' => 'Q4 Infrastructure Audit',
            'description' => 'Security, cost optimization, and database index tuning for production clusters.',
            'status' => ProjectStatus::COMPLETED,
        ]);

        // Tasks for Project 1
        Task::create([
            'project_id' => $project1->id,
            'title' => 'Wireframe user authentication flow',
            'description' => 'Design high-fidelity Figma mockups for login, registration, and password recovery.',
            'status' => TaskStatus::COMPLETED,
            'priority' => TaskPriority::HIGH,
            'due_date' => now()->subDays(2)->toDateString(),
            'completed_at' => now()->subDays(2),
        ]);

        Task::create([
            'project_id' => $project1->id,
            'title' => 'Implement dashboard analytics charts',
            'description' => 'Integrate Chart.js widgets for metrics and active user statistics.',
            'status' => TaskStatus::IN_PROGRESS,
            'priority' => TaskPriority::MEDIUM,
            'due_date' => now()->addDays(5)->toDateString(),
            'completed_at' => null,
        ]);

        Task::create([
            'project_id' => $project1->id,
            'title' => 'Conduct cross-browser usability testing',
            'description' => 'Verify compatibility on Safari, Chrome, Firefox, and mobile viewports.',
            'status' => TaskStatus::PENDING,
            'priority' => TaskPriority::LOW,
            'due_date' => now()->addDays(12)->toDateString(),
            'completed_at' => null,
        ]);

        // Tasks for Project 2
        Task::create([
            'project_id' => $project2->id,
            'title' => 'Implement rate limiting and token revocation',
            'description' => 'Configure Redis throttle headers and Sanctum token revocation on logout.',
            'status' => TaskStatus::IN_PROGRESS,
            'priority' => TaskPriority::HIGH,
            'due_date' => now()->addDays(3)->toDateString(),
            'completed_at' => null,
        ]);

        Task::create([
            'project_id' => $project2->id,
            'title' => 'Publish OpenAPI 3.0 documentation',
            'description' => 'Generate interactive Swagger / OpenAPI schema for third-party consumers.',
            'status' => TaskStatus::PENDING,
            'priority' => TaskPriority::MEDIUM,
            'due_date' => now()->addDays(10)->toDateString(),
            'completed_at' => null,
        ]);

        // Projects & Tasks for Second User (Sarah)
        $sarahProject = Project::create([
            'user_id' => $otherUser->id,
            'name' => 'Internal Security Scanner',
            'description' => 'Automated vulnerability scanning pipeline.',
            'status' => ProjectStatus::ACTIVE,
        ]);

        Task::create([
            'project_id' => $sarahProject->id,
            'title' => 'Audit Docker base image CVEs',
            'description' => 'Run Trivy scanning on production container definitions.',
            'status' => TaskStatus::PENDING,
            'priority' => TaskPriority::HIGH,
            'due_date' => now()->addDays(7)->toDateString(),
            'completed_at' => null,
        ]);
    }
}
