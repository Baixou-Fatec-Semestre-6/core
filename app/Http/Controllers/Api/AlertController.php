<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreAlertRequest;
use App\Http\Resources\AlertResource;
use App\Services\Api\AlertService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AlertController extends Controller
{
    public function __construct(private AlertService $alertService)
    {
        //
    }

    /**
     * List alerts belonging to the authenticated user.
     *
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        return AlertResource::collection(
            $this->alertService->getForUser($request->user()),
        );
    }

    /**
     * Create an alert for the authenticated user.
     *
     * @param StoreAlertRequest $request
     * @return JsonResponse
     */
    public function store(StoreAlertRequest $request): JsonResponse
    {
        $alert = $this->alertService->createForUser(
            $request->user(),
            $request->validated(),
        );

        return (new AlertResource($alert))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Delete an alert belonging to the authenticated user.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $this->alertService->deleteForUser($request->user(), $id);

        return response()->json(null, 204);
    }
}
