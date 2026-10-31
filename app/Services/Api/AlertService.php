<?php

namespace App\Services\Api;

use App\Models\Alert;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;

class AlertService
{
    /**
     * Get alerts that belong to the authenticated user.
     *
     * @return Collection<int, Alert>
     */
    public function getForUser(User $user): Collection
    {
        return Alert::whereHas('productMonitor', fn ($query) => $query->where('user_id', $user->id))
            ->with('productMonitor')
            ->get();
    }

    /**
     * Create an alert for a product the user is monitoring.
     *
     * @param  array{product_id: int, target_price: int|float|string, notification_channel?: string}  $data
     */
    public function createForUser(User $user, array $data): Alert
    {
        $monitor = $user->productMonitors()
            ->where('product_id', $data['product_id'])
            ->firstOrFail();

        return $monitor->alerts()->create(
            Arr::only($data, ['target_price', 'notification_channel'])
        );
    }

    /**
     * Delete an alert owned by the authenticated user.
     */
    public function deleteForUser(User $user, int $alertId): void
    {
        Alert::whereHas('productMonitor', fn ($query) => $query->where('user_id', $user->id))
            ->findOrFail($alertId)
            ->delete();
    }
}
