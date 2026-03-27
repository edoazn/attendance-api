# Sistem Absensi Mahasiswa Geolocation

Sistem absensi mahasiswa berbasis geolocation menggunakan Laravel 12. Mahasiswa dapat melakukan absensi dengan validasi lokasi GPS, dan admin dapat mengelola data melalui dashboard Filament.

## Features

- **API REST v1** - Endpoint untuk mobile app
- **Geolocation Validation** - Validasi lokasi menggunakan Haversine Formula
- **Class Management** - Pengelolaan kelas dan penugasan mahasiswa per kelas
- **Admin Dashboard** - Filament v4 untuk manajemen data
- **Swagger Documentation** - Interactive API docs
- **Excel Export** - Download laporan absensi
- **Soft Deletes** - Data tidak hilang permanen

## Tech Stack

- Laravel 12
- PHP 8.2+
- MySQL
- Filament v4
- Laravel Sanctum (Authentication)
- L5-Swagger (API Documentation)
- Maatwebsite Excel (Export)

## Installation

```bash
# Clone repository
git clone https://github.com/edoazn/attendance-api.git
cd attendance-api

# Install dependencies
composer install

# Copy environment file
cp .env.example .env

# Generate app key
php artisan key:generate

# Configure database in .env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=absensi_mhs
DB_USERNAME=root
DB_PASSWORD=

# Run migrations and seeders
php artisan migrate --seed

# Generate Swagger docs
php artisan l5-swagger:generate

# Start server
php artisan serve
```

## Default Accounts

| Role | Email | Password |
|------|-------|----------|
| Admin | admin@kampus.ac.id | password |
| Mahasiswa | budi@mahasiswa.ac.id | password |
| Mahasiswa | siti@mahasiswa.ac.id | password |

## API Endpoints

Base URL: `/api/v1`

### Authentication
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/login` | Login dan dapatkan token |
| POST | `/logout` | Logout (hapus token) |

### Mahasiswa
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/schedules/today` | Jadwal hari ini |
| POST | `/attendance` | Submit absensi |
| GET | `/attendance/history` | Riwayat absensi |

### Admin Only
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/locations` | Daftar lokasi |
| POST | `/locations` | Tambah lokasi |
| PUT | `/locations/{id}` | Update lokasi |
| GET | `/schedules` | Daftar jadwal |
| POST | `/schedules` | Tambah jadwal |
| GET | `/reports/attendance` | Laporan absensi |
| GET | `/reports/attendance/export` | Export Excel |

## API Documentation

Swagger UI tersedia di: `/api/documentation`

## Admin Dashboard

Filament dashboard tersedia di: `/admin`

Login dengan akun admin untuk mengakses:
- Manajemen Users
- Manajemen Classes (Kelas)
- Manajemen Locations
- Manajemen Courses
- Manajemen Schedules
- View Attendance Records
- Dashboard Statistics

## Attendance Flow

1. Mahasiswa login via mobile app
2. Lihat jadwal hari ini (`GET /schedules/today`)
3. Submit absensi dengan koordinat GPS (`POST /attendance`)
4. Sistem validasi:
   - Waktu dalam jadwal (±5 menit toleransi)
   - Lokasi dalam radius
5. Status: `hadir` (dalam radius) atau `ditolak` (di luar radius)
6. Jika ditolak, bisa retry unlimited sampai berhasil

## Geolocation

Sistem menggunakan **Haversine Formula** untuk menghitung jarak antara koordinat user dan lokasi absensi.

```
Radius default: 100 meter
Toleransi waktu: ±5 menit dari jadwal
```

## Testing

```bash
# Run all tests
php artisan test

# Run property tests only
php artisan test --filter=Property
```

47 property-based tests untuk memastikan sistem berjalan dengan benar.

## Project Structure

```
app/
├── Filament/           # Admin dashboard resources
│   ├── Resources/
│   │   ├── Attendances/
│   │   ├── ClassRooms/     # Manajemen kelas
│   │   ├── Courses/
│   │   ├── Locations/
│   │   ├── Schedules/
│   │   └── Users/
│   └── Widgets/        # Dashboard widgets
├── Http/
│   ├── Controllers/    # API controllers
│   ├── Middleware/     # Custom middleware
│   └── Requests/       # Form requests
├── Models/             # Eloquent models
│   ├── Attendance.php
│   ├── ClassRoom.php   # Model kelas
│   ├── Course.php
│   ├── Location.php
│   ├── Schedule.php
│   └── User.php
├── Services/           # Business logic
│   ├── AttendanceService.php
│   ├── GeolocationService.php
│   └── ReportService.php
└── Exports/            # Excel exports
```

## Environment Variables

```env
APP_TIMEZONE=Asia/Jakarta

DB_CONNECTION=mysql
DB_DATABASE=absensi_mhs

L5_SWAGGER_GENERATE_ALWAYS=true
```

## License

MIT License
