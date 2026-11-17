# WorkBoard API – REST Reference (v1)

Base URL: `http://localhost:8000/api/v1`

All responses follow a consistent JSON envelope:
- **Resource**: `{ "success": true, "message": "...", "data": { ... } }`
- **Collection**: `{ "success": true, "message": "...", "data": [ ... ], "meta": { "current_page": 1, "last_page": 1, "per_page": 15, "total": 0 } }`
- **Error / Validation**: `{ "success": false, "message": "...", "errors": { ... } }`

---

## 1. Authentication Endpoints

### 1.1 Register User
- **Method**: `POST`
- **URL**: `/api/v1/register`
- **Headers**: `Content-Type: application/json`, `Accept: application/json`

**Request Body**:
```json
{
  "name": "Alex Morgan",
  "email": "alex@example.com",
  "password": "Password123!",
  "password_confirmation": "Password123!"
}
```

**Response (`201 Created`)**:
```json
{
  "success": true,
  "message": "User registered successfully",
  "data": {
    "user": {
      "id": 1,
      "name": "Alex Morgan",
      "email": "alex@example.com",
      "created_at": "2026-11-16T08:00:00Z",
      "updated_at": "2026-11-16T08:00:00Z"
    },
    "token": "1|q6Wc5v8...plainTextToken",
    "token_type": "Bearer"
  }
}
```

---

### 1.2 Login User
- **Method**: `POST`
- **URL**: `/api/v1/login`

**Request Body**:
```json
{
  "email": "alex@example.com",
  "password": "Password123!"
}
```

