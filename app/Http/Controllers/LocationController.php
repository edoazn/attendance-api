<?php

namespace App\Http\Controllers;

use App\Http\Requests\LocationRequest;
use App\Models\Location;
use Illuminate\Http\JsonResponse;

class LocationController extends Controller
{
    /**
     * @OA\Get(
     *     path="/locations",
     *     summary="Daftar lokasi",
     *     description="Mendapatkan semua lokasi absensi (Admin only)",
     *     operationId="indexLocations",
     *     tags={"Locations"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Berhasil",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Gedung A - Fakultas Teknik"),
     *                     @OA\Property(property="latitude", type="number", example=-6.2000000),
     *                     @OA\Property(property="longitude", type="number", example=106.8166660),
     *                     @OA\Property(property="radius", type="number", example=100)
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
        $locations = Location::all();

        return response()->json([
            'data' => $locations
        ]);
    }

    /**
     * @OA\Post(
     *     path="/locations",
     *     summary="Tambah lokasi baru",
     *     description="Membuat lokasi absensi baru (Admin only)",
     *     operationId="storeLocation",
     *     tags={"Locations"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","latitude","longitude","radius"},
     *             @OA\Property(property="name", type="string", example="Gedung C - Lab Komputer"),
     *             @OA\Property(property="latitude", type="number", example=-6.2010000, description="Latitude (-90 to 90)"),
     *             @OA\Property(property="longitude", type="number", example=106.8170000, description="Longitude (-180 to 180)"),
     *             @OA\Property(property="radius", type="number", example=100, description="Radius dalam meter")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Lokasi berhasil dibuat",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Location created successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Forbidden - Admin only"),
     *     @OA\Response(response=422, description="Validasi gagal")
     * )
     */
    public function store(LocationRequest $request): JsonResponse
    {
        $location = Location::create([
            'name' => $request->name,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'radius' => $request->radius,
        ]);

        return response()->json([
            'message' => 'Location created successfully',
            'data' => $location
        ], 201);
    }

    /**
     * @OA\Put(
     *     path="/locations/{id}",
     *     summary="Update lokasi",
     *     description="Mengupdate lokasi absensi (Admin only)",
     *     operationId="updateLocation",
     *     tags={"Locations"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID lokasi",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","latitude","longitude","radius"},
     *             @OA\Property(property="name", type="string", example="Gedung C - Lab Komputer Updated"),
     *             @OA\Property(property="latitude", type="number", example=-6.2010000),
     *             @OA\Property(property="longitude", type="number", example=106.8170000),
     *             @OA\Property(property="radius", type="number", example=150)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lokasi berhasil diupdate",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Location updated successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Forbidden - Admin only"),
     *     @OA\Response(response=404, description="Lokasi tidak ditemukan"),
     *     @OA\Response(response=422, description="Validasi gagal")
     * )
     */
    public function update(LocationRequest $request, int $id): JsonResponse
    {
        $location = Location::findOrFail($id);

        $location->update([
            'name' => $request->name,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'radius' => $request->radius,
        ]);

        return response()->json([
            'message' => 'Location updated successfully',
            'data' => $location
        ]);
    }
}
