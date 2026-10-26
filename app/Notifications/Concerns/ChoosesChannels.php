<?php

namespace App\Notifications\Concerns;

use App\Enums\NotificationTopic;
use App\Models\User;

/**
 * In-app notifications always; email too when the user has an address and has not switched the
 * topic off in their notification settings.
 */
trait ChoosesChannels
{
    /**
     * @return list<string>
     */
    protected function channelsFor(User $notifiable, NotificationTopic $topic): array
    {
        return $notifiable->wantsEmailFor($topic) ? ['database', 'mail'] : ['database'];
    }
}
