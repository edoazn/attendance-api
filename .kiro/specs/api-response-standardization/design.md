# Design Document: API Response Standardization

## Overview

This design implements a comprehensive API response standardization system for the Laravel geolocation-based student attendance application. The system addresses current inconsistencies in response formats, manual array mapping in controllers, and varying error structures by introducing Laravel API Resources, a unified response envelope pattern, centralized exception handling, and reusable response helper utilities.

The design follows Laravel 12 best practices and maintains backward compatibility within the v1 API prefix while establishing a foundation for consistent, maintainable API responses across all endpoints.

### Goals

- Establish consistent response structure across all API endpoints
- Eliminate manual array mapping in controllers through Laravel API Resources
- Implement centralized exception handling with proper HTTP status codes
- Provide reusable response helper utilities for developers
- Maintain backward compatibility for existing API consumers
- Improve API maintainability and developer experience

### Non-Goals

- Changing API versioning strategy (remains v1)
- Modifying authentication mechanism (Sanctum remains)
- Altering database schema or model relationships
- Implementing GraphQL or alternative API paradigms
- Adding new business logic or features beyond response standardization

## Architecture

### High-Level Architecture

The response standardization system consists of four primary layers:

```
┌─────────────────────────────────────────────────────────────┐
│                     API Request Layer                        │
│  (Routes → Middleware → Form Requests → Controllers)         │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────┐
│                  Response Helper Layer                       │
│  (ApiResponse Trait with helper methods)                     │
│  - success()  - error()  - resource()                        │
│  - collection()  - paginated()                               │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────┐
│              Resource Transformation Layer                   │
│  (Laravel API Resources for each model)                      │
│  UserResource, AttendanceResource, ScheduleResource, etc.    │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────┐
│                 Response Envelope Layer                      │
│  (Standardized JSON structure)                               │
│  { success, data, message, meta, links, errors }             │
└─────────────────────────────────────────────────────────────┘
```

### Exception Handling Flow

```
┌──────────────────┐
│   Exception      │
│   Thrown         │
└────────┬─────────┘
         │
         ▼
┌──────────────────────────────────────────────────────────────┐
│              App\Exceptions\Handler                          │
│  (Centralized exception handling)                            │
└────────┬─────────────────────────────────────────────────────┘
         │
         ├─── ModelNotFoundException → 404
         ├─── AuthenticationException → 401
         ├─── AuthorizationException → 403
         ├─── ValidationException → 422
         ├─── ThrottleRequestsException → 429
         └─── Other Exceptions → 500
         │
         ▼
┌──────────────────────────────────────────────────────────────┐
│              Standardized Error Response                     │
│  { success: false, message: "...", errors: {...} }           │
└──────────────────────────────────────────────────────────────┘
```

### Data Flow for Successful Responses

```
Controller Method
      │
      ├─── Calls Service Layer (business logic)
      │
      ├─── Receives Model/Collection
      │
      ├─── Wraps in API Resource
      │         │
      │         ├─── Single Model → ModelResource
      │         ├─── Collection → ModelResource::collection()
      │         └─── Paginated → ModelResource::collection($paginator)
      │
      ├─── Calls Response Helper
      │         │
      │         ├─── success() for simple data
      │         ├─── resource() for single resource
      │         ├─── collection() for resource arrays
      │         └─── paginated() for paginated resources
      │
      └─── Returns JsonResponse with standardized envelope
```

## Components and Interfaces

### 1. Response Envelope Structure

All API responses follow a consistent envelope structure:

