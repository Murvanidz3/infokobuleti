<?php

namespace App\Actions\Messaging;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * The conversation between two users about an item: the existing one, or a new one.
 */
class StartConversation
{
    public function handle(User $sender, User $owner, Model $subject): Conversation
    {
        return DB::transaction(function () use ($sender, $owner, $subject): Conversation {
            $conversation = Conversation::query()
                ->where('subject_type', $subject->getMorphClass())
                ->where('subject_id', $subject->getKey())
                ->whereHas('participants', fn (Builder $query) => $query->whereKey($sender->getKey()))
                ->whereHas('participants', fn (Builder $query) => $query->whereKey($owner->getKey()))
                ->lockForUpdate()
                ->first();

            if ($conversation === null) {
                $conversation = Conversation::query()->create(['subject_type' => $subject->getMorphClass(), 'subject_id' => $subject->getKey()]);
                $conversation->participants()->attach([$sender->getKey(), $owner->getKey()]);
            }

            return $conversation;
        });
    }
}
