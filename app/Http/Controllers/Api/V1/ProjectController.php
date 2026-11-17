<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Project\StoreProjectRequest;
use App\Http\Requests\Project\UpdateProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class ProjectController extends Controller
{
    use ApiResponse;

    /**
     * Display a paginated listing of the authenticated user's projects.
     */
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Project::class);

        $query = $request->user()->projects()->withCount('tasks');

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('search')) {
            $searchTerm = (string) $request->query('search');
            $query->where('name', 'like', "%{$searchTerm}%");
        }

        $perPage = (int) $request->query('per_page', 15);
        $perPage = max(1, min($perPage, 100));

        $projects = $query->latest()->paginate($perPage);

        return $this->paginatedResponse($projects, ProjectResource::class, 'Projects retrieved successfully');
    }

    /**
     * Store a newly created project for the authenticated user.
     */
    public function store(StoreProjectRequest $request): JsonResponse
    {
        Gate::authorize('create', Project::class);

        $project = $request->user()->projects()->create($request->validated());

        return $this->successResponse(
            new ProjectResource($project),
            'Project created successfully',
            Response::HTTP_CREATED
        );
    }

    /**
     * Display the specified project after policy authorization.
     */
    public function show(Project $project): JsonResponse
    {
        Gate::authorize('view', $project);

        $project->loadCount('tasks');

        return $this->successResponse(new ProjectResource($project), 'Project retrieved successfully');
    }

    /**
     * Update the specified project.
     */
    public function update(UpdateProjectRequest $request, Project $project): JsonResponse
    {
        Gate::authorize('update', $project);

        $project->update($request->validated());

        return $this->successResponse(new ProjectResource($project), 'Project updated successfully');
    }

    /**
     * Remove the specified project from storage (soft delete).
     */
    public function destroy(Project $project): JsonResponse
    {
        Gate::authorize('delete', $project);

        $project->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
