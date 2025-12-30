# Requirements Document

## Introduction

Sistem API Absensi Mahasiswa berbasis Geolocation adalah RESTful API yang memungkinkan mahasiswa melakukan absensi kehadiran berdasarkan lokasi geografis. Sistem memvalidasi bahwa mahasiswa berada dalam radius tertentu dari lokasi yang ditentukan sebelum mencatat kehadiran. Dibangun menggunakan Laravel dengan arsitektur Controller → Service → Model.

## Glossary

- **System**: API Absensi Mahasiswa Geolocation
- **Mahasiswa**: Pengguna dengan role mahasiswa yang dapat melakukan absensi
- **Admin**: Pengguna dengan role admin yang mengelola lokasi, jadwal, dan laporan
- **Location**: Titik koordinat kampus dengan radius yang ditentukan
- **Schedule**: Jadwal mata kuliah dengan waktu mulai dan selesai
- **Attendance**: Catatan kehadiran mahasiswa
- **Haversine_Formula**: Rumus matematika untuk menghitung jarak antara dua titik koordinat di permukaan bumi
- **Radius**: Jarak maksimum (dalam meter) dari lokasi kampus yang diizinkan untuk absensi
- **Sanctum**: Laravel Sanctum untuk autentikasi API berbasis token

## Requirements

### Requirement 1: User Authentication

**User Story:** As a user, I want to authenticate to the system, so that I can access protected features based on my role.

#### Acceptance Criteria

1. WHEN a user submits valid email and password to login endpoint, THE System SHALL return an authentication token
2. WHEN a user submits invalid credentials, THE System SHALL return 401 Unauthorized error
3. WHEN an authenticated user requests logout, THE System SHALL revoke the current token
4. THE System SHALL use Laravel Sanctum for token-based authentication
5. THE System SHALL protect all attendance-related endpoints with auth:sanctum middleware

### Requirement 2: Location Management (Admin)

**User Story:** As an admin, I want to manage campus locations, so that I can define valid attendance points with specific coordinates and radius.

#### Acceptance Criteria

1. WHEN an admin creates a new location with name, latitude, longitude, and radius, THE System SHALL store the location in database
2. WHEN an admin updates an existing location, THE System SHALL modify the location data
3. WHEN an admin requests location list, THE System SHALL return all locations
4. IF a non-admin user attempts location management, THEN THE System SHALL return 403 Forbidden error
5. THE System SHALL validate that latitude is between -90 and 90
6. THE System SHALL validate that longitude is between -180 and 180
7. THE System SHALL validate that radius is a positive number in meters

### Requirement 3: Schedule Management (Admin)

**User Story:** As an admin, I want to manage class schedules, so that I can define when and where attendance can be recorded.

#### Acceptance Criteria

1. WHEN an admin creates a schedule with course_id, location_id, start_time, and end_time, THE System SHALL store the schedule
2. WHEN an admin requests schedule list, THE System SHALL return all schedules with course and location details
3. IF a non-admin user attempts schedule management, THEN THE System SHALL return 403 Forbidden error
4. THE System SHALL validate that end_time is after start_time
5. THE System SHALL validate that course_id and location_id exist in database

### Requirement 4: Attendance Recording (Mahasiswa)

**User Story:** As a mahasiswa, I want to record my attendance, so that my presence in class is documented.

#### Acceptance Criteria

1. WHEN a mahasiswa submits attendance with schedule_id, latitude, and longitude, THE System SHALL process the attendance request
2. WHEN the current time is within schedule start_time and end_time, THE System SHALL allow attendance processing
3. IF the current time is outside schedule time range, THEN THE System SHALL reject the attendance with appropriate message
4. WHEN a mahasiswa has already recorded attendance for a schedule, THE System SHALL reject duplicate attendance
5. THE System SHALL calculate distance using Haversine Formula with Earth radius of 6371000 meters
6. WHEN calculated distance is within location radius, THE System SHALL record attendance with status "hadir"
7. WHEN calculated distance exceeds location radius, THE System SHALL record attendance with status "ditolak"
8. THE System SHALL store latitude, longitude, and calculated distance for every attendance attempt
9. THE System SHALL return status, distance, and message in response

### Requirement 5: Attendance History (Mahasiswa)

**User Story:** As a mahasiswa, I want to view my attendance history, so that I can track my class attendance records.

#### Acceptance Criteria

1. WHEN a mahasiswa requests attendance history, THE System SHALL return all attendance records for that user
2. THE System SHALL include schedule details, status, distance, and timestamp in history response
3. THE System SHALL order history by created_at descending

### Requirement 6: Today's Schedule (Mahasiswa)

**User Story:** As a mahasiswa, I want to view today's schedules, so that I know which classes I need to attend.

#### Acceptance Criteria

1. WHEN a mahasiswa requests today's schedules, THE System SHALL return schedules for current date
2. THE System SHALL include course name, location name, start_time, and end_time in response

### Requirement 7: Attendance Reports (Admin)

**User Story:** As an admin, I want to view attendance reports, so that I can monitor student attendance across all schedules.

#### Acceptance Criteria

1. WHEN an admin requests attendance report, THE System SHALL return attendance data with user and schedule details
2. THE System SHALL support filtering by date range
3. THE System SHALL support filtering by schedule_id
4. IF a non-admin user attempts to access reports, THEN THE System SHALL return 403 Forbidden error

### Requirement 8: Distance Calculation Service

**User Story:** As a system architect, I want accurate distance calculation, so that attendance validation is reliable.

#### Acceptance Criteria

1. THE Haversine_Formula SHALL calculate distance between two coordinate points
2. THE System SHALL use Earth radius constant of 6371000 meters
3. THE System SHALL return distance in meters
4. THE System SHALL not use external APIs for distance calculation
5. FOR ALL valid coordinate pairs, calculating distance then comparing with radius SHALL produce consistent results

### Requirement 9: API Response Format

**User Story:** As an API consumer, I want consistent response format, so that I can reliably parse API responses.

#### Acceptance Criteria

1. THE System SHALL return JSON format for all responses
2. WHEN attendance is processed, THE System SHALL return status, distance, and message fields
3. WHEN an error occurs, THE System SHALL return appropriate HTTP status code with error message
4. THE System SHALL use consistent response structure across all endpoints

### Requirement 10: Security Requirements

**User Story:** As a system administrator, I want secure API operations, so that the system is protected from abuse.

#### Acceptance Criteria

1. THE System SHALL implement rate limiting on attendance endpoint
2. THE System SHALL not expose campus location coordinates to client responses
3. THE System SHALL store all attendance attempts for audit purposes
4. THE System SHALL validate all input data using Form Request Validation

### Requirement 11: Code Architecture

**User Story:** As a developer, I want clean code architecture, so that the codebase is maintainable and testable.

#### Acceptance Criteria

1. THE System SHALL implement Controller → Service → Model architecture
2. THE System SHALL place all business logic in Service classes
3. THE System SHALL place all distance calculations in dedicated Service
4. THE System SHALL use Form Request classes for input validation
5. THE System SHALL not contain business logic in Controller classes
