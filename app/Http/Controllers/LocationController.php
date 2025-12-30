<?php

namespace App\Http\Controllers;

use App\Http\Requests\LocationRequest;
use App\Models\Location;
use Illuminate\Http\JsonResponse;

class LocationController extends Controller
{
    /**
     * Display a listing of all locations.
     */
    public function index(): JsonResponse
    {
        $locations = Location::all();

        return response()->json([
            'data' => $locations
        ]);
    }

    /**
     * Store a newly created location in storage.
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
     * Update the specified location in storage.
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
