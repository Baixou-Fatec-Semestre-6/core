<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class HealthController extends Controller
{
    /**
     * Check API health.
     *
     * Returns the current status of the API service.
     */
    public function show(): JsonResponse
    {
        return response()->json([
            'data' => [
                'status' => 'ok',
                'service' => 'baixou-api',
            ],
        ]);
    }
}