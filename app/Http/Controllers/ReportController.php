<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReportRequest;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;

class ReportController extends Controller
{
    public function __construct(
        private ReportService $reportService
    ) {}

    /**
     * Get attendance report with optional filtering.
     * 
     * Requirements: 7.1
     */
    public function attendanceReport(ReportRequest $request): JsonResponse
    {
        $attendances = $this->reportService->getAttendanceReport(
            $request->start_date,
            $request->end_date,
            $request->schedule_id
        );

        $data = $attendances->map(function ($attendance) {
            return [
                'id' => $attendance->id,
                'user' => [
                    'id' => $attendance->user->id,
                    'name' => $attendance->user->name,
                    'email' => $attendance->user->email,
                ],
                'schedule' => [
                    'id' => $attendance->schedule->id,
                    'course_name' => $attendance->schedule->course->course_name,
                ],
                'status' => $attendance->status,
                'distance' => $attendance->distance,
                'created_at' => $attendance->created_at,
            ];
        });

        return response()->json([
            'data' => $data,
        ]);
    }
}
