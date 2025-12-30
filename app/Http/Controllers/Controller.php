<?php

namespace App\Http\Controllers;

/**
 * @OA\Info(
 *     version="1.0.0",
 *     title="Sistem Absensi Mahasiswa Geolocation API",
 *     description="API untuk sistem absensi mahasiswa berbasis geolocation. Mahasiswa dapat melakukan absensi dengan validasi lokasi GPS.",
 *     @OA\Contact(
 *         email="admin@kampus.ac.id",
 *         name="Admin Support"
 *     )
 * )
 * 
 * @OA\Server(
 *     url="/api/v1",
 *     description="API Server v1"
 * )
 * 
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Masukkan token yang didapat dari endpoint login"
 * )
 * 
 * @OA\Tag(
 *     name="Authentication",
 *     description="Endpoint untuk autentikasi user"
 * )
 * @OA\Tag(
 *     name="Attendance",
 *     description="Endpoint untuk absensi mahasiswa"
 * )
 * @OA\Tag(
 *     name="Schedules",
 *     description="Endpoint untuk manajemen jadwal"
 * )
 * @OA\Tag(
 *     name="Locations",
 *     description="Endpoint untuk manajemen lokasi (Admin only)"
 * )
 * @OA\Tag(
 *     name="Reports",
 *     description="Endpoint untuk laporan absensi (Admin only)"
 * )
 */
abstract class Controller
{
    //
}
