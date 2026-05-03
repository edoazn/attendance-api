<?php

namespace App\Http\Controllers;

use App\Http\Requests\ScheduleRequest;
use App\Http\Resources\ScheduleResource;
use App\Http\Traits\ApiResponse;
use App\Models\Schedule;
use Illuminate\Http\JsonResponse;

class ScheduleController extends Controller
{
    use ApiResponse;

    /**
     * @OA\Get(
     *     path="/schedules",
     *     summary="Daftar semua jadwal",
     *     description="Mendapatkan semua jadwal dengan detail class, course dan location (Admin only)",
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
     *                     @OA\Property(property="class_id", type="integer"),
     *                     @OA\Property(property="course_id", type="integer"),
     *                     @OA\Property(property="location_id", type="integer"),
     *                     @OA\Property(property="start_time", type="string", format="date-time"),
     *                     @OA\Property(property="end_time", type="string", format="date-time"),
     *                     @OA\Property(property="class_room", type="object",
     *                         @OA\Property(property="id", type="integer"),
     *                         @OA\Property(property="name", type="string", example="TI-2A"),
     *                         @OA\Property(property="academic_year", type="string", example="2024/2025")
     *                     ),
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
        $schedules = Schedule::with(['classRoom', 'course', 'location'])->get();

        return $this->collection(ScheduleResource::collection($schedules));
    }

    /**
     * @OA\Post(
     *     path="/schedules",
     *     summary="Tambah jadwal baru",
     *     description="Membuat jadwal baru untuk kelas tertentu (Admin only)",
     *     operationId="storeSchedule",
     *     tags={"Schedules"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"class_id","course_id","location_id","start_time","end_time"},
     *             @OA\Property(property="class_id", type="integer", example=1, description="ID kelas"),
     *             @OA\Property(property="course_id", type="integer", example=1, description="ID mata kuliah"),
     *             @OA\Property(property="location_id", type="integer", example=1, description="ID lokasi"),
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
            'class_id'    => $request->class_id,
            'course_id'   => $request->course_id,
            'location_id' => $request->location_id,
            'start_time'  => $request->start_time,
            'end_time'    => $request->end_time,
        ]);

        $schedule->load(['classRoom', 'course', 'location']);

        return $this->resource(new ScheduleResource($schedule), 'Schedule created successfully', 201);
    }

    /**
     * @OA\Post(
     *     path="/schedules/{id}/generate-code",
     *     summary="Generate kode absensi manual",
     *     description="Generate kode 6-digit untuk absensi manual. Admin only.",
     *     operationId="generateAttendanceCode",
     *     tags={"Schedules"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="minutes_valid", type="integer", example=30,
     *                 description="Durasi berlaku kode dalam menit (default 30)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Kode berhasil di-generate",
     *         @OA\JsonContent(
     *             @OA\Property(property="attendance_code", type="string", example="ABC123"),
     *             @OA\Property(property="expires_at", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(response=403, description="Forbidden - Admin only"),
     *     @OA\Response(response=404, description="Schedule not found")
     * )
     */
    public function generateCode(\Illuminate\Http\Request $request, int $id): JsonResponse
    {
        $schedule = Schedule::findOrFail($id);
        $minutes  = (int) $request->input('minutes_valid', 30);
        $minutes  = max(1, min($minutes, 1440)); // clamp 1–1440 min

        $code = $schedule->generateAttendanceCode($minutes);

        return $this->success([
            'attendance_code' => $code,
            'expires_at'      => $schedule->code_expires_at->toISOString(),
            'minutes_valid'   => $minutes,
        ], 'Kode absensi berhasil di-generate');
    }

    /**
     * @OA\Post(
     *     path="/schedules/{id}/generate-qr",
     *     summary="Generate QR token jadwal",
     *     description="Generate atau regenerate QR token untuk jadwal. QR lama akan tidak berfungsi. Admin only.",
     *     operationId="generateQrToken",
     *     tags={"Schedules"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="QR token berhasil di-generate",
     *         @OA\JsonContent(
     *             @OA\Property(property="qr_token", type="string", example="550e8400-e29b-41d4-a716-446655440000")
     *         )
     *     ),
     *     @OA\Response(response=403, description="Forbidden - Admin only"),
     *     @OA\Response(response=404, description="Schedule not found")
     * )
     */
    public function generateQr(int $id): JsonResponse
    {
        $schedule = Schedule::findOrFail($id);
        $token    = $schedule->generateQrToken();

        return $this->success([
            'qr_token'    => $token,
            'schedule_id' => $schedule->id,
        ], 'QR token berhasil di-generate');
    }
}
