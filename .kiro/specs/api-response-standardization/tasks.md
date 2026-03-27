# Implementation Plan: API Response Standardization

## Overview

This implementation plan converts the API response standardization design into actionable coding tasks. The plan follows a logical sequence: first establishing the response infrastructure (trait and resources), then implementing centralized error handling, and finally refactoring all controllers to use the new standardized system. Each task builds incrementally to ensure the system remains functional throughout the implementation process.

## Tasks

- [x] 1. Create ApiResponse trait with helper methods
  - Create `app/Http/Traits/ApiResponse.php` file
  - Implement `success()` method for simple data responses
  - Implement `error()` method for error responses
  - Implement `resource()` method for single resource responses
  - Implement `collection()` method for resource collection responses
  - Implement `paginated()` method for paginated resource responses
  - Ensure all methods return JsonResponse with standardized envelope structure
  - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5, 1.6, 6.1, 6.2, 6.3, 6.4, 6.5, 6.6_
  
  - [x]* 1.1 Write unit tests for ApiResponse trait
    - Test success() method with various data types
    - Test error() method with and without errors array
    - Test resource(), collection(), and paginated() methods
    - Verify correct HTTP status codes are returned
    - _Requirements: 6.1, 6.2, 6.3, 6.4, 6.5_

- [x] 2. Create API Resource classes for all models
  - [x] 2.1 Create UserResource
    - Create `app/Http/Resources/UserResource.php`
    - Implement `toArray()` method with fields: id, name, identity_number, email, role, created_at, updated_at
    - Add conditional loading for classes relationship using `whenLoaded()`
    - Format timestamps to ISO 8601 format
    - _Requirements: 3.1, 3.2, 3.5, 3.6, 14.4_
    
    - [x]* 2.1.1 Write unit tests for UserResource
      - Test resource transformation with and without relationships
      - Verify timestamp formatting
      - Test conditional classes relationship loading
      - _Requirements: 3.1, 3.2, 3.5, 14.4_

  - [x] 2.2 Create AttendanceResource
    - Create `app/Http/Resources/AttendanceResource.php`
    - Implement `toArray()` method with fields: id, status, distance, latitude, longitude, created_at, updated_at
    - Add conditional loading for user and schedule relationships
    - Ensure numeric fields (distance, latitude, longitude) are returned as numbers
    - _Requirements: 3.1, 3.2, 3.5, 3.6, 14.6_
    
    - [x]* 2.2.1 Write unit tests for AttendanceResource
      - Test numeric field types (distance, latitude, longitude)
      - Test conditional relationship loading
      - Verify timestamp formatting
      - _Requirements: 3.1, 3.2, 3.5, 14.6_
    
  - [x] 2.3 Create ScheduleResource
    - Create `app/Http/Resources/ScheduleResource.php`
    - Implement `toArray()` method with fields: id, day, start_time, end_time, created_at, updated_at
    - Add conditional loading for class_room, course, and location relationships
    - Format timestamps to ISO 8601 format
    - _Requirements: 3.1, 3.2, 3.3, 3.5, 3.6, 14.4_
    
    - [x]* 2.3.1 Write unit tests for ScheduleResource
      - Test multiple conditional relationships (class_room, course, location)
      - Verify timestamp formatting
      - Test nested resource transformation
      - _Requirements: 3.1, 3.3, 3.5, 14.4_

  - [x] 2.4 Create LocationResource
    - Create `app/Http/Resources/LocationResource.php`
    - Implement `toArray()` method with fields: id, name, address, latitude, longitude, radius, created_at, updated_at
    - Ensure numeric fields (latitude, longitude, radius) are returned as numbers
    - _Requirements: 3.1, 3.2, 3.6, 14.6_
    
    - [x]* 2.4.1 Write unit tests for LocationResource
      - Test numeric field types (latitude, longitude, radius)
      - Verify all fields are properly transformed
      - _Requirements: 3.1, 3.2, 14.6_

  - [x] 2.5 Create ClassRoomResource
    - Create `app/Http/Resources/ClassRoomResource.php`
    - Implement `toArray()` method with fields: id, name, academic_year, created_at, updated_at
    - _Requirements: 3.1, 3.2, 3.6_
    
    - [x]* 2.5.1 Write unit tests for ClassRoomResource
      - Test resource transformation
      - Verify all fields are properly formatted
      - _Requirements: 3.1, 3.2_

  - [x] 2.6 Create CourseResource
    - Create `app/Http/Resources/CourseResource.php`
    - Implement `toArray()` method with fields: id, course_code, course_name, credits, created_at, updated_at
    - _Requirements: 3.1, 3.2, 3.6_
    
    - [x]* 2.6.1 Write unit tests for CourseResource
      - Test resource transformation
      - Verify all fields are properly formatted
      - _Requirements: 3.1, 3.2_

