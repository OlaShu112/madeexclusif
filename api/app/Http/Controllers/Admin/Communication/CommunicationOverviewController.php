<?php

namespace App\Http\Controllers\Admin\Communication;

use App\Http\Controllers\Controller;
use App\Services\Communication\CommunicationOverviewService;
use Illuminate\Http\JsonResponse;

class CommunicationOverviewController extends Controller
{
    public function __construct(
        private readonly CommunicationOverviewService $overviewService
    ) {
    }

    public function index(): JsonResponse
    {
        abort_unless(
            config('communication.enabled', false),
            503,
            'The Communication module is currently unavailable.'
        );

        return response()->json([

            'success' => true,

            'message' =>
                'Communication overview retrieved successfully.',

            'data' => $this->overviewService->overview(),

        ]);
    }
}