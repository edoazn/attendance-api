# Design Document

## Overview

Admin Dashboard untuk Sistem Absensi Mahasiswa Geolocation dibangun menggunakan Filament v4. Dashboard menyediakan antarmuka web untuk admin mengelola users, locations, courses, schedules, dan melihat attendance records dengan statistik dashboard.

### Key Design Decisions

1. **Filament v4** - Menggunakan versi terbaru dengan unified Schema system
2. **Resource-based CRUD** - Setiap entity memiliki Filament Resource sendiri
3. **Role-based Access** - Hanya admin yang dapat mengakses panel
4. **View-only Attendance** - Attendance records tidak dapat dimodifikasi via dashboard
5. **Dashboard Widgets** - Statistik real-time untuk monitoring

## Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                    Filament Admin Panel                      │
│                      (/admin route)                          │
├─────────────────────────────────────────────────────────────┤
│                    AdminPanelProvider                        │
│              (Panel configuration & auth)                    │
├─────────────────────────────────────────────────────────────┤
│                      Resources                               │
│   UserResource | LocationResource | CourseResource          │
│   ScheduleResource | AttendanceResource                     │
├─────────────────────────────────────────────────────────────┤
│                       Widgets                                │
│   StatsOverview | TodayAttendance | RecentAttendance        │
├─────────────────────────────────────────────────────────────┤
│                    Existing Models                           │
│    User | Location | Course | Schedule | Attendance         │
└─────────────────────────────────────────────────────────────┘
```

## Components and Interfaces

### 1. Panel Provider

```php
// app/Providers/Filament/AdminPanelProvider.php
class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->colors(['primary' => Color::Blue])
            ->discoverResources(in: app_path('Filament/Resources'))
            ->discoverWidgets(in: app_path('Filament/Widgets'))
            ->middleware([...])
            ->authMiddleware([Authenticate::class]);
    }
}
```

### 2. User Model Update

```php
// app/Models/User.php
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

class User extends Authenticatable implements FilamentUser
{
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->role === 'admin';
    }
}
```

### 3. Resources

#### UserResource
```php
class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'User Management';
    
    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('name')->required(),
            TextInput::make('email')->email()->required()->unique(ignoreRecord: true),
            TextInput::make('password')->password()->required(fn ($record) => !$record),
            Select::make('role')->options(['admin' => 'Admin', 'mahasiswa' => 'Mahasiswa']),
        ]);
    }
    
    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->searchable(),
            TextColumn::make('email')->searchable(),
            TextColumn::make('role')->badge(),
            TextColumn::make('created_at')->dateTime(),
        ])->filters([
            SelectFilter::make('role'),
        ]);
    }
}
```

#### LocationResource
```php
class LocationResource extends Resource
{
    protected static ?string $model = Location::class;
    protected static ?string $navigationIcon = 'heroicon-o-map-pin';
    protected static ?string $navigationGroup = 'Master Data';
    
    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('name')->required(),
            TextInput::make('latitude')->numeric()->required()
                ->minValue(-90)->maxValue(90),
            TextInput::make('longitude')->numeric()->required()
                ->minValue(-180)->maxValue(180),
            TextInput::make('radius')->numeric()->required()->minValue(1)
                ->suffix('meters'),
        ]);
    }
    
    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->searchable(),
            TextColumn::make('latitude'),
            TextColumn::make('longitude'),
            TextColumn::make('radius')->suffix(' m'),
        ]);
    }
    
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
}
```

#### CourseResource
```php
class CourseResource extends Resource
{
    protected static ?string $model = Course::class;
    protected static ?string $navigationIcon = 'heroicon-o-book-open';
    protected static ?string $navigationGroup = 'Master Data';
    
    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('course_name')->required(),
            TextInput::make('course_code')->required()->unique(ignoreRecord: true),
            TextInput::make('lecturer_name')->required(),
            TextInput::make('location_room'),
        ]);
    }
    
    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('course_code')->searchable(),
            TextColumn::make('course_name')->searchable(),
            TextColumn::make('lecturer_name'),
            TextColumn::make('location_room'),
        ]);
    }
}
```

#### ScheduleResource
```php
class ScheduleResource extends Resource
{
    protected static ?string $model = Schedule::class;
    protected static ?string $navigationIcon = 'heroicon-o-calendar';
    protected static ?string $navigationGroup = 'Master Data';
    
    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('course_id')
                ->relationship('course', 'course_name')->required(),
            Select::make('location_id')
                ->relationship('location', 'name')->required(),
            DateTimePicker::make('start_time')->required(),
            DateTimePicker::make('end_time')->required()
                ->after('start_time'),
        ]);
    }
    
    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('course.course_name')->searchable(),
            TextColumn::make('location.name'),
            TextColumn::make('start_time')->dateTime(),
            TextColumn::make('end_time')->dateTime(),
        ])->filters([
            SelectFilter::make('course_id')->relationship('course', 'course_name'),
            SelectFilter::make('location_id')->relationship('location', 'name'),
        ]);
    }
}
```

#### AttendanceResource (View Only)
```php
class AttendanceResource extends Resource
{
    protected static ?string $model = Attendance::class;
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationGroup = 'Attendance';
    