- [x] 3. Modify Exception Handler for standardized error responses
  - Modify `app/Exceptions/Handler.php`
  - Override `render()` method to detect API requests (check if request path starts with 'api/')
  - Implement `handleApiException()` method to format exceptions into standardized error responses
  - Map ModelNotFoundException to 404 status with standardized error envelope
  - Map AuthenticationException to 401 status with standardized error envelope
  - Map AuthorizationException to 403 status with standardized error envelope
  - Map ValidationException to 422 status with field-specific errors in "errors" object
  - Map ThrottleRequestsException to 429 status with standardized error envelope
  - Map all other exceptions to 500 status with generic message (exclude stack trace in production)
  - Include stack trace only when APP_DEBUG is true
  - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5, 2.6, 2.7, 12.1, 12.2, 12.3, 12.4, 12.5, 15.1, 15.2, 15.3, 15.4, 15.5, 15.6, 15.7, 15.8, 15.9_
  
  - [ ]* 3.1 Write integration tests for Exception Handler
    - Test ModelNotFoundException returns 404 with correct format
    - Test AuthenticationException returns 401 with correct format
    - Test AuthorizationException returns 403 with correct format
    - Test ValidationException returns 422 with field errors
    - Test ThrottleRequestsException returns 429 with correct format
    - Test generic exceptions return 500 in production mode
    - Verify stack traces only appear in debug mode
    - _Requirements: 15.2, 15.3, 15.4, 15.5, 15.6, 15.7, 15.8, 15.9_

- [x] 4. Checkpoint - Verify infrastructure components
  - Ensure all tests pass, ask the user if questions arise.

- [x] 5. Refactor AuthController to use standardized responses
  - Add `use ApiResponse` trait to AuthController
  - Refactor `login()` method to use `success()` helper with token and UserResource
  - Refactor `logout()` method to use `success()` helper with message
  - Refactor `profile()` method to use `resource()` helper with UserResource
  - Ensure login returns status 200 on success
  - Ensure profile eager loads 'classes' relationship to avoid N+1 queries
  - _Requirements: 7.1, 7.2, 7.3, 7.4, 7.5, 5.1, 5.2_
  
  - [ ]* 5.1 Write integration tests for AuthController
    - Test login endpoint returns standardized success response with token and user data
    - Test login endpoint returns 401 for invalid credentials
    - Test logout endpoint returns standardized success response
    - Test profile endpoint returns user data with classes relationship
    - Verify response envelope structure matches specification
    - _Requirements: 7.1, 7.2, 7.3, 7.4, 7.5_

- [x] 6. Refactor AttendanceController to use standardized responses
  - Add `use ApiResponse` trait to AttendanceController
  - Refactor attendance submission endpoint to use `success()` helper with status, distance, and AttendanceResource
  - Refactor attendance history endpoint to use `paginated()` helper with AttendanceResource collection
  - Refactor today's schedules endpoint to use `collection()` helper with ScheduleResource collection
  - Ensure attendance submission returns status 200 on success
  - Eager load 'user', 'schedule.course' relationships for attendance history
  - Eager load 'classRoom', 'course', 'location' relationships for today's schedules
  - _Requirements: 8.1, 8.2, 8.3, 8.4, 8.5, 5.1, 3.5_
  
  - [ ]* 6.1 Write integration tests for AttendanceController
    - Test attendance submission returns standardized response with status and distance
    - Test attendance submission returns 422 for validation errors
    - Test attendance history returns paginated response with correct meta and links
    - Test today's schedules returns collection with nested relationships
    - Verify N+1 query prevention with relationship eager loading
    - _Requirements: 8.1, 8.2, 8.3, 8.4, 8.5, 4.1, 4.2, 4.3_

