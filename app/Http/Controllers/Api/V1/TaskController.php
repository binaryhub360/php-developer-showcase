<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\TaskStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Task\StoreTaskRequest;
use App\Http\Requests\Task\UpdateTaskRequest;
use App\Http\Requests\Task\UpdateTaskStatusRequest;
use App\Http\Resources\TaskResource;
use App\Models\Project;
use App\Models\Task;
use App\Services\TaskService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class TaskController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected readonly TaskService $taskService
    ) {}

    /**
     * Display a listing of tasks under a given project with filtering, search, and sorting.
     */
    public function index(Request $request, Project $project): JsonResponse
    {
        Gate::authorize('view', $project);

        $query = $project->tasks();

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->query('priority'));
        }

        if ($request->filled('due_date')) {
            $query->whereDate('due_date', (string) $request->query('due_date'));
        }

        if ($request->filled('search')) {
            $searchTerm = (string) $request->query('search');
            $query->where('title', 'like', "%{$searchTerm}%");
        }

        $sortField = $request->query('sort_by', 'created_at');
        $allowedSortFields = ['created_at', 'due_date'];
        if (!in_array($sortField, $allowedSortFields, true)) {
            $sortField = 'created_at';
        }

        $direction = strtolower((string) $request->query('sort_direction', 'desc')) === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sortField, $direction);

        $perPage = (int) $request->query('per_page', 15);
        $perPage = max(1, min($perPage, 100));

        $tasks = $query->paginate($perPage);

        return $this->paginatedResponse($tasks, TaskResource::class, 'Tasks retrieved successfully');
    }

    /**
     * Store a newly created task under a specific project using TaskService.
     */
    public function store(StoreTaskRequest $request, Project $project): JsonResponse
    {
        Gate::authorize('update', $project);

        $task = $this->taskService->createTask($project, $request->validated());

        return $this->successResponse(
            new TaskResource($task),
            'Task created successfully',
            Response::HTTP_CREATED
        );
    }

    /**
     * Display the specified task details after authorization.
     */
    public function show(Task $task): JsonResponse
    {
        Gate::authorize('view', $task);

        return $this->successResponse(new TaskResource($task), 'Task retrieved successfully');
    }

    /**
     * Update task details using TaskService.
     */
    public function update(UpdateTaskRequest $request, Task $task): JsonResponse
    {
        Gate::authorize('update', $task);

        $updatedTask = $this->taskService->updateTask($task, $request->validated());

        return $this->successResponse(new TaskResource($updatedTask), 'Task updated successfully');
    }

    /**
     * Update only the status of the specified task and manage completed_at.
     */
    public function updateStatus(UpdateTaskStatusRequest $request, Task $task): JsonResponse
    {
        Gate::authorize('update', $task);

        $status = TaskStatus::from((string) $request->validated('status'));
        $updatedTask = $this->taskService->updateStatus($task, $status);

        return $this->successResponse(new TaskResource($updatedTask), 'Task status updated successfully');
    }

    /**
     * Remove the specified task (soft delete).
     */
    public function destroy(Task $task): JsonResponse
    {
        Gate::authorize('delete', $task);

        $task->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