**Success Response (Single Resource):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Example",
    "created_at": "2024-01-15T10:30:00Z"
  },
  "message": "Resource retrieved successfully"
}
```

**Success Response (Collection):**
```json
{
  "success": true,
  "data": [
    {"id": 1, "name": "Item 1"},
    {"id": 2, "name": "Item 2"}
  ],
  "message": "Resources retrieved successfully"
}
```

**Success Response (Paginated):**
```json
{
  "success": true,
  "data": [
    {"id": 1, "name": "Item 1"}
  ],
  "meta": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 15,
    "total": 75,
    "from": 1,
    "to": 15
  },
  "links": {
    "first": "http://api.example.com/v1/resource?page=1",
    "last": "http://api.example.com/v1/resource?page=5",
    "prev": null,
    "next": "http://api.example.com/v1/resource?page=2"
  }
}
```

**Error Response (Validation):**
```json
{
  "success": false,
  "message": "The given data was invalid.",
  "errors": {
    "email": ["The email field is required."],
    "password": ["The password must be at least 8 characters."]
  }
}
```

**Error Response (General):**
```json
{
  "success": false,
  "message": "Resource not found"
}
```

### 2. ApiResponse Trait

Location: `app/Http/Traits/ApiResponse.php`

This trait provides reusable response helper methods for all controllers.

**Interface:**

```php
trait ApiResponse
{
    /**
     * Return a success response
     * 
     * @param mixed $data
     * @param string|null $message
     * @param int $status
     * @return JsonResponse
     */
    protected function success($data = null, ?string $message = null, int $status = 200): JsonResponse;

    /**
     * Return an error response
     * 
     * @param string $message
     * @param array $errors
     * @param int $status
     * @return JsonResponse
     */
    protected function error(string $message, array $errors = [], int $status = 400): JsonResponse;

    /**
     * Return a resource response
     * 
     * @param JsonResource $resource
     * @param string|null $message
     * @param int $status
     * @return JsonResponse
     */
    protected function resource(JsonResource $resource, ?string $message = null, int $status = 200): JsonResponse;

    /**
     * Return a collection response
     * 
     * @param ResourceCollection $collection
     * @param string|null $message
     * @param int $status
     * @return JsonResponse
     */
    protected function collection(ResourceCollection $collection, ?string $message = null, int $status = 200): JsonResponse;

    /**
     * Return a paginated response
     * 
     * @param ResourceCollection $collection (must be paginated)
     * @param string|null $message
     * @param int $status
     * @return JsonResponse
     */
    protected function paginated(ResourceCollection $collection, ?string $message = null, int $status = 200): JsonResponse;
}
```

**Usage in Controllers:**

```php
class ExampleController extends Controller
{
    use ApiResponse;

    public function show($id)
    {
        $model = Model::findOrFail($id);
        return $this->resource(new ModelResource($model));
    }

