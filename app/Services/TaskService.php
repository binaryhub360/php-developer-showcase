<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TaskService
{
    /**
     * Create a new task within a project, managing initial completion state.
     *
     * @param array<string, mixed> $data
     */
    public function createTask(Project $project, array $data): Task
    {
        return DB::transaction(function () use ($project, $data): Task {
            $status = isset($data['status'])
                ? ($data['status'] instanceof TaskStatus ? $data['status'] : TaskStatus::from((string) $data['status']))
                : TaskStatus::PENDING;

            $data['status'] = $status;

            if ($status === TaskStatus::COMPLETED && !isset($data['completed_at'])) {
                $data['completed_at'] = Carbon::now();
            }

            return $project->tasks()->create($data);
        });
    }

    /**
     * Update general task attributes while maintaining status completion logic.
     *
     * @param array<string, mixed> $data
     */
    public function updateTask(Task $task, array $data): Task
    {
        return DB::transaction(function () use ($task, $data): Task {
            if (isset($data['status'])) {
                $newStatus = $data['status'] instanceof TaskStatus
                    ? $data['status']
                    : TaskStatus::from((string) $data['status']);

                $data['status'] = $newStatus;

                if ($newStatus === TaskStatus::COMPLETED && $task->status !== TaskStatus::COMPLETED) {
                    $data['completed_at'] = Carbon::now();
                } elseif ($newStatus !== TaskStatus::COMPLETED && $task->status === TaskStatus::COMPLETED) {
                    $data['completed_at'] = null;
                }
            }

            $task->update($data);

            return $task->fresh();
        });
    }

    /**
     * Change a task's status and automatically maintain completed_at timestamp.
     */
    public function updateStatus(Task $task, TaskStatus $status): Task
    {
        return DB::transaction(function () use ($task, $status): Task {
            $task->status = $status;

            if ($status->isCompleted()) {
                $task->completed_at = Carbon::now();
            } else {
                $task->completed_at = null;
            }

            $task->save();

            return $task->fresh();
        });
    }
}
