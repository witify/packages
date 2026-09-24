<?php

namespace Witify\Notifications\Policies;

use Illuminate\Database\Eloquent\Model;
use Witify\Notifications\Models\Notification;
use Witify\Notifications\QueryBuilders\NotificationQueryBuilder;

/**
 * Every read goes through NotificationQueryBuilder::protect(), which scopes the query
 * to the authenticated notifiable, so any authenticated user may list notifications.
 *
 * @see NotificationQueryBuilder
 */
class NotificationPolicy
{
    /**
     * Determine whether the user can view any notification.
     */
    public function viewAny(Model $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the notification.
     */
    public function view(Model $user, Notification $notification): bool
    {
        return (string) $notification->notifiable_id === (string) $user->getKey()
            && $notification->notifiable_type === $user->getMorphClass();
    }

    /**
     * Determine whether the user can update the notification.
     */
    public function update(Model $user, Notification $notification): bool
    {
        return $this->view($user, $notification);
    }
}