**Response (`200 OK`)**:
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "user": {
      "id": 1,
      "name": "Alex Morgan",
      "email": "alex@example.com",
      "created_at": "2026-11-16T08:00:00Z",
      "updated_at": "2026-11-16T08:00:00Z"
    },
    "token": "2|h92jKl...plainTextToken",
    "token_type": "Bearer"
  }
}
```

---

### 1.3 Get Current Profile
- **Method**: `GET`
- **URL**: `/api/v1/me`
- **Headers**: `Authorization: Bearer <TOKEN>`

**Response (`200 OK`)**:
```json
{
  "success": true,
  "message": "Profile retrieved successfully",
  "data": {
    "id": 1,
    "name": "Alex Morgan",
    "email": "alex@example.com",
    "created_at": "2026-11-16T08:00:00Z",
    "updated_at": "2026-11-16T08:00:00Z"
  }
}
```

---

### 1.4 Logout
- **Method**: `POST`
- **URL**: `/api/v1/logout`
- **Headers**: `Authorization: Bearer <TOKEN>`

**Response (`200 OK`)**:
```json
{
  "success": true,
  "message": "Logged out successfully",
  "data": null
}
```

---

## 2. Project Management Endpoints

All project endpoints require the `Authorization: Bearer <TOKEN>` header.

### 2.1 List Projects
- **Method**: `GET`
- **URL**: `/api/v1/projects`
- **Query Parameters**:
  - `status`: Filter by status (`active`, `completed`, `archived`)
  - `search`: Case-insensitive search on project name
  - `per_page`: Number of items per page (default: 15, max: 100)
  - `page`: Page number

**Response (`200 OK`)**:
```json
{
  "success": true,
  "message": "Projects retrieved successfully",
  "data": [
    {
      "id": 1,
      "user_id": 1,
      "name": "Customer Portal Redesign",
      "description": "Overhaul the customer portal frontend.",
      "status": "active",
      "tasks_count": 3,
      "created_at": "2026-11-16T08:00:00Z",
      "updated_at": "2026-11-16T08:00:00Z",
      "deleted_at": null
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 15,
    "total": 1
  }
}
```

---

### 2.2 Create Project
- **Method**: `POST`
- **URL**: `/api/v1/projects`

**Request Body**:
```json
{
  "name": "Mobile Application v2",
  "description": "Rebuild mobile application using React Native.",
  "status": "active"
}
```

**Response (`201 Created`)**:
```json
{
  "success": true,
  "message": "Project created successfully",
  "data": {
    "id": 2,
    "user_id": 1,
    "name": "Mobile Application v2",
    "description": "Rebuild mobile application using React Native.",
    "status": "active",
    "tasks_count": null,
    "created_at": "2026-11-16T08:15:00Z",
    "updated_at": "2026-11-16T08:15:00Z",
    "deleted_at": null
  }
}
```

---

### 2.3 Get Project Details
- **Method**: `GET`
- **URL**: `/api/v1/projects/{project_id}`

**Response (`200 OK`)**:
```json
{
  "success": true,
  "message": "Project retrieved successfully",
  "data": {
    "id": 1,
    "user_id": 1,
    "name": "Customer Portal Redesign",
    "description": "Overhaul the customer portal frontend.",
    "status": "active",
    "tasks_count": 3,
    "created_at": "2026-11-16T08:00:00Z",
    "updated_at": "2026-11-16T08:00:00Z",
    "deleted_at": null
  }
}
```

---

### 2.4 Update Project
- **Method**: `PUT`
- **URL**: `/api/v1/projects/{project_id}`

**Request Body**:
```json
{
  "name": "Customer Portal Redesign – Final Stage",
  "status": "completed"
}
```

**Response (`200 OK`)**:
```json
{
  "success": true,
  "message": "Project updated successfully",
  "data": {
    "id": 1,
    "user_id": 1,
    "name": "Customer Portal Redesign – Final Stage",
    "description": "Overhaul the customer portal frontend.",
    "status": "completed",
    "tasks_count": null,
    "created_at": "2026-11-16T08:00:00Z",
    "updated_at": "2026-11-16T08:30:00Z",
    "deleted_at": null
  }
}
```

---

### 2.5 Delete Project
- **Method**: `DELETE`
- **URL**: `/api/v1/projects/{project_id}`

**Response (`204 No Content`)**:
*(Empty response body)*

---

## 3. Task Management Endpoints

### 3.1 List Tasks For a Project
- **Method**: `GET`
- **URL**: `/api/v1/projects/{project_id}/tasks`
- **Query Parameters**:
  - `status`: Filter by status (`pending`, `in_progress`, `completed`)
  - `priority`: Filter by priority (`low`, `medium`, `high`)
  - `due_date`: Filter by date (`YYYY-MM-DD`)
  - `search`: Case-insensitive search on task title
  - `sort_by`: Sort field (`created_at` [default], `due_date`)
  - `sort_direction`: `asc` or `desc` (default: `desc`)
  - `per_page`: Items per page (default: 15)
  - `page`: Page number

**Response (`200 OK`)**:
```json
{
  "success": true,
  "message": "Tasks retrieved successfully",
  "data": [
    {
      "id": 1,
      "project_id": 1,
      "title": "Wireframe user authentication flow",
      "description": "Design high-fidelity Figma mockups.",
      "status": "completed",
      "priority": "high",
      "due_date": "2026-11-14",
      "completed_at": "2026-11-14T17:00:00Z",
      "created_at": "2026-11-10T08:00:00Z",
      "updated_at": "2026-11-14T17:00:00Z",
      "deleted_at": null
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 15,
    "total": 1
  }
}
```

---

### 3.2 Create Task Under Project
- **Method**: `POST`
- **URL**: `/api/v1/projects/{project_id}/tasks`

**Request Body**:
```json
{
  "title": "Implement rate limiting middleware",
  "description": "Configure Redis-backed throttle headers.",
  "status": "pending",
  "priority": "high",
  "due_date": "2026-12-01"
}
```

**Response (`201 Created`)**:
```json
{
  "success": true,
  "message": "Task created successfully",
  "data": {
    "id": 4,
    "project_id": 1,
    "title": "Implement rate limiting middleware",
    "description": "Configure Redis-backed throttle headers.",
    "status": "pending",
    "priority": "high",
    "due_date": "2026-12-01",
    "completed_at": null,
    "created_at": "2026-11-16T08:45:00Z",
    "updated_at": "2026-11-16T08:45:00Z",
    "deleted_at": null
  }
}
```

---

### 3.3 Get Task Details
- **Method**: `GET`
- **URL**: `/api/v1/tasks/{task_id}`

**Response (`200 OK`)**:
```json
{
  "success": true,
  "message": "Task retrieved successfully",
  "data": {
    "id": 4,
    "project_id": 1,
    "title": "Implement rate limiting middleware",
    "description": "Configure Redis-backed throttle headers.",
    "status": "pending",
    "priority": "high",
    "due_date": "2026-12-01",
    "completed_at": null,
    "created_at": "2026-11-16T08:45:00Z",
    "updated_at": "2026-11-16T08:45:00Z",
    "deleted_at": null
  }
}
```

---

### 3.4 Update Task Details
- **Method**: `PUT`
- **URL**: `/api/v1/tasks/{task_id}`

**Request Body**:
```json
{
  "title": "Implement rate limiting middleware with tier support",
  "priority": "medium"
}
```

**Response (`200 OK`)**:
```json
{
  "success": true,
  "message": "Task updated successfully",
  "data": {
    "id": 4,
    "project_id": 1,
    "title": "Implement rate limiting middleware with tier support",
    "description": "Configure Redis-backed throttle headers.",
    "status": "pending",
    "priority": "medium",
    "due_date": "2026-12-01",
    "completed_at": null,
    "created_at": "2026-11-16T08:45:00Z",
    "updated_at": "2026-11-16T09:00:00Z",
    "deleted_at": null
  }
}
```

---

### 3.5 Update Task Status (Transition)
- **Method**: `PATCH`
- **URL**: `/api/v1/tasks/{task_id}/status`

**Request Body**:
```json
{
  "status": "completed"
}
```

**Response (`200 OK`)**:
```json
{
  "success": true,
  "message": "Task status updated successfully",
  "data": {
    "id": 4,
    "project_id": 1,
    "title": "Implement rate limiting middleware with tier support",
    "description": "Configure Redis-backed throttle headers.",
    "status": "completed",
    "priority": "medium",
    "due_date": "2026-12-01",
    "completed_at": "2026-11-16T09:05:00Z",
    "created_at": "2026-11-16T08:45:00Z",
    "updated_at": "2026-11-16T09:05:00Z",
    "deleted_at": null
  }
}
```

*Note: Transitioning to `completed` automatically stamps `completed_at`. Reopening to `pending` or `in_progress` automatically resets `completed_at` to `null`.*

---

### 3.6 Delete Task
- **Method**: `DELETE`
- **URL**: `/api/v1/tasks/{task_id}`

**Response (`204 No Content`)**:
*(Empty response body)*
