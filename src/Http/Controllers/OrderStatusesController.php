<?php

namespace Ingenius\Orders\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Ingenius\Auth\Helpers\AuthHelper;
use Ingenius\Auth\Models\User;
use Ingenius\Core\Http\Controllers\Controller;
use Ingenius\Orders\Constants\OrderStatusPermissions;
use Ingenius\Orders\Enums\OrderStatusEnum;
use Ingenius\Orders\Http\Requests\StoreTransitionsStatusRequest;
use Ingenius\Orders\Models\OrderStatusTransition;
use Ingenius\Orders\Services\OrderStatusManager;

class OrderStatusesController extends Controller
{
    /**
     * The order status manager instance.
     */
    protected OrderStatusManager $statusManager;

    /**
     * Create a new controller instance.
     */
    public function __construct(OrderStatusManager $statusManager)
    {
        $this->statusManager = $statusManager;
    }

    /**
     * List all order statuses with their possible transitions.
     */
    public function index(): JsonResponse
    {
        $statuses = $this->statusManager->getStatuses();
        $formattedStatuses = [];

        foreach ($statuses as $identifier => $status) {
            $formattedStatuses[] = [
                'identifier' => $identifier,
                'name' => $status->getName(),
                'description' => $status->getDescription(),
            ];
        }

        return response()->json([
            'data' => $formattedStatuses,
            'message' => 'Order statuses fetched successfully'
        ]);
    }

    /**
     * Create multiple status transitions in one request.
     */
    public function storeTransitions(StoreTransitionsStatusRequest $request): JsonResponse
    {
        $user = AuthHelper::getUser();

        if (!$user instanceof User || !$user->can(OrderStatusPermissions::ORDER_STATUS_TRANSITIONS_CREATE)) {
            return response()->json([
                'message' => 'You are not authorized to create order status transitions'
            ], 403);
        }

        $transitions = $request->validated()['transitions'];
        $createdTransitions = [];
        $errors = [];
        $responseData = null;
        $responseMessage = '';
        $responseStatus = 200;
        $responseParams = [];

        DB::beginTransaction();

        try {
            foreach ($transitions as $index => $transitionData) {
                // Check for duplicate transitions
                $existingTransition = OrderStatusTransition::where('from_status', $transitionData['from_status'])
                    ->where('to_status', $transitionData['to_status'])
                    ->first();

                if ($existingTransition) {
                    $errors[] = [
                        'index' => $index,
                        'message' => "Transition from {$transitionData['from_status']} to {$transitionData['to_status']} already exists.",
                    ];
                    continue;
                }

                // Create the transition
                $transition = new OrderStatusTransition();
                $transition->from_status = $transitionData['from_status'];
                $transition->to_status = $transitionData['to_status'];
                $transition->is_enabled = $transitionData['is_enabled'] ?? true;
                $transition->sort_order = $transitionData['sort_order'] ?? 0;
                $transition->module = $transitionData['module'] ?? 'Orders';
                $transition->save();

                $createdTransitions[] = $transition;
            }

            // If there are errors, rollback the transaction
            if (!empty($errors)) {
                DB::rollBack();
                $responseMessage = 'Some transitions could not be created.';
                $responseStatus = 422;
                $responseParams = ['errors' => $errors];
            } else {
                DB::commit();
                $responseData = $createdTransitions;
                $responseMessage = 'Transitions created successfully.';
                $responseStatus = 201;
            }

            return response()->json([
                'message' => $responseMessage,
                'data' => $responseData,
                'params' => $responseParams
            ], $responseStatus);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to create transitions.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a specific transition.
     */
    public function deleteTransition($id): JsonResponse
    {
        $user = AuthHelper::getUser();

        if (!$user instanceof User || !$user->can(OrderStatusPermissions::ORDER_STATUS_TRANSITIONS_DELETE)) {
            return response()->json([
                'message' => 'You are not authorized to delete order status transitions'
            ], 403);
        }

        $transition = OrderStatusTransition::find($id);
        $message = '';
        $status = 200;

        if (!$transition || $this->isDefaultTransition($transition)) {
            $message = !$transition ? 'Transition not found.' : 'Cannot delete default transition.';
            $status = !$transition ? 404 : 403;
        } else {
            $transition->delete();
            $message = 'Transition deleted successfully.';
        }

        return response()->json(['message' => $message], $status);
    }

    /**
     * Clean up orphaned transitions (where status doesn't exist in the manager).
     */
    public function cleanupOrphanedTransitions(): JsonResponse
    {
        $user = AuthHelper::getUser();

        if (!$user instanceof User || !$user->can(OrderStatusPermissions::ORDER_STATUS_TRANSITIONS_DELETE)) {
            return response()->json([
                'message' => 'You are not authorized to delete order status transitions'
            ], 403);
        }

        // Get all registered status identifiers
        $registeredStatusIds = array_keys($this->statusManager->getStatuses());

        // Find transitions where from_status or to_status is not in registered statuses
        $orphanedTransitions = OrderStatusTransition::where(function ($query) use ($registeredStatusIds) {
            $query->whereNotIn('from_status', $registeredStatusIds)
                ->orWhereNotIn('to_status', $registeredStatusIds);
        })->get();

        $deletedCount = 0;
        $preservedDefaultCount = 0;

        foreach ($orphanedTransitions as $transition) {
            // Check if this is a default transition
            $isDefault = $this->isDefaultTransition($transition);

            if ($isDefault) {
                $preservedDefaultCount++;
                continue; // Skip default transitions
            }

            // Delete the orphaned transition
            $transition->delete();
            $deletedCount++;
        }

        return response()->json([
            'message' => 'Orphaned transitions cleanup completed.',
            'data' => [
                'deleted_count' => $deletedCount,
                'preserved_default_count' => $preservedDefaultCount,
            ]
        ]);
    }

    /**
     * Check if a transition is a default one.
     */
    private function isDefaultTransition(OrderStatusTransition $transition): bool
    {
        // Default transitions are those defined in the migration
        $defaultTransitions = [
            [
                'from_status' => OrderStatusEnum::NEW->value,
                'to_status' => OrderStatusEnum::COMPLETED->value,
            ],
            [
                'from_status' => OrderStatusEnum::NEW->value,
                'to_status' => OrderStatusEnum::CANCELLED->value,
            ],
        ];

        foreach ($defaultTransitions as $defaultTransition) {
            if (
                $transition->from_status === $defaultTransition['from_status'] &&
                $transition->to_status === $defaultTransition['to_status']
            ) {
                return true;
            }
        }

        return false;
    }
}
