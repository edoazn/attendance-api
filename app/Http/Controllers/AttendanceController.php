<?php

namespace App\Http\Controllers;

use App\Http\Requests\AttendanceRequest;
use App\Http\Resources\AttendanceResource;
use App\Http\Resources\ScheduleResource;
use App\Http\Traits\ApiResponse;
use App\Services\AttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    use ApiResponse;
    public function __construct(
        private AttendanceService $attendanceService
    ) {}

    /**
     * @OA\Post(
     *     path="/attendance",
     *     summary="Submit absensi",
     *     description="Mahasiswa submit absensi dengan koordinat GPS. Status akan 'hadir' jika dalam radius lokasi, 'ditolak' jika di luar radius.",
     *     operationId="storeAttendance",
     *     tags={"Attendance"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"schedule_id","latitude","longitude"},
     *             @OA\Property(property="schedule_id", type="integer", example=1, description="ID jadwal yang akan diabsen"),
     *             @OA\Property(property="latitude", type="number", format="float", example=-6.2000000, description="Latitude posisi user"),
     *             @OA\Property(property="longitude", type="number", format="float", example=106.8166660, description="Longitude posisi user")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Absensi berhasil diproses",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", enum={"hadir", "ditolak"}, example="hadir"),
     *             @OA\Property(property="distance", type="number", format="float", example=45.23, description="Jarak dari lokasi dalam meter"),
     *             @OA\Property(property="message", type="string", example="Absensi berhasil dicatat")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Absensi gagal (di luar waktu jadwal atau sudah absen)",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", nullable=true),
     *             @OA\Property(property="distance", type="number", nullable=true),
     *             @OA\Property(property="message", type="string", example="Absensi hanya dapat dilakukan pada waktu jadwal aktif")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
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
            return $this->error(
                $result['message'],
                [],
                422
            );
        }

        return $this->success([
            'status' => $result['status'],
            'distance' => $result['distance'],
            'attendance' => new AttendanceResource($result['attendance']),
        ], $result['message'], 200);
    }

    /**
     * @OA\Get(
     *     path="/attendance/history",
     *     summary="Riwayat absensi user",
     *     description="Mendapatkan riwayat absensi user yang sedang login (dengan pagination)",
     *     operationId="attendanceHistory",
     *     tags={"Attendance"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Jumlah data per halaman",
     *         required=false,
     *         @OA\Schema(type="integer", default=15)
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Nomor halaman",
     *         required=false,
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Berhasil mendapatkan riwayat",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="schedule", type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="course_name", type="string", example="Pemrograman Web"),
     *                         @OA\Property(property="start_time", type="string", format="date-time"),
     *                         @OA\Property(property="end_time", type="string", format="date-time")
     *                     ),
     *                     @OA\Property(property="status", type="string", example="hadir"),
     *                     @OA\Property(property="distance", type="number", example=45.23),
     *                     @OA\Property(property="created_at", type="string", format="date-time")
     *                 )
     *             ),
     *             @OA\Property(property="meta", type="object",
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="last_page", type="integer", example=5),
     *                 @OA\Property(property="per_page", type="integer", example=15),
     *                 @OA\Property(property="total", type="integer", example=75)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function history(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $history = $this->attendanceService->getUserHistory($request->user(), $perPage);

        return $this->paginated(AttendanceResource::collection($history));
    }

    /**
     * @OA\Get(
     *     path="/schedules/today",
     *     summary="Jadwal hari ini",
     *     description="Mendapatkan daftar jadwal untuk hari ini berdasarkan kelas user",
     *     operationId="todaySchedules",
     *     tags={"Schedules"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Berhasil mendapatkan jadwal",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="class_name", type="string", example="TI-2A"),
     *                     @OA\Property(property="course_name", type="string", example="Pemrograman Web"),
     *                     @OA\Property(property="location_name", type="string", example="Gedung A - Fakultas Teknik"),
     *                     @OA\Property(property="start_time", type="string", format="date-time"),
     *                     @OA\Property(property="end_time", type="string", format="date-time")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function todaySchedules(Request $request): JsonResponse
    {
        $schedules = $this->attendanceService->getTodaySchedules($request->user());

        return $this->collection(ScheduleResource::collection($schedules));
    }
}
