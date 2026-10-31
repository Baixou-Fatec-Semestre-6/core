<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PriceNotificationResource;
use App\Services\Api\PriceNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class NotificationController extends Controller
{
    public function __construct(private PriceNotificationService $priceNotificationService)
    {
        //
    }

    /**
     * Retrieve the authenticated user's notifications.
     *
     * @param  Request  $request  The incoming request.
     * @return AnonymousResourceCollection The user's notifications.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        return PriceNotificationResource::collection(
            $this->priceNotificationService->getForUser($request->user()),
        );
    }

    /**
     * Mark all of the authenticated user's notifications as read.
     *
     * @param  Request  $request  The incoming request.
     * @return JsonResponse A confirmation message.
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $this->priceNotificationService->markAllAsRead($request->user());

        return response()->json([
            'message' => 'Notificações marcadas como lidas.',
        ]);
    }

    /**
     * Mark one of the authenticated user's notifications as read.
     *
     * @param  Request  $request  The incoming request.
     * @param  int  $id  The notification identifier.
     * @return JsonResponse A confirmation or not-found message.
     */
    public function markAsRead(Request $request, int $id): JsonResponse
    {
        if (! $this->priceNotificationService->markAsRead($request->user(), $id)) {
            return response()->json([
                'message' => 'Notificação não encontrada.',
            ], 404);
        }

        return response()->json([
            'message' => 'Notificação marcada como lida.',
        ]);
    }
}
