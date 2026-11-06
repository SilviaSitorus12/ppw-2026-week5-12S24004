<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

/**
 * GET /api/v1: titik masuk API (HATEOAS entry point).
 * Klien cukup mengetahui satu URL ini, lalu menemukan endpoint lain lewat _links.
 */
class ApiRootController extends Controller
{
    use ApiResponse;

    public function __invoke(): JsonResponse
    {
        return $this->successResponse(
            data: [
                'service' => 'SIPUS-Del REST API',
                'version' => 'v1',
                '_links'  => [
                    'self'        => ['href' => url('/api/v1'), 'method' => 'GET'],
                    'login'       => ['href' => url('/api/v1/auth/login'), 'method' => 'POST'],
                    'bukus'       => ['href' => url('/api/v1/bukus'), 'method' => 'GET'],
                    'peminjamans' => ['href' => url('/api/v1/peminjamans'), 'method' => 'GET'],
                    'soap_wsdl'   => ['href' => url('/soap/sipus.wsdl'), 'method' => 'GET'],
                ],
            ],
            message: 'Selamat datang di SIPUS-Del REST API v1.'
        );
    }
}