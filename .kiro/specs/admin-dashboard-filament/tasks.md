# Implementation Plan: Admin Dashboard Filament v4

## Overview

Implementasi Admin Dashboard menggunakan Filament v4 untuk sistem absensi mahasiswa. Dashboard menyediakan CRUD untuk users, locations, courses, schedules, dan view-only untuk attendance records.

## Tasks

- [x] 1. Install dan Setup Filament v4
  - [x] 1.1 Install Filament v4 via Composer
    - Run: composer require filament/filament:"^4.0"
    - _Requirements: 1.1_

  - [x] 1.2 Create Admin Panel Provider
    - Run: php artisan filament:install --panels
    - Configure panel di AdminPanelProvider
    - _Requirements: 1.2_

  - [x] 1.3 Update User Model untuk FilamentUser
    - Implement FilamentUser interface
    - Add canAccessPanel() method untuk role check
    - _Requirements: 1.3, 1.4, 1.5_

  - [x] 1.4 Publish dan run Filament assets
    - Run: php artisan filament:assets
    - _Requirements: 1.6_

- [x] 2. Checkpoint - Filament Setup
  - Verify /admin route accessible
  - Verify admin can login
  - Verify mahasiswa cannot access
  - Ask user if questions arise

- [x] 3. Create User Resource
  - [x] 3.1 Generate UserResource
    - Run: php artisan make:filament-resource User --generate
    - _Requirements: 2.1_

  - [x] 3.2 Configure User form schema
    - Add name, email, password, role fields
    - Add password hashing mutator
    - _Requirements: 2.2, 2.7_

  - [x] 3.3 Configure User table columns
    - Add name, email, role, created_at columns
    - Add role filter
    - Add searchable columns
    - _Requirements: 2.5, 2.6_

  - [x] 3.4 Configure User pages (Create, Edit, List)
    - _Requirements: 2.2, 2.3, 2.4_

- [x] 4. Create Location Resource
  - [x] 4.1 Generate LocationResource
    - Run: php artisan make:filament-resource Location --generate
    - _Requirements: 3.1_

  - [x] 4.2 Configure Location form schema
    - Add name, latitude, longitude, radius fields
    - Add coordinate validation
    - _Requirements: 3.2_

  - [x] 4.3 Configure Location table columns
    - Add navigation badge for count
    - _Requirements: 3.5, 3.6_

- [ ] 5. Create Course Resource
  - [ ] 5.1 Generate CourseResource
    - Run: php artisan make:filament-resource Course --generate
    - _Requirements: 4.1_

  - [ ] 5.2 Configure Course form schema
    - Add course_name, course_code, lecturer_name, location_room
    - Add unique validation for course_code
    - _Requirements: 4.2, 4.6_

  - [ ] 5.3 Configure Course table columns
    - Add searchable columns
    - _Requirements: 4.5_

- [ ] 6. Create Schedule Resource
  - [ ] 6.1 Generate ScheduleResource
    - Run: php artisan make:filament-resource Schedule --generate
    - _Requirements: 5.1_

  - [ ] 6.2 Configure Schedule form schema
    - Add course_id, location_id relationships
    - Add start_time, end_time with validation
    - _Requirements: 5.2, 5.3_

  - [ ] 6.3 Configure Schedule table columns
    - Add relationship columns
    - Add course and location filters
    - _Requirements: 5.6, 5.7_

- [ ] 7. Checkpoint - CRUD Resources
  - Verify all CRUD operations work
  - Verify validations work
  - Ask user if questions arise

- [ ] 8. Create Attendance Resource (View Only)
  - [ ] 8.1 Generate AttendanceResource
    - Run: php artisan make:filament-resource Attendance --generate
    - _Requirements: 6.1_

  - [ ] 8.2 Configure as view-only
    - Disable create action
    - Disable edit action
    - Keep only view action
    - _Requirements: 6.5_

  - [ ] 8.3 Configure Attendance table columns
    - Add user, schedule relationships
    - Add status with color badge
    - Add distance, created_at
    - _Requirements: 6.2, 6.6_

  - [ ] 8.4 Configure Attendance filters
    - Add status filter
    - Add date range filter
    - _Requirements: 6.3, 6.4_

- [ ] 9. Create Dashboard Widgets
  - [ ] 9.1 Create StatsOverviewWidget
    - Display total users, locations, courses, attendance today
    - _Requirements: 7.1, 7.2_

  - [ ] 9.2 Create AttendanceChartWidget
    - Display hadir vs ditolak breakdown
    - Use doughnut chart
    - _Requirements: 7.3_

  - [ ] 9.3 Create RecentAttendanceWidget
    - Display 5 recent attendance records
    - _Requirements: 7.4_

  - [ ] 9.4 Create TodaySchedulesWidget
    - Display active schedules for today
    - _Requirements: 7.5_

- [ ] 10. Configure Navigation dan UI
  - [ ] 10.1 Group resources in navigation
    - User Management group
    - Master Data group
    - Attendance group
    - _Requirements: 8.1_

  - [ ] 10.2 Add navigation badges
    - Show counts on Location, Course, Schedule
    - _Requirements: 8.2_

  - [ ] 10.3 Configure Indonesian labels
    - Update resource labels
    - _Requirements: 8.3_

  - [ ] 10.4 Configure pagination
    - Set default pagination
    - _Requirements: 8.5_

- [ ] 11. Final Checkpoint
  - Run all tests
  - Verify all resources work
  - Verify dashboard widgets display correctly
  - Verify navigation and UI
  - Ask user if questions arise

## Notes

- Filament v4 menggunakan unified Schema system
- Attendance resource adalah view-only (tidak bisa create/edit)
- Dashboard widgets menampilkan statistik real-time
- Hanya user dengan role admin yang dapat mengakses panel
