<?php

namespace App\Http\Controllers;

use App\Services\GoogleSheetsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class GoogleSheetsController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            $googleSheetsService = app(GoogleSheetsService::class);

            $rows = $googleSheetsService->values('Sheet1!A:F');
        } catch (\Throwable $exception) {
            Log::warning('Google Sheet rows could not be loaded.', [
                'message' => $exception->getMessage(),
            ]);

            return response()->json([
                'message' => 'Google Sheet could not be loaded.',
                'rows' => [],
            ], 502);
        }

        return response()->json([
            'rows' => $rows,
        ]);
    }
}
