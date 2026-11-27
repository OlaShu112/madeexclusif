<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\System\SystemHealthService;
use Illuminate\Http\JsonResponse;

class SystemHealthController extends Controller
{
    public function __construct(
        private readonly SystemHealthService $health
    ) {
    }

    public function index(): JsonResponse
    {
        $result = $this->health->check();

        return response()->json([
            'success' => $result['healthy'],
            'data' => $result,
        ], $result['healthy'] ? 200 : 503);
    }
}