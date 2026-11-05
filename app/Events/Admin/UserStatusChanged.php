<?php

declare(strict_types=1);

namespace App\Events\Admin;

use App\Models\User;
use App\Enums\UserStatus;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when an admin activates/deactivates a user account.
 */
final class UserStatusChanged
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly User $user,
        public readonly ?Authenticatable $actor,
        public readonly UserStatus $oldStatus,
        public readonly UserStatus $newStatus,
    ) {
    }
}
