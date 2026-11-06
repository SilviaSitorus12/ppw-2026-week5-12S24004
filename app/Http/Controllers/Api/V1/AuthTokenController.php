<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * Otentikasi stateless berbasis Sanctum Bearer Token.
 * Tidak ada session/cookie: setiap request membawa identitasnya sendiri
 * lewat header "Authorization: Bearer <token>".
 */
class AuthTokenController extends Controller
{
    use ApiResponse;

    /** Masa berlaku token (hari). */
    private const TOKEN_TTL_DAYS = 7;

    /**
     * POST /api/v1/auth/login
     * Memverifikasi kredensial pustakawan & menerbitkan token baru.
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email'       => 'required|email|max:255',
            'password'    => 'required|string',
            'device_name' => 'nullable|string|max:100',
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return $this->problemResponse(
                title: 'Invalid Credentials',
                detail: 'Kombinasi email atau password yang diberikan tidak cocok.',
                status: 401,
                errorSlug: 'invalid-credentials'
            );
        }

        $deviceName = $validated['device_name'] ?? 'api-client';
        $expiresAt  = now()->addDays(self::TOKEN_TTL_DAYS);

        $token = $user->createToken($deviceName, ['*'], $expiresAt)->plainTextToken;

        return $this->successResponse(
            data: [
                'token_type'   => 'Bearer',
                'access_token' => $token,
                'expires_at'   => $expiresAt->toIso8601String(),
                'user'         => $this->userPayload($user),
                '_links'       => [
                    'me'     => ['href' => url('/api/v1/auth/me'), 'method' => 'GET'],
                    'logout' => ['href' => url('/api/v1/auth/logout'), 'method' => 'POST'],
                    'bukus'  => ['href' => url('/api/v1/bukus'), 'method' => 'GET'],
                ],
            ],
            message: 'Otentikasi berhasil. Lampirkan access_token pada header Authorization: Bearer <token>.'
        );
    }

    /**
     * GET /api/v1/auth/me (auth:sanctum)
     * Profil pemilik token aktif + informasi token yang sedang dipakai.
     */
    public function me(Request $request): JsonResponse
    {
        $user  = $request->user();
        $token = $user->currentAccessToken();

        return $this->successResponse(
            data: [
                'user'  => $this->userPayload($user),
                'token' => [
                    'name'         => $token->name,
                    'created_at'   => $token->created_at?->toIso8601String(),
                    'last_used_at' => $token->last_used_at?->toIso8601String(),
                    'expires_at'   => $token->expires_at?->toIso8601String(),
                ],
            ],
            message: 'Profil pemilik token aktif berhasil diambil.'
        );
    }

    /**
     * POST /api/v1/auth/logout (auth:sanctum)
     * Mencabut (revoke) HANYA token yang sedang dipakai; token di perangkat lain tetap aktif.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return $this->successResponse(
            data: null,
            message: 'Bearer Token berhasil dicabut (revoked). Token ini tidak dapat dipakai lagi.'
        );
    }

    /**
     * Representasi publik user: hanya kolom aman (tanpa password/remember_token).
     */
    private function userPayload(User $user): array
    {
        return [
            'id'    => $user->id,
            'name'  => $user->name,
            'email' => $user->email,
        ];
    }
}