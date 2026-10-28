<?php

namespace App\Services\Account;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;

/**
 * A user's conversations: list, unread state and read receipts.
 */
class Inbox
{
    /**
     * Conversations of the user, newest activity first, with the other participant, the subject,
     * the last message and an "unread_count" attribute.
     *
     * @return Builder<Conversation>
     */
    public function conversations(User $user): Builder
    {
        return Conversation::query()
            ->join('conversation_participants as me', function ($join) use ($user): void {
                $join->on('me.conversation_id', '=', 'conversations.id')->where('me.user_id', $user->getKey());
            })
            ->whereNull('me.archived_at')
            ->whereNotNull('conversations.last_message_at')
            ->select('conversations.*', 'me.last_read_at as my_last_read_at')
            ->selectSub(fn ($query) => $query
                ->from('messages')
                ->whereColumn('messages.conversation_id', 'conversations.id')
                ->where('messages.user_id', '!=', $user->getKey())
                ->whereNull('messages.deleted_at')
                ->whereRaw("messages.created_at > coalesce(me.last_read_at, '-infinity'::timestamp)")
                ->selectRaw('count(*)'), 'unread_count')
            ->with([
                'subject',
                'participants' => fn (BelongsToMany $query) => $query->whereKeyNot($user->getKey())->select('users.id', 'users.name', 'users.avatar'),
                'latestMessage',
            ])
            ->orderByDesc('conversations.last_message_at');
    }

    /**
     * Number of conversations with messages the user has not read yet.
     */
    public function unreadCount(User $user): int
    {
        return (int) DB::table('conversation_participants as me')
            ->where('me.user_id', $user->getKey())
            ->whereNull('me.archived_at')
            ->whereExists(fn ($query) => $query
                ->from('messages')
                ->whereColumn('messages.conversation_id', 'me.conversation_id')
                ->where('messages.user_id', '!=', $user->getKey())
                ->whereNull('messages.deleted_at')
                ->whereRaw("messages.created_at > coalesce(me.last_read_at, '-infinity'::timestamp)"))
            ->count();
    }

    public function isParticipant(User $user, Conversation $conversation): bool
    {
        return DB::table('conversation_participants')
            ->where('conversation_id', $conversation->getKey())
            ->where('user_id', $user->getKey())
            ->exists();
    }

    public function markRead(User $user, Conversation $conversation): void
    {
        DB::table('conversation_participants')
            ->where('conversation_id', $conversation->getKey())
            ->where('user_id', $user->getKey())
            ->update(['last_read_at' => now(), 'archived_at' => null]);
    }
}