    public function index()
    {
        $models = Model::paginate(15);
        return $this->paginated(ModelResource::collection($models));
    }
}
```

### 3. Laravel API Resources

Each model will have a dedicated API Resource class for data transformation.

**Resource Class Structure:**

Location: `app/Http/Resources/`

```
app/Http/Resources/
├── UserResource.php
├── AttendanceResource.php
├── ScheduleResource.php
├── LocationResource.php
├── ClassRoomResource.php
└── CourseResource.php
```

**Base Resource Pattern:**

```php
namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ModelResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'field_name' => $this->field_name,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            
            // Conditional relationships
            'relationship' => $this->whenLoaded('relationship', 
                fn() => new RelatedResource($this->relationship)
            ),
        ];
    }
}
```

### 4. Resource Specifications

#### UserResource

**Fields:**
- id (integer)
- name (string)
- identity_number (string)
- email (string|null)
- role (string)
- created_at (ISO 8601 timestamp)
- updated_at (ISO 8601 timestamp)
- classes (array, conditional) - Array of ClassRoomResource when loaded

**Relationships:**
- classes: Many-to-many relationship with ClassRoom model

#### AttendanceResource

**Fields:**
- id (integer)
- status (string: "hadir" | "ditolak")
- distance (float) - Distance in meters
- latitude (float)
- longitude (float)
- created_at (ISO 8601 timestamp)
- updated_at (ISO 8601 timestamp)
- user (object, conditional) - UserResource when loaded
- schedule (object, conditional) - ScheduleResource when loaded

**Relationships:**
- user: Belongs to User model
- schedule: Belongs to Schedule model

#### ScheduleResource

**Fields:**
- id (integer)
- day (string)
- start_time (ISO 8601 timestamp)
- end_time (ISO 8601 timestamp)
- created_at (ISO 8601 timestamp)
- updated_at (ISO 8601 timestamp)
- class_room (object, conditional) - ClassRoomResource when loaded
- course (object, conditional) - CourseResource when loaded
- location (object, conditional) - LocationResource when loaded

**Relationships:**
- classRoom: Belongs to ClassRoom model
- course: Belongs to Course model
- location: Belongs to Location model

#### LocationResource

**Fields:**
- id (integer)
- name (string)
- address (string|null)
- latitude (float)
- longitude (float)
- radius (float) - Radius in meters
- created_at (ISO 8601 timestamp)
- updated_at (ISO 8601 timestamp)

#### ClassRoomResource

**Fields:**
- id (integer)
- name (string)
- academic_year (string)
- created_at (ISO 8601 timestamp)
- updated_at (ISO 8601 timestamp)

#### CourseResource

**Fields:**
- id (integer)
- course_code (string)
- course_name (string)
- credits (integer)
- created_at (ISO 8601 timestamp)
- updated_at (ISO 8601 timestamp)

### 5. Exception Handler Modifications

Location: `app/Exceptions/Handler.php`

The exception handler will be modified to catch and format all exceptions into standardized responses.

**Exception Mapping:**

| Exception Type | HTTP Status | Response Format |
|---------------|-------------|-----------------|
| ModelNotFoundException | 404 | `{success: false, message: "Resource not found"}` |
| AuthenticationException | 401 | `{success: false, message: "Unauthenticated"}` |
| AuthorizationException | 403 | `{success: false, message: "Unauthorized"}` |
| ValidationException | 422 | `{success: false, message: "...", errors: {...}}` |
| ThrottleRequestsException | 429 | `{success: false, message: "Too many requests"}` |
| Other Exceptions | 500 | `{success: false, message: "Server error"}` |

**Handler Interface:**

```php
public function render($request, Throwable $exception): Response
{
    // Only format as JSON for API requests
    if ($request->is('api/*')) {
        return $this->handleApiException($request, $exception);
    }
    
    return parent::render($request, $exception);
}

protected function handleApiException($request, Throwable $exception): JsonResponse
{
    // Map exception types to standardized responses
    // Include stack trace only in debug mode
    // Exclude sensitive information in production
}
```

### 6. Controller Refactoring Strategy

Controllers will be refactored to:

1. Use the `ApiResponse` trait
2. Replace manual array mapping with API Resources
3. Remove inline response formatting
4. Delegate data transformation to Resource classes
5. Use helper methods for consistent responses

**Before (Current):**

```php
public function show($id)
{
    $user = User::findOrFail($id);
    
    return response()->json([
        'id' => $user->id,
        'name' => $user->name,
        'email' => $user->email,
    ]);
}
```

**After (Standardized):**

```php
use App\Http\Traits\ApiResponse;
use App\Http\Resources\UserResource;

class UserController extends Controller
{
    use ApiResponse;
    
    public function show($id)
    {
        $user = User::findOrFail($id);
        return $this->resource(new UserResource($user));
    }
}
```

### 7. Endpoint-Specific Transformations

#### Authentication Endpoints

**Login Response:**
```json
{
  "success": true,
  "data": {
    "token": "1|abc123xyz...",
    "user": {
      "id": 1,
      "name": "Budi Santoso",
      "identity_number": "12345678",
      "email": "budi@mahasiswa.ac.id",
      "role": "mahasiswa"
    }
  },
  "message": "Login successful"
}
```

**Logout Response:**
```json
{
  "success": true,
  "message": "Logged out successfully"
}
```

**Profile Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Budi Santoso",
    "identity_number": "211420108",
    "email": "budi@mahasiswa.ac.id",
    "role": "mahasiswa",
    "classes": [
      {
        "id": 1,
        "name": "TI-2A",
        "academic_year": "2024/2025"
      }
    ]
  }
}
```

#### Attendance Endpoints

**Submit Attendance Response:**
```json
{
  "success": true,
  "data": {
    "status": "hadir",
    "distance": 45.23,
    "attendance": {
      "id": 123,
      "status": "hadir",
      "distance": 45.23,
      "created_at": "2024-01-15T10:30:00Z"
    }
  },
  "message": "Absensi berhasil dicatat"
}
```

