<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

/**
 * Notification topics a user can opt in or out of by email (account notification settings).
 */
enum NotificationTopic: string
{
    use HasLabel;

    case Messages = 'messages';
    case Inquiries = 'inquiries';
    case Moderation = 'moderation';
    case Reminders = 'reminders';
    case SavedSearches = 'saved_searches';
}