    public static function canCreate(): bool
    {
        return false;
    }
    
    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('user.name')->searchable(),
            TextColumn::make('schedule.course.course_name'),
            TextColumn::make('status')->badge()
                ->color(fn (string $state): string => match ($state) {
                    'hadir' => 'success',
                    'ditolak' => 'danger',
                }),
            TextColumn::make('distance')->suffix(' m'),
            TextColumn::make('created_at')->dateTime(),
        ])->filters([
            SelectFilter::make('status')
                ->options(['hadir' => 'Hadir', 'ditolak' => 'Ditolak']),
            Filter::make('created_at')
                ->form([
                    DatePicker::make('from'),
                    DatePicker::make('until'),
                ])
                ->query(fn (Builder $query, array $data) => $query
                    ->when($data['from'], fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
                    ->when($data['until'], fn ($q, $date) => $q->whereDate('created_at', '<=', $date))
                ),
        ])->actions([
            ViewAction::make(),
        ]);
    }
}
```

### 4. Widgets

#### StatsOverviewWidget
```php
class StatsOverviewWidget extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Users', User::count()),
            Stat::make('Total Locations', Location::count()),
            Stat::make('Total Courses', Course::count()),
            Stat::make('Attendance Today', Attendance::whereDate('created_at', today())->count()),
        ];
    }
}
```

#### AttendanceChartWidget
```php
class AttendanceChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Attendance Status Today';
    
    protected function getData(): array
    {
        $hadir = Attendance::whereDate('created_at', today())
            ->where('status', 'hadir')->count();
        $ditolak = Attendance::whereDate('created_at', today())
            ->where('status', 'ditolak')->count();
            
        return [
            'datasets' => [
                ['data' => [$hadir, $ditolak], 'backgroundColor' => ['#10B981', '#EF4444']],
            ],
            'labels' => ['Hadir', 'Ditolak'],
        ];
    }
    
    protected function getType(): string
    {
        return 'doughnut';
    }
}
```

#### RecentAttendanceWidget
```php
class RecentAttendanceWidget extends BaseWidget
{
    protected function getTableQuery(): Builder
    {
        return Attendance::query()->with(['user', 'schedule.course'])
            ->latest()->limit(5);
    }
    
    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('user.name'),
            TextColumn::make('schedule.course.course_name'),
            TextColumn::make('status')->badge(),
            TextColumn::make('created_at')->since(),
        ];
    }
}
```

## Data Models

Menggunakan existing models dari API implementation:
- User (dengan tambahan FilamentUser interface)
- Location
- Course
- Schedule
- Attendance

## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system-essentially, a formal statement about what the system should do.*

### Property 1: Admin Panel Access Control

*For any* user with role "admin", accessing /admin panel should be allowed (return 200).
*For any* user with role "mahasiswa", accessing /admin panel should be denied (redirect to login or 403).

**Validates: Requirements 1.4, 1.5**

### Property 2: User CRUD Round-Trip

*For any* valid user data (name, email, role), creating a user then retrieving should return equivalent data with hashed password.

**Validates: Requirements 2.2, 2.7**

### Property 3: User Role Filter Correctness

*For any* role filter applied to user list, all returned users should have that specific role.

**Validates: Requirements 2.6**

### Property 4: Location CRUD Round-Trip

*For any* valid location data, creating then retrieving should return equivalent data.

**Validates: Requirements 3.3, 3.4**

### Property 5: Course Code Uniqueness

*For any* attempt to create a course with duplicate course_code, the system should reject the creation.

**Validates: Requirements 4.6**

### Property 6: Schedule Time Validation

*For any* schedule where end_time is not after start_time, creation should be rejected.

**Validates: Requirements 5.3**

### Property 7: Schedule Filter Correctness

*For any* course or location filter applied to schedule list, all returned schedules should match the filter criteria.

**Validates: Requirements 5.7**

### Property 8: Attendance Status Filter Correctness

*For any* status filter (hadir/ditolak) applied to attendance list, all returned records should have that status.

**Validates: Requirements 6.3**

### Property 9: Attendance Date Range Filter

*For any* date range filter applied to attendance list, all returned records should have created_at within the specified range.

**Validates: Requirements 6.4**

### Property 10: Attendance View-Only Enforcement

*For any* attempt to create or edit attendance via Filament, the action should be disabled or not available.

**Validates: Requirements 6.5**

### Property 11: Search Functionality

*For any* search query on searchable columns, returned results should contain the search term in the searchable fields.

**Validates: Requirements 8.4**

## Error Handling

| Scenario | Handling |
|----------|----------|
| Unauthorized access | Redirect to login page |
| Validation error | Display inline error messages |
| Duplicate entry | Show validation error for unique fields |
| Delete with relations | Show error or cascade based on config |
| Invalid date range | Form validation prevents submission |

## Testing Strategy

### Testing Framework
- **Feature Tests**: Laravel HTTP testing untuk panel access
- **Property Tests**: PHPUnit dengan Pest untuk CRUD operations

### Test Structure
```
tests/
├── Feature/
│   └── Filament/
│       ├── AdminPanelAccessTest.php
│       ├── UserResourceTest.php
│       ├── LocationResourceTest.php
│       ├── CourseResourceTest.php
│       ├── ScheduleResourceTest.php
│       └── AttendanceResourceTest.php
└── Property/
    └── Filament/
        ├── AdminAccessPropertyTest.php
        ├── UserCrudPropertyTest.php
        └── FilterPropertyTest.php
```

### Property-Based Testing Configuration
- Minimum 100 iterations per property test
- Use Faker for generating random valid data
- Tag format: **Feature: admin-dashboard-filament, Property {number}: {property_text}**
