# Requirements Document

## Introduction

Admin Dashboard untuk Sistem Absensi Mahasiswa Geolocation menggunakan Filament v4. Dashboard ini memungkinkan admin untuk mengelola lokasi, jadwal, mata kuliah, pengguna, dan melihat laporan absensi melalui antarmuka web yang modern dan user-friendly.

## Glossary

- **System**: Admin Dashboard Filament
- **Admin**: Pengguna dengan role admin yang dapat mengakses dashboard
- **Filament**: Laravel admin panel framework versi 4 (stable August 2025)
- **Resource**: Filament resource untuk CRUD operations
- **Widget**: Komponen dashboard untuk menampilkan statistik
- **Panel**: Filament panel configuration untuk admin area
- **Schema**: Filament v4 unified schema system untuk forms, tables, dan infolists

## Requirements

### Requirement 1: Filament Installation dan Configuration

**User Story:** As a developer, I want to install and configure Filament v4, so that I can build an admin dashboard.

#### Acceptance Criteria

1. THE System SHALL install Filament v4 via Composer (filament/filament ^4.0)
2. THE System SHALL create a Filament admin panel at /admin route
3. THE System SHALL configure User model to implement FilamentUser interface
4. WHEN a user with role "admin" accesses /admin, THE System SHALL allow access
5. WHEN a user with role "mahasiswa" accesses /admin, THE System SHALL deny access
6. THE System SHALL use Filament v4 Schema system for unified form/table components

### Requirement 2: User Management Resource

**User Story:** As an admin, I want to manage users through the dashboard, so that I can create, view, edit, and delete user accounts.

#### Acceptance Criteria

1. WHEN an admin accesses User resource, THE System SHALL display list of all users
2. WHEN an admin creates a new user, THE System SHALL validate and store user data
3. WHEN an admin edits a user, THE System SHALL update user data
4. WHEN an admin deletes a user, THE System SHALL remove user from database
5. THE System SHALL display user name, email, role, and created_at in table
6. THE System SHALL provide filter by role (admin/mahasiswa)
7. THE System SHALL hash password when creating or updating user

### Requirement 3: Location Management Resource

**User Story:** As an admin, I want to manage campus locations through the dashboard, so that I can define attendance points.

#### Acceptance Criteria

1. WHEN an admin accesses Location resource, THE System SHALL display list of all locations
2. WHEN an admin creates a location, THE System SHALL validate latitude (-90 to 90), longitude (-180 to 180), and positive radius
3. WHEN an admin edits a location, THE System SHALL update location data
4. WHEN an admin deletes a location, THE System SHALL remove location from database
5. THE System SHALL display name, latitude, longitude, radius in table
6. THE System SHALL show location count in navigation badge

### Requirement 4: Course Management Resource

**User Story:** As an admin, I want to manage courses through the dashboard, so that I can organize class information.

#### Acceptance Criteria

1. WHEN an admin accesses Course resource, THE System SHALL display list of all courses
2. WHEN an admin creates a course, THE System SHALL validate and store course data
3. WHEN an admin edits a course, THE System SHALL update course data
4. WHEN an admin deletes a course, THE System SHALL remove course from database
5. THE System SHALL display course_name, course_code, lecturer_name in table
6. THE System SHALL ensure course_code is unique

### Requirement 5: Schedule Management Resource

**User Story:** As an admin, I want to manage class schedules through the dashboard, so that I can define when attendance can be recorded.

#### Acceptance Criteria

1. WHEN an admin accesses Schedule resource, THE System SHALL display list of all schedules with course and location names
2. WHEN an admin creates a schedule, THE System SHALL validate course_id, location_id, start_time, end_time
3. THE System SHALL validate that end_time is after start_time
4. WHEN an admin edits a schedule, THE System SHALL update schedule data
5. WHEN an admin deletes a schedule, THE System SHALL remove schedule from database
6. THE System SHALL display course name, location name, start_time, end_time in table
7. THE System SHALL provide filter by course and location

### Requirement 6: Attendance Resource (View Only)

**User Story:** As an admin, I want to view attendance records through the dashboard, so that I can monitor student attendance.

#### Acceptance Criteria

1. WHEN an admin accesses Attendance resource, THE System SHALL display list of all attendance records
2. THE System SHALL display user name, schedule info, status, distance, created_at in table
3. THE System SHALL provide filter by status (hadir/ditolak)
4. THE System SHALL provide filter by date range
5. THE System SHALL NOT allow creating or editing attendance records (view only)
6. THE System SHALL color-code status (green for hadir, red for ditolak)

### Requirement 7: Dashboard Widgets

**User Story:** As an admin, I want to see statistics on the dashboard, so that I can quickly understand attendance overview.

#### Acceptance Criteria

1. WHEN an admin accesses dashboard, THE System SHALL display total users count widget
2. THE System SHALL display total attendance today widget
3. THE System SHALL display attendance status breakdown (hadir vs ditolak) widget
4. THE System SHALL display recent attendance records widget
5. THE System SHALL display active schedules today widget

### Requirement 8: Navigation dan UI

**User Story:** As an admin, I want intuitive navigation, so that I can easily access all management features.

#### Acceptance Criteria

1. THE System SHALL group resources in sidebar navigation
2. THE System SHALL display resource counts as navigation badges
3. THE System SHALL use Indonesian language for labels where appropriate
4. THE System SHALL provide search functionality in tables
5. THE System SHALL support pagination in all resource tables

