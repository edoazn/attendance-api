# Implementation Plan: API Absensi Mahasiswa Geolocation

## Overview

Implementasi RESTful API untuk sistem absensi mahasiswa berbasis geolocation menggunakan Laravel. Mengikuti arsitektur Controller → Service → Model dengan Laravel Sanctum untuk autentikasi.

## Tasks

- [x] 1. Setup Database Migrations dan Models
  - [x] 1.1 Create locations migration dan model
    - Migration dengan fields: name, latitude, longitude, radius
    - Model dengan fillable dan relationships
    - _Requirements: 2.1, 2.5, 2.6, 2.7_

  - [x] 1.2 Create courses migration dan model
    - Migration dengan fields: course_name, course_code, lecturer_name, location
    - Model dengan fillable dan relationships
    - _Requirements: 3.1_

  - [x] 1.3 Create schedules migration dan model
    - Migration dengan fields: course_id, location_id, start_time, end_time
    - Model dengan relationships ke Course dan Location
    - Add isActive() method untuk cek waktu
    - _Requirements: 3.1, 4.2_

  - [x] 1.4 Create attendances migration dan model
    - Migration dengan fields: user_id, schedule_id, latitude, longitude, distance, status
    - Model dengan relationships ke User dan Schedule
    - _Requirements: 4.8_

  - [x] 1.5 Update User model
    - Add role field ke users migration
    - Add isAdmin() dan isMahasiswa() methods
    - Add HasApiTokens trait
    - _Requirements: 1.4_

- [x] 2. Implement GeolocationService
  - [x] 2.1 Create GeolocationService class
    - Implement calculateDistance() dengan Haversine Formula
    - Implement isWithinRadius() method
    - Use EARTH_RADIUS constant = 6371000 meters
    - _Requirements: 8.1, 4.5_

  - [x] 2.2 Write property test for Haversine calculation
    - **Property 6: Haversine Distance Calculation Correctness**
    - Test symmetry: distance(A,B) == distance(B,A)
    - Test identity: distance(A,A) == 0
    - Test triangle inequality
    - **Validates: Requirements 8.1, 8.5, 4.5**

- [x] 3. Implement Authentication
  - [x] 3.1 Create LoginRequest form validation
    - Validate email dan password fields
    - _Requirements: 1.1_

  - [x] 3.2 Create AuthController
    - Implement login() method dengan Sanctum token
    - Implement logout() method untuk revoke token
    - _Requirements: 1.1, 1.3_

  - [x] 3.3 Setup auth routes
    - POST /api/login
    - POST /api/logout (protected)
    - _Requirements: 1.1, 1.3_

  - [x] 3.4 Write property test for authentication
    - **Property 1: Authentication Token Round-Trip**
    - **Validates: Requirements 1.1, 1.3**

- [x] 4. Implement Admin Middleware dan Authorization
  - [x] 4.1 Create EnsureUserIsAdmin middleware
    - Check user role
    - Return 403 jika bukan admin
    - _Requirements: 2.4, 3.3, 7.4_

  - [x] 4.2 Register middleware di bootstrap/app.php
    - _Requirements: 2.4_

  - [x] 4.3 Write property test for admin protection
    - **Property 3: Admin-Only Endpoints Protection**
    - **Validates: Requirements 2.4, 3.3, 7.4**

- [x] 5. Checkpoint - Core Infrastructure
  - Ensure all migrations run successfully
  - Ensure GeolocationService tests pass
  - Ensure authentication works
  - Ask user if questions arise

- [x] 6. Implement Location Management (Admin)
  - [x] 6.1 Create LocationRequest form validation
    - Validate name, latitude, longitude, radius
    - _Requirements: 2.5, 2.6, 2.7_

  - [x] 6.2 Create LocationController
    - Implement store() method
    - Implement update() method
    - Implement index() method
    - _Requirements: 2.1, 2.2, 2.3_

  - [x] 6.3 Setup location routes dengan admin middleware
    - POST /api/locations
    - PUT /api/locations/{id}
    - GET /api/locations
    - _Requirements: 2.1, 2.2, 2.3_

  - [x] 6.4 Write property test for location CRUD
    - **Property 4: Location CRUD Round-Trip**
    - **Validates: Requirements 2.1, 2.2, 2.3**

