<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\ProjectStatus;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use PHPUnit\Framework\TestCase;

class EnumTest extends TestCase
{
    public function test_project_status_cases_and_values(): void
    {
        $expected = ['active', 'completed', 'archived'];
        $this->assertSame($expected, ProjectStatus::values());
        $this->assertSame('active', ProjectStatus::ACTIVE->value);
        $this->assertSame('completed', ProjectStatus::COMPLETED->value);
        $this->assertSame('archived', ProjectStatus::ARCHIVED->value);
    }

    public function test_task_status_cases_and_values(): void
    {
        $expected = ['pending', 'in_progress', 'completed'];
        $this->assertSame($expected, TaskStatus::values());
        $this->assertTrue(TaskStatus::COMPLETED->isCompleted());
        $this->assertFalse(TaskStatus::PENDING->isCompleted());
        $this->assertFalse(TaskStatus::IN_PROGRESS->isCompleted());
    }

    public function test_task_priority_cases_and_values(): void
    {
        $expected = ['low', 'medium', 'high'];
        $this->assertSame($expected, TaskPriority::values());
        $this->assertSame('low', TaskPriority::LOW->value);
        $this->assertSame('medium', TaskPriority::MEDIUM->value);
        $this->assertSame('high', TaskPriority::HIGH->value);
    }
}
