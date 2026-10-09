<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreCustomerRequest;
use App\Http\Requests\Api\UpdateCustomerRequest;
use App\Models\User;
use App\Services\FirebaseFirestoreService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Notifications\CrmNotification;

use Throwable;

class CustomerController extends Controller
{
    private const COLLECTION = 'customers';

    public function __construct(
        private readonly FirebaseFirestoreService $firestore
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $customers = $this->firestore->getAll(self::COLLECTION);

            if (! $user->isAdmin()) {
                $customers = array_values(array_filter(
                    $customers,
                    fn (array $customer): bool => (string) ($customer['created_by'] ?? '')
                        === (string) $user->getAuthIdentifier()
                ));
            }

            return response()->json([
                'success' => true,
                'message' => 'Customers loaded successfully.',
                'data' => $customers,
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
            'created_by' => $request->user()->getAuthIdentifier(),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        User::query()
            ->whereKeyNot($request->user()->getAuthIdentifier())
            ->whereHas('roles', function ($query) {
                $query->whereIn('name', ['super-admin', 'admin', 'manager']);
            })
            ->each(function (User $user) use ($customer) {
                $user->notify(new CrmNotification(
                    title: 'New Customer Created',
                    message: 'A new customer has been added to the CRM.',
                    url: '/customers',
                    event: 'customer.created',
                ));
            });

        return response()->json([
            'success' => true,
            'message' => 'Customer created successfully.',
            'data' => $customer,
        ], 201);
    } catch (Throwable $exception) {
        return $this->serverError(
            $exception,
            'Customer create failed.'
        );
    }
}


    public function show(Request $request, string $id): JsonResponse
    {
        try {
            $customer = $this->findVisibleCustomer($request->user(), $id);

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
            if ($this->findVisibleCustomer($request->user(), $id) === null) {
                return $this->notFound();
            }

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

    public function destroy(Request $request, string $id): JsonResponse
    {
        try {
            if ($this->findVisibleCustomer($request->user(), $id) === null) {
                return $this->notFound();
            }

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

    private function findVisibleCustomer(User $user, string $id): ?array
    {
        $customer = $this->firestore->find(self::COLLECTION, $id);

        if (
            $customer === null
            || (
                ! $user->isAdmin()
                && (string) ($customer['created_by'] ?? '') !== (string) $user->getAuthIdentifier()
            )
        ) {
            return null;
        }

        return $customer;
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
