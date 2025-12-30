<?php

namespace App\Services;

use App\Models\Attendance;
use Illuminate\Database\Eloquent\Collection;

class ReportService
{
    /**
     * Get attendance report with optional filtering
     *
     * @param string|null $startDate Start date for filtering (Y-m-d format)
     * @param string|null $endDate End date for filtering (Y-m-d format)
     * @param int|null $scheduleId Filter by specific schedule
     * @return Collection
     * 
     * Requirements: 7.1, 7.2, 7.3
     */
    public function getAttendanceReport(
        ?string $startDate = null,
        ?string $endDate = null,
        ?int $scheduleId = null
    ): Collection {
        $query = Attendance::with(['user', 'schedule.course', 'schedule.location']);

        // Apply date range filter
        if ($startDate !== null) {
            $query->whereDate('created_at', '>=', $startDate);
        }

        if ($endDate !== null) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        // Apply schedule_id filter
        if ($scheduleId !== null) {
            $query->where('schedule_id', $scheduleId);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }
}
