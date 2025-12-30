<?php

namespace App\Http\Controllers;

use App\Http\Requests\AttendanceRequest;
use App\Services\AttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function __construct(
        private AttendanceService $attendanceService
    ) {}

    /**
     * Store a new attendance record.
     * 
     * Requirements: 4.1
     */
    public function store(AttendanceRequest $request): JsonResponse
    {
        $result = $this->attendanceService->processAttendance(
            $request->user(),
            $request->schedule_id,
            $request->latitude,
            $request->longitude
        );

        if (!$result['success']) {
            return response()->json([
                'status' => $result['status'],
                'distance' => $result['distance'],
                'message' => $result['message'],
            ], 422);
        }

        return response()->json([
            'status' => $result['status'],
            'distance' => $result['distance'],
            'message' => $result['message'],
        ]);
    }

    /**
     * Get the authenticated user's attendance history.
     * 
     * Requirements: 5.1
     */
    public function history(Request $request): JsonResponse
    {
        $history = $this->attendanceService->getUserHistory($request->user());

        $data = $history->map(function ($attendance) {
            return [
                'id' => $attendance->id,
                'schedule' => [
                    'id' => $attendance->schedule->id,
                    'course_name' => $attendance->schedule->course->course_name,
                    'start_time' => $attendance->schedule->start_time,
                    'end_time' => $attendance->schedule->end_time,
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

    /**
     * Get today's schedules.
     * 
     * Requirements: 6.1
     */
    public function todaySchedules(): JsonResponse
    {
        $schedules = $this->attendanceService->getTodaySchedules();

        $data = $schedules->map(function ($schedule) {
            return [
                'id' => $schedule->id,
                'course_name' => $schedule->course->course_name,
                'location_name' => $schedule->location->name,
                'start_time' => $schedule->start_time,
                'end_time' => $schedule->end_time,
            ];
        });

        return response()->json([
            'data' => $data,
        ]);
    }
}
