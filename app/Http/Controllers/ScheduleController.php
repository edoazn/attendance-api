<?php

namespace App\Http\Controllers;

use App\Http\Requests\ScheduleRequest;
use App\Models\Schedule;
use Illuminate\Http\JsonResponse;

class ScheduleController extends Controller
{
    /**
     * @OA\Get(
     *     path="/schedules",
     *     summary="Daftar semua jadwal",
     *     description="Mendapatkan semua jadwal dengan detail course dan location (Admin only)",
     *     operationId="indexSchedules",
     *     tags={"Schedules"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Berhasil",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="course_id", type="integer"),
     *                     @OA\Property(property="location_id", type="integer"),
     *                     @OA\Property(property="start_time", type="string", format="date-time"),
     *                     @OA\Property(property="end_time", type="string", format="date-time"),
     *                     @OA\Property(property="course", type="object",
     *                         @OA\Property(property="id", type="integer"),
     *                         @OA\Property(property="course_name", type="string"),
     *                         @OA\Property(property="course_code", type="string")
     *                     ),
     *                     @OA\Property(property="location", type="object",
     *                         @OA\Property(property="id", type="integer"),
     *                         @OA\Property(property="name", type="string"),
     *                         @OA\Property(property="latitude", type="number"),
     *                         @OA\Property(property="longitude", type="number"),
     *                         @OA\Property(property="radius", type="number")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Forbidden - Admin only")
     * )
     */
    public function index(): JsonResponse
    {
        $schedules = Schedule::with(['course', 'location'])->get();

        return response()->json([
            'data' => $schedules
        ]);
    }

    /**
     * @OA\Post(
     *     path="/schedules",
     *     summary="Tambah jadwal baru",
     *     description="Membuat jadwal baru (Admin only)",
     *     operationId="storeSchedule",
     *     tags={"Schedules"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"course_id","location_id","start_time","end_time"},
     *             @OA\Property(property="course_id", type="integer", example=1),
     *             @OA\Property(property="location_id", type="integer", example=1),
     *             @OA\Property(property="start_time", type="string", format="date-time", example="2025-01-15 08:00:00"),
     *             @OA\Property(property="end_time", type="string", format="date-time", example="2025-01-15 10:00:00", description="Harus setelah start_time")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Jadwal berhasil dibuat",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Schedule created successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Forbidden - Admin only"),
     *     @OA\Response(response=422, description="Validasi gagal")
     * )
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
