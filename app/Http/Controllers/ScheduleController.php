<?php

namespace App\Http\Controllers;

use App\Http\Requests\ScheduleRequest;
use App\Models\Schedule;
use Illuminate\Http\JsonResponse;

class ScheduleController extends Controller
{
    /**
     * Display a listing of all schedules with course and location details.
     */
    public function index(): JsonResponse
    {
        $schedules = Schedule::with(['course', 'location'])->get();

        return response()->json([
            'data' => $schedules
        ]);
    }

    /**
     * Store a newly created schedule in storage.
     */
    public function store(ScheduleRequest $request): JsonResponse
    {
        $schedule = Schedule::create([
            'course_id' => $request->course_id,
            'location_id' => $request->location_id,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
        ]);

        $schedule->load(['course', 'location']);

        return response()->json([
            'message' => 'Schedule created successfully',
            'data' => $schedule
        ], 201);
    }
}
