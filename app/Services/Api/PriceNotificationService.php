<?php

namespace App\Services\Api;

use App\Models\PriceNotification;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class PriceNotificationService
{
    /**
     * Get price notifications owned by the user.
     *
     * @return Collection<int, PriceNotification>
     */
    public function getForUser(User $user): Collection
    {
        return $user->priceNotifications()
            ->with('product')
            ->latest()
            ->get();
    }

    /**
     * Mark all of the user's price notifications as read.
     */
    public function markAllAsRead(User $user): void
    {
        $user->priceNotifications()
            ->where('is_read', false)
            ->update(['is_read' => true]);
    }

    /**
     * Mark one of the user's price notifications as read.
     */
    public function markAsRead(User $user, int $notificationId): bool
    {
        $notification = $user->priceNotifications()->find($notificationId);

        if (! $notification) {
            return false;
        }

        $notification->update(['is_read' => true]);

        return true;
    }
}
