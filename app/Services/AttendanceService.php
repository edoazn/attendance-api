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
     * Process attendance submission for a user
     *
     * @param User $user The user submitting attendance
     * @param int $scheduleId The schedule ID
     * @param float $latitude User's latitude
     * @param float $longitude User's longitude
     * @return array Response with status, distance, and message
     */
    public function processAttendance(
        User $user,
        int $scheduleId,
        float $latitude,
        float $longitude
    ): array {
        $schedule = Schedule::with('location')->findOrFail($scheduleId);

        // Validate schedule time
        if (!$this->validateScheduleTime($schedule)) {
            return [
                'success' => false,
                'status' => null,
                'distance' => null,
                'message' => 'Absensi hanya dapat dilakukan pada waktu jadwal aktif',
            ];
        }

        // Check for duplicate attendance
        if ($this->checkDuplicateAttendance($user, $schedule)) {
            return [
                'success' => false,
                'status' => null,
                'distance' => null,
                'message' => 'Anda sudah melakukan absensi untuk jadwal ini',
            ];
        }

        // Calculate distance using GeolocationService
        $distance = $this->geolocationService->calculateDistance(
            $latitude,
            $longitude,
            (float) $schedule->location->latitude,
            (float) $schedule->location->longitude
        );

        // Determine status based on distance and radius
        $isWithinRadius = $distance <= (float) $schedule->location->radius;
        $status = $isWithinRadius ? 'hadir' : 'ditolak';

        // Store attendance record
        Attendance::create([
            'user_id' => $user->id,
            'schedule_id' => $schedule->id,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'distance' => $distance,
            'status' => $status,
        ]);

        $message = $isWithinRadius
            ? 'Absensi berhasil dicatat'
            : 'Absensi ditolak karena lokasi di luar radius';

        return [
            'success' => true,
            'status' => $status,
            'distance' => round($distance, 2),
            'message' => $message,
        ];
    }

    /**
     * Validate if current time is within schedule time range
     *
     * @param Schedule $schedule
     * @return bool
     */
    private function validateScheduleTime(Schedule $schedule): bool
    {
        return $schedule->isActive();
    }

    /**
     * Check if user has already submitted attendance for this schedule
     *
     * @param User $user
     * @param Schedule $schedule
     * @return bool True if duplicate exists
     */
    private function checkDuplicateAttendance(User $user, Schedule $schedule): bool
    {
        return Attendance::where('user_id', $user->id)
            ->where('schedule_id', $schedule->id)
            ->exists();
    }

    /**
     * Get user's attendance history with schedule details (paginated)
     *
     * @param User $user
     * @param int $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getUserHistory(User $user, int $perPage = 15)
    {
        return Attendance::with(['schedule.course', 'schedule.location'])
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get today's schedules with course and location details
     *
     * @return Collection
     */
    public function getTodaySchedules(): Collection
    {
        $today = Carbon::today();
        
        return Schedule::with(['course', 'location'])
            ->whereDate('start_time', $today)
            ->orderBy('start_time', 'asc')
            ->get();
    }
}