**Attendance History Response (Paginated):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "status": "hadir",
      "distance": 45.23,
      "created_at": "2024-01-15T10:30:00Z",
      "schedule": {
        "id": 1,
        "start_time": "2024-01-15T08:00:00Z",
        "end_time": "2024-01-15T10:00:00Z",
        "course": {
          "id": 1,
          "course_name": "Pemrograman Web"
        }
      }
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 15,
    "total": 75,
    "from": 1,
    "to": 15
  },
  "links": {
    "first": "http://api.example.com/v1/attendance/history?page=1",
    "last": "http://api.example.com/v1/attendance/history?page=5",
    "prev": null,
    "next": "http://api.example.com/v1/attendance/history?page=2"
  }
}
```

**Today's Schedules Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "day": "Monday",
      "start_time": "2024-01-15T08:00:00Z",
      "end_time": "2024-01-15T10:00:00Z",
      "class_room": {
        "id": 1,
        "name": "TI-2A"
      },
      "course": {
        "id": 1,
        "course_name": "Pemrograman Web"
      },
      "location": {
        "id": 1,
        "name": "Gedung A - Fakultas Teknik"
      }
    }
  ]
}
```

#### Location Management Endpoints

**List Locations Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Gedung A - Fakultas Teknik",
      "address": "Jl. Kampus No. 1",
      "latitude": -6.2000000,
      "longitude": 106.8166660,
      "radius": 100.0
    }
  ]
}
```

**Create/Update Location Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Gedung A - Fakultas Teknik",
    "address": "Jl. Kampus No. 1",
    "latitude": -6.2000000,
    "longitude": 106.8166660,
    "radius": 100.0,
    "created_at": "2024-01-15T10:30:00Z",
    "updated_at": "2024-01-15T10:30:00Z"
  },
  "message": "Location created successfully"
}
```

#### Schedule Management Endpoints

**List Schedules Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "day": "Monday",
      "start_time": "2024-01-15T08:00:00Z",
      "end_time": "2024-01-15T10:00:00Z",
      "class_room": {
        "id": 1,
        "name": "TI-2A",
        "academic_year": "2024/2025"
      },
      "course": {
        "id": 1,
        "course_code": "TI101",
        "course_name": "Pemrograman Web",
        "credits": 3
      },
      "location": {
        "id": 1,
        "name": "Gedung A - Fakultas Teknik"
      }
    }
  ]
}
```

#### Report Endpoints

**Attendance Report Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "status": "hadir",
      "distance": 45.23,
      "created_at": "2024-01-15T10:30:00Z",
      "user": {
        "id": 1,
        "name": "Budi Santoso",
        "identity_number": "211420108"
      },
      "schedule": {
        "id": 1,
        "start_time": "2024-01-15T08:00:00Z",
        "end_time": "2024-01-15T10:00:00Z",
        "course": {
          "course_name": "Pemrograman Web"
        }
      }
    }
  ]
}
```

## Data Models

The design leverages existing Eloquent models without schema changes:

### Model Relationships

```
User
├── hasMany: Attendance
└── belongsToMany: ClassRoom (through class_user pivot)

Attendance
├── belongsTo: User
└── belongsTo: Schedule

Schedule
├── belongsTo: ClassRoom
├── belongsTo: Course
├── belongsTo: Location
└── hasMany: Attendance

Location
└── hasMany: Schedule

ClassRoom
├── belongsToMany: User (through class_user pivot)
└── hasMany: Schedule

Course
└── hasMany: Schedule
```

### Resource Loading Strategy

To avoid N+1 query problems, Resources will use conditional loading:

```php
// In Resource classes
'relationship' => $this->whenLoaded('relationship', 
    fn() => new RelatedResource($this->relationship)
),
```

Controllers should eager load relationships when needed:

```php
// In Controllers
$attendance = Attendance::with(['user', 'schedule.course'])->paginate(15);
return $this->paginated(AttendanceResource::collection($attendance));
```