- [x] 7. Implement Schedule Management (Admin)
  - [x] 7.1 Create ScheduleRequest form validation
    - Validate course_id, location_id, start_time, end_time
    - Validate end_time after start_time
    - _Requirements: 3.4, 3.5_

  - [x] 7.2 Create ScheduleController
    - Implement store() method
    - Implement index() method dengan eager loading
    - _Requirements: 3.1, 3.2_

  - [x] 7.3 Setup schedule routes dengan admin middleware
    - POST /api/schedules
    - GET /api/schedules
    - _Requirements: 3.1, 3.2_

  - [x] 7.4 Write property test for schedule CRUD
    - **Property 5: Schedule CRUD Round-Trip**
    - **Validates: Requirements 3.1, 3.2**

- [x] 8. Implement AttendanceService
  - [x] 8.1 Create AttendanceService class
    - Inject GeolocationService
    - Implement processAttendance() method
    - Implement validateScheduleTime() private method
    - Implement checkDuplicateAttendance() private method
    - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.6, 4.7_

  - [x] 8.2 Implement getUserHistory() method
    - Return user's attendance dengan schedule details
    - Order by created_at descending
    - _Requirements: 5.1, 5.2, 5.3_

  - [x] 8.3 Implement getTodaySchedules() method
    - Filter schedules untuk hari ini
    - Include course dan location details
    - _Requirements: 6.1, 6.2_

  - [x] 8.4 Write property test for attendance status determination
    - **Property 7: Distance-Based Attendance Status Determination**
    - **Validates: Requirements 4.6, 4.7**

  - [x] 8.5 Write property test for duplicate prevention
    - **Property 8: Duplicate Attendance Prevention**
    - **Validates: Requirements 4.4**

- [x] 9. Implement Attendance Endpoints (Mahasiswa)
  - [x] 9.1 Create AttendanceRequest form validation
    - Validate schedule_id, latitude, longitude
    - _Requirements: 4.1_

  - [x] 9.2 Create AttendanceController
    - Inject AttendanceService
    - Implement store() method
    - Implement history() method
    - Implement todaySchedules() method
    - _Requirements: 4.1, 5.1, 6.1_

  - [x] 9.3 Setup attendance routes dengan auth middleware
    - POST /api/attendance
    - GET /api/attendance/history
    - GET /api/schedules/today
    - _Requirements: 1.5, 4.1, 5.1, 6.1_

  - [x] 9.4 Write property test for schedule time validation
    - **Property 9: Schedule Time Validation**
    - **Validates: Requirements 4.2, 4.3**

  - [x] 9.5 Write property test for attendance data persistence
    - **Property 10: Attendance Data Persistence**
    - **Validates: Requirements 4.8, 10.3**

- [x] 10. Checkpoint - Attendance Flow
  - Ensure attendance submission works
  - Ensure history retrieval works
  - Ensure today's schedules works
  - Ask user if questions arise

- [x] 11. Implement Report Service dan Endpoint
  - [x] 11.1 Create ReportService class
    - Implement getAttendanceReport() method
    - Support date range filtering
    - Support schedule_id filtering
    - _Requirements: 7.1, 7.2, 7.3_

  - [x] 11.2 Create ReportRequest form validation
    - Validate optional start_date, end_date, schedule_id
    - _Requirements: 7.2, 7.3_

  - [x] 11.3 Add report endpoint ke AdminController
    - Implement attendanceReport() method
    - _Requirements: 7.1_

  - [x] 11.4 Setup report route dengan admin middleware
    - GET /api/reports/attendance
    - _Requirements: 7.1, 7.4_

  - [x] 11.5 Write property test for report filtering
    - **Property 13: Report Filtering Correctness**
    - **Validates: Requirements 7.2, 7.3**

- [x] 12. Implement Security Features
  - [x] 12.1 Add rate limiting ke attendance endpoint
    - Configure throttle middleware
    - _Requirements: 10.1_

  - [x] 12.2 Ensure location coordinates not exposed
    - Review all responses
    - Remove sensitive data dari client responses
    - _Requirements: 10.2_

  - [x] 12.3 Write property test for protected endpoints
    - **Property 2: Protected Endpoints Require Authentication**
    - **Validates: Requirements 1.5**

- [x] 13. Implement History Properties
  - [x] 13.1 Write property test for user-specific history
    - **Property 11: User-Specific History Retrieval**
    - **Validates: Requirements 5.1**

  - [x] 13.2 Write property test for history ordering
    - **Property 12: History Ordering**
    - **Validates: Requirements 5.3**

- [x] 14. Final Checkpoint
  - Run all migrations
  - Run all tests
  - Verify all endpoints work correctly
  - Ensure all property tests pass
  - Ask user if questions arise

## Notes

- All tasks are required for comprehensive implementation
- Each task references specific requirements for traceability
- Checkpoints ensure incremental validation
- Property tests validate universal correctness properties
- Unit tests validate specific examples and edge cases
- All business logic must be in Service classes, not Controllers