- [ ] 7. Refactor LocationController to use standardized responses
  - Add `use ApiResponse` trait to LocationController
  - Refactor `index()` method to use `collection()` helper with LocationResource collection
  - Refactor `store()` method to use `resource()` helper with LocationResource and status 201
  - Refactor `update()` method to use `resource()` helper with LocationResource and status 200
  - Refactor `show()` method to use `resource()` helper with LocationResource
  - Remove manual array mapping from all methods
  - _Requirements: 9.1, 9.2, 9.3, 9.4, 9.5, 5.2, 5.1_
  
  - [ ]* 7.1 Write integration tests for LocationController
    - Test index endpoint returns collection of locations
    - Test store endpoint returns 201 with created location
    - Test update endpoint returns 200 with updated location
    - Test show endpoint returns single location resource
    - Test 404 error when location not found
    - Verify response envelope structure for all endpoints
    - _Requirements: 9.1, 9.2, 9.3, 9.4, 9.5, 5.1, 5.2_

- [ ] 8. Refactor ScheduleController to use standardized responses
  - Add `use ApiResponse` trait to ScheduleController
  - Refactor `index()` method to use `collection()` helper with ScheduleResource collection
  - Refactor `store()` method to use `resource()` helper with ScheduleResource and status 201
  - Refactor `show()` method to use `resource()` helper with ScheduleResource
  - Eager load 'classRoom', 'course', 'location' relationships to avoid N+1 queries
  - Remove manual array mapping from all methods
  - _Requirements: 10.1, 10.2, 10.3, 10.4, 5.2, 5.1, 3.5_
  
  - [ ]* 8.1 Write integration tests for ScheduleController
    - Test index endpoint returns collection with nested relationships
    - Test store endpoint returns 201 with created schedule
    - Test show endpoint returns single schedule with relationships
    - Test 404 error when schedule not found
    - Verify N+1 query prevention with eager loading
    - _Requirements: 10.1, 10.2, 10.3, 10.4, 3.5_

- [ ] 9. Refactor ReportController to use standardized responses
  - Add `use ApiResponse` trait to ReportController
  - Refactor attendance report endpoint to use `collection()` or `paginated()` helper with AttendanceResource
  - Eager load 'user', 'schedule.course' relationships for report data
  - Ensure Excel export endpoint maintains file download functionality (skip Response_Envelope for file downloads)
  - _Requirements: 11.1, 11.2, 11.3, 11.4, 3.5_
  
  - [ ]* 9.1 Write integration tests for ReportController
    - Test attendance report returns standardized collection response
    - Test report filtering returns correct filtered results
    - Test Excel export returns file with correct headers
    - Verify nested relationships are included in report data
    - _Requirements: 11.1, 11.2, 11.3, 11.4_

- [ ] 10. Final checkpoint - Verify all endpoints and run tests
  - Run full test suite to ensure all tests pass
  - Verify all API endpoints return standardized response format
  - Check that HTTP status codes are correct across all endpoints
  - Ensure no N+1 query issues with relationship loading
  - Confirm error responses follow standardized format
  - Ask the user if questions arise or if any issues are found

## Notes

- Tasks marked with `*` are optional and can be skipped for faster MVP
- Each task references specific requirements for traceability
- Checkpoints ensure incremental validation
- All controllers must use the ApiResponse trait for consistency
- All Resource classes must handle conditional relationship loading to prevent N+1 queries
- Exception Handler must only format API responses (requests starting with 'api/')
- HTTP status codes must follow REST conventions (200 for success, 201 for creation, 404 for not found, etc.)
- Timestamps must be formatted in ISO 8601 format across all resources
- Numeric fields (latitude, longitude, distance, radius) must be returned as numbers, not strings
- Testing sub-tasks are marked as optional with `*` postfix - they provide additional quality assurance but are not required for core functionality
- Integration tests verify end-to-end behavior of refactored controllers
- Unit tests verify individual component behavior (Resources, Trait methods)
