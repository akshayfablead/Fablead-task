<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreCustomerRequest;
use App\Http\Requests\Api\UpdateCustomerRequest;
use App\Services\FirebaseFirestoreService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Throwable;

class CustomerController extends Controller
{
    private const COLLECTION = 'customers';

    public function __construct(
        private readonly FirebaseFirestoreService $firestore
    ) {
    }

    public function index(): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'message' => 'Customers loaded successfully.',
                'data' => $this->firestore->getAll(self::COLLECTION),
            ]);
        } catch (Throwable $exception) {
            return $this->serverError($exception, 'Customer list failed.');
        }
    }

    public function store(StoreCustomerRequest $request): JsonResponse
    {
        try {
            $now = now()->toISOString();

            $customer = $this->firestore->create(self::COLLECTION, [
                ...$request->validated(),
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Customer created successfully.',
                'data' => $customer,
            ], 201);
        } catch (Throwable $exception) {
            return $this->serverError($exception, 'Customer create failed.');
        }
    }

    public function show(string $id): JsonResponse
    {
        try {
            $customer = $this->firestore->find(self::COLLECTION, $id);

            if ($customer === null) {
                return $this->notFound();
            }

            return response()->json([
                'success' => true,
                'message' => 'Customer loaded successfully.',
                'data' => $customer,
            ]);
        } catch (Throwable $exception) {
            return $this->serverError($exception, 'Customer lookup failed.');
        }
    }

    public function update(
        UpdateCustomerRequest $request,
        string $id
    ): JsonResponse {
        try {
            $customer = $this->firestore->update(self::COLLECTION, $id, [
                ...$request->validated(),
                'updated_at' => now()->toISOString(),
            ]);

            if ($customer === null) {
                return $this->notFound();
            }

            return response()->json([
                'success' => true,
                'message' => 'Customer updated successfully.',
                'data' => $customer,
            ]);
        } catch (Throwable $exception) {
            return $this->serverError($exception, 'Customer update failed.');
        }
    }

    public function destroy(string $id): JsonResponse
    {
        try {
            if (! $this->firestore->delete(self::COLLECTION, $id)) {
                return $this->notFound();
            }

            return response()->json([
                'success' => true,
                'message' => 'Customer deleted successfully.',
                'data' => null,
            ]);
        } catch (Throwable $exception) {
            return $this->serverError($exception, 'Customer delete failed.');
        }
    }

    private function notFound(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Customer not found.',
        ], 404);
    }

    private function serverError(
        Throwable $exception,
        string $logMessage
    ): JsonResponse {
        Log::error($logMessage, [
            'message' => $exception->getMessage(),
            'exception' => $exception,
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Server error. Please try again.',
        ], 500);
    }
}
