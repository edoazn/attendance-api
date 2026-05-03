<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Schedule;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class AttendanceService
{
    public function __construct(
        private GeolocationService $geolocationService
    ) {}

    /**
     * Process attendance submission for a user.
     * Dispatches to the correct handler based on $method.
     *
     * @param User   $user       The user submitting attendance
     * @param int    $scheduleId The schedule ID
     * @param string $method     'geolocation' | 'qr_code' | 'attendance_code'
     * @param array  $data       Method-specific payload
     * @return array Response with success, status, distance, method, message, attendance
     */
    public function processAttendance(
        User $user,
        int $scheduleId,
        string $method,
        array $data
    ): array {
        $schedule = Schedule::with('location')->findOrFail($scheduleId);

        // --- Shared validations (all methods) ---

        if (!$this->validateUserClass($user, $schedule)) {
            return $this->failure('Anda tidak terdaftar di kelas ini');
        }

        if (!$this->validateScheduleTime($schedule)) {
            return $this->failure('Absensi hanya dapat dilakukan pada waktu jadwal aktif');
        }

        if ($this->checkDuplicateAttendance($user, $schedule)) {
            return $this->failure('Anda sudah melakukan absensi untuk jadwal ini');
        }

        // --- Method-specific handling ---

        return match ($method) {
            'geolocation'     => $this->handleGeolocation($user, $schedule, $data),
            'qr_code'         => $this->handleQrCode($user, $schedule, $data),
            'attendance_code' => $this->handleAttendanceCode($user, $schedule, $data),
        };
    }

    // -------------------------------------------------------------------------
    // Private handlers
    // -------------------------------------------------------------------------

    /**
     * Handle geolocation-based attendance (existing logic, unchanged).
     */
    private function handleGeolocation(User $user, Schedule $schedule, array $data): array
    {
        $latitude  = (float) $data['latitude'];
        $longitude = (float) $data['longitude'];

        $distance = $this->geolocationService->calculateDistance(
            $latitude,
            $longitude,
            (float) $schedule->location->latitude,
            (float) $schedule->location->longitude
        );

        $isWithinRadius = $distance <= (float) $schedule->location->radius;
        $status         = $isWithinRadius ? 'hadir' : 'ditolak';

        $attendance = Attendance::create([
            'user_id'     => $user->id,
            'schedule_id' => $schedule->id,
            'latitude'    => $latitude,
            'longitude'   => $longitude,
            'distance'    => $distance,
            'status'      => $status,
            'method'      => 'geolocation',
        ]);

        $message = $isWithinRadius
            ? 'Absensi berhasil dicatat'
            : 'Absensi ditolak karena lokasi di luar radius';

        return [
            'success'    => true,
            'status'     => $status,
            'distance'   => round($distance, 2),
            'method'     => 'geolocation',
            'attendance' => $attendance,
            'message'    => $message,
        ];
    }

    /**
     * Handle QR Code-based attendance.
     * Matches the provided qr_token against the schedule's stored qr_token.
     */
    private function handleQrCode(User $user, Schedule $schedule, array $data): array
    {
        $providedToken = $data['qr_token'] ?? '';

        if (empty($schedule->qr_token) || $schedule->qr_token !== $providedToken) {
            return $this->failure('QR Code tidak valid untuk jadwal ini');
        }

        $attendance = Attendance::create([
            'user_id'     => $user->id,
            'schedule_id' => $schedule->id,
            'status'      => 'hadir',
            'method'      => 'qr_code',
        ]);

        return [
            'success'    => true,
            'status'     => 'hadir',
            'distance'   => null,
            'method'     => 'qr_code',
            'attendance' => $attendance,
            'message'    => 'Absensi via QR Code berhasil dicatat',
        ];
    }

    /**
     * Handle manual 6-digit code attendance.
     * Validates the code and its expiry time.
     */
    private function handleAttendanceCode(User $user, Schedule $schedule, array $data): array
    {
        $providedCode = strtoupper($data['attendance_code'] ?? '');

        if (empty($schedule->attendance_code)) {
            return $this->failure('Kode absensi belum dibuat untuk jadwal ini');
        }

        if ($schedule->attendance_code !== $providedCode) {
            return $this->failure('Kode absensi tidak valid');
        }

        if (!$schedule->isCodeValid()) {
            return $this->failure('Kode absensi sudah kedaluwarsa');
        }

        $attendance = Attendance::create([
            'user_id'     => $user->id,
            'schedule_id' => $schedule->id,
            'status'      => 'hadir',
            'method'      => 'attendance_code',
        ]);

        return [
            'success'    => true,
            'status'     => 'hadir',
            'distance'   => null,
            'method'     => 'attendance_code',
            'attendance' => $attendance,
            'message'    => 'Absensi via kode manual berhasil dicatat',
        ];
    }

    // -------------------------------------------------------------------------
    // Shared private helpers
    // -------------------------------------------------------------------------

    private function failure(string $message): array
    {
        return [
            'success'    => false,
            'status'     => null,
            'distance'   => null,
            'method'     => null,
            'message'    => $message,
        ];
    }

    /**
     * Validate if user belongs to the schedule's class.
     */
    private function validateUserClass(User $user, Schedule $schedule): bool
    {
        return $user->classes()->where('classes.id', $schedule->class_id)->exists();
    }

    /**
     * Validate if current time is within schedule time range.
     */
    private function validateScheduleTime(Schedule $schedule): bool
    {
        return $schedule->isActive();
    }

    /**
     * Check if user has already successfully attended this schedule.
     * Only blocks if status is 'hadir', allows retry if 'ditolak'.
     */
    private function checkDuplicateAttendance(User $user, Schedule $schedule): bool
    {
        return Attendance::where('user_id', $user->id)
            ->where('schedule_id', $schedule->id)
            ->where('status', 'hadir')
            ->exists();
    }

    // -------------------------------------------------------------------------
    // Query helpers
    // -------------------------------------------------------------------------

    /**
     * Get user's attendance history with schedule details (paginated).
     */
    public function getUserHistory(User $user, int $perPage = 15)
    {
        return Attendance::with(['user', 'schedule.course'])
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get today's schedules for user's classes.
     */
    public function getTodaySchedules(User $user): Collection
    {
        $today       = Carbon::today();
        $userClassIds = $user->classes()->pluck('classes.id');

        return Schedule::with(['classRoom', 'course', 'location'])
            ->whereIn('class_id', $userClassIds)
            ->whereDate('start_time', $today)
            ->orderBy('start_time', 'asc')
            ->get();
    }
}
