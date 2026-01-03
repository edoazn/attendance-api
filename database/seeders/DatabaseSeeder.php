<?php

namespace Database\Seeders;

use App\Models\ClassRoom;
use App\Models\Course;
use App\Models\Location;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Users - 1 Admin, 2 Mahasiswa
        $admin = User::create([
            'name' => 'Admin User',
            'identity_number' => '12345678',
            'email' => 'admin@kampus.ac.id',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        $mahasiswa1 = User::create([
            'name' => 'Budi Santoso',
            'identity_number' => '211420108',
            'email' => 'budi@mahasiswa.ac.id',
            'password' => Hash::make('password'),
            'role' => 'mahasiswa',
        ]);

        $mahasiswa2 = User::create([
            'name' => 'Siti Rahayu',
            'identity_number' => '211420109',
            'email' => 'siti@mahasiswa.ac.id',
            'password' => Hash::make('password'),
            'role' => 'mahasiswa',
        ]);

        // Locations - 2 Gedung Kampus
        $gedungA = Location::create([
            'name' => 'Gedung A - Fakultas Teknik',
            'latitude' => -6.200000,
            'longitude' => 106.816666,
            'radius' => 100, // 100 meter
        ]);

        $gedungB = Location::create([
            'name' => 'Gedung B - Fakultas Ekonomi',
            'latitude' => -6.201500,
            'longitude' => 106.818000,
            'radius' => 150, // 150 meter
        ]);

        // Courses - 3 Mata Kuliah
        $course1 = Course::create([
            'course_name' => 'Pemrograman Web',
            'course_code' => 'IF101',
            'lecturer_name' => 'Dr. Ahmad Wijaya',
            'location_room' => 'Ruang 101',
        ]);

        $course2 = Course::create([
            'course_name' => 'Basis Data',
            'course_code' => 'IF102',
            'lecturer_name' => 'Prof. Dewi Lestari',
            'location_room' => 'Ruang 202',
        ]);

        $course3 = Course::create([
            'course_name' => 'Ekonomi Digital',
            'course_code' => 'EK201',
            'lecturer_name' => 'Dr. Rudi Hartono',
            'location_room' => 'Ruang 301',
        ]);

        // Classes - Kelas
        $kelasTI2A = ClassRoom::create([
            'name' => 'TI-2A',
            'academic_year' => '2024/2025',
        ]);

        $kelasTI2B = ClassRoom::create([
            'name' => 'TI-2B',
            'academic_year' => '2024/2025',
        ]);

        // Assign mahasiswa ke kelas
        $kelasTI2A->users()->attach([$mahasiswa1->id, $mahasiswa2->id]);

        // Schedules - Jadwal untuk hari ini
        $today = now()->format('Y-m-d');

        Schedule::create([
            'class_id' => $kelasTI2A->id,
            'course_id' => $course1->id,
            'location_id' => $gedungA->id,
            'start_time' => "{$today} 08:00:00",
            'end_time' => "{$today} 10:00:00",
        ]);

        Schedule::create([
            'class_id' => $kelasTI2A->id,
            'course_id' => $course2->id,
            'location_id' => $gedungA->id,
            'start_time' => "{$today} 10:30:00",
            'end_time' => "{$today} 12:30:00",
        ]);

        Schedule::create([
            'class_id' => $kelasTI2B->id,
            'course_id' => $course3->id,
            'location_id' => $gedungB->id,
            'start_time' => "{$today} 13:00:00",
            'end_time' => "{$today} 15:00:00",
        ]);

        // Schedule untuk testing - aktif sepanjang hari
        Schedule::create([
            'class_id' => $kelasTI2A->id,
            'course_id' => $course1->id,
            'location_id' => $gedungA->id,
            'start_time' => "{$today} 00:00:00",
            'end_time' => "{$today} 23:59:59",
        ]);

        $this->command->info('Seeding completed!');
        $this->command->info('');
        $this->command->info('=== LOGIN CREDENTIALS ===');
        $this->command->info('Admin: 12345678 / password');
        $this->command->info('Mahasiswa 1: 211420108 / password');
        $this->command->info('Mahasiswa 2: 211420109 / password');
        $this->command->info('');
        $this->command->info('=== LOCATIONS ===');
        $this->command->info("Gedung A: lat={$gedungA->latitude}, lon={$gedungA->longitude}, radius={$gedungA->radius}m");
        $this->command->info("Gedung B: lat={$gedungB->latitude}, lon={$gedungB->longitude}, radius={$gedungB->radius}m");
    }
}
