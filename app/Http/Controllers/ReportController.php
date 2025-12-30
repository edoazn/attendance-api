<?php

namespace App\Http\Controllers;

use App\Exports\AttendanceExport;
use App\Http\Requests\ReportRequest;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportController extends Controller
{
    public function __construct(
        private ReportService $reportService
    ) {}

    /**
     * @OA\Get(
     *     path="/reports/attendance",
     *     summary="Laporan absensi",
     *     description="Mendapatkan laporan absensi dengan filter opsional (Admin only)",
     *     operationId="attendanceReport",
     *     tags={"Reports"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="start_date",
     *         in="query",
     *         description="Tanggal mulai (format: Y-m-d)",
     *         required=false,
     *         @OA\Schema(type="string", format="date", example="2025-01-01")
     *     ),
     *     @OA\Parameter(
     *         name="end_date",
     *         in="query",
     *         description="Tanggal akhir (format: Y-m-d)",
     *         required=false,
     *         @OA\Schema(type="string", format="date", example="2025-12-31")
     *     ),
     *     @OA\Parameter(
     *         name="schedule_id",
     *         in="query",
     *         description="Filter berdasarkan jadwal",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Berhasil",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="user", type="object",
     *                         @OA\Property(property="id", type="integer"),
     *                         @OA\Property(property="name", type="string"),
     *                         @OA\Property(property="email", type="string")
     *                     ),
     *                     @OA\Property(property="schedule", type="object",
     *                         @OA\Property(property="id", type="integer"),
     *                         @OA\Property(property="course_name", type="string")
     *                     ),
     *                     @OA\Property(property="status", type="string"),
     *                     @OA\Property(property="distance", type="number"),
     *                     @OA\Property(property="created_at", type="string", format="date-time")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Forbidden - Admin only")
     * )
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

    /**
     * @OA\Get(
     *     path="/reports/attendance/export",
     *     summary="Export laporan ke Excel",
     *     description="Download laporan absensi dalam format Excel (Admin only)",
     *     operationId="exportAttendanceExcel",
     *     tags={"Reports"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="start_date",
     *         in="query",
     *         description="Tanggal mulai (format: Y-m-d)",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="end_date",
     *         in="query",
     *         description="Tanggal akhir (format: Y-m-d)",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="schedule_id",
     *         in="query",
     *         description="Filter berdasarkan jadwal",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="File Excel berhasil didownload",
     *         @OA\MediaType(
     *             mediaType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Forbidden - Admin only")
     * )
     */
    public function exportExcel(ReportRequest $request): BinaryFileResponse
    {
        $filename = 'laporan-absensi-' . now()->format('Y-m-d-His') . '.xlsx';

        return Excel::download(
            new AttendanceExport(
                $request->start_date,
                $request->end_date,
                $request->schedule_id
            ),
            $filename
        );
    }
}
