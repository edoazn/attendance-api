<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * @OA\Post(
     *     path="/login",
     *     summary="Login user",
     *     description="Autentikasi user dan mendapatkan token akses",
     *     operationId="login",
     *     tags={"Authentication"},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"identity_number","password"},
     *
     *             @OA\Property(property="identity_number", type="string", format="string", example="12345678"),
     *             @OA\Property(property="password", type="string", format="password", example="password")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Login berhasil",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="token", type="string", example="1|abc123xyz..."),
     *             @OA\Property(property="user", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Budi Santoso"),
     *                 @OA\Property(property="identity_number", type="string", example="12345678"),
     *                 @OA\Property(property="email", type="string", example="budi@mahasiswa.ac.id"),
     *                 @OA\Property(property="role", type="string", example="mahasiswa")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Kredensial tidak valid",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Invalid credentials")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Validasi gagal"
     *     )
     * )
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('identity_number', $request->identity_number)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials',
            ], 401);
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'identity_number' => $user->identity_number,
                'email' => $user->email,
                'role' => $user->role,
            ],
        ]);
    }

    /**
     * @OA\Post(
     *     path="/logout",
     *     summary="Logout user",
     *     description="Logout dan menghapus semua token user",
     *     operationId="logout",
     *     tags={"Authentication"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Logout berhasil",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Logged out successfully")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     */
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        // Delete all tokens for the authenticated user
        $user->tokens()->delete();

        return response()->json([
            'message' => 'Logged out successfully',
        ]);
    }

    /**
     * @OA\Get(
     *     path="/profile",
     *     summary="Get user profile",
     *     description="Mendapatkan data profil user yang sedang login beserta kelas yang diikuti",
     *     operationId="profile",
     *     tags={"Authentication"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Berhasil mendapatkan profil",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="Budi Santoso"),
     *             @OA\Property(property="identity_number", type="string", example="211420108"),
     *             @OA\Property(property="email", type="string", nullable=true, example="budi@mahasiswa.ac.id"),
     *             @OA\Property(property="role", type="string", example="mahasiswa"),
     *             @OA\Property(property="classes", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="TI-2A"),
     *                     @OA\Property(property="academic_year", type="string", example="2024/2025")
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     */
    public function profile(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->load('classes');

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'identity_number' => $user->identity_number,
            'email' => $user->email,
            'role' => $user->role,
            'classes' => $user->classes->map(fn ($class) => [
                'id' => $class->id,
                'name' => $class->name,
                'academic_year' => $class->academic_year,
            ]),
        ]);
    }
}
