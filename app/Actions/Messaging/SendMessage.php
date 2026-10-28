<?php

namespace App\Actions\Messaging;

use App\Exceptions\MessagingBlocked;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Notifications\NewInquiry;
use App\Services\Media\ImageSanitizer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * Adds a message (text and/or up to four photos) to a conversation the sender takes part in.
 * The other participant is notified only for the first unread message, not for every line.
 */
class SendMessage
{
    public const int MAX_ATTACHMENTS = 4;

    public function __construct(private ImageSanitizer $sanitizer) {}

    /**
     * @param  list<UploadedFile>  $images
     *
     * @throws MessagingBlocked when either side blocked the other
     */
    public function handle(User $sender, Conversation $conversation, ?string $body, array $images = []): Message
    {
        $conversation->loadMissing('participants', 'subject');
        $recipient = $conversation->otherParticipant($sender);

        if ($recipient !== null && $sender->isBlockedWith($recipient)) {
            throw new MessagingBlocked;
        }

        $alreadyUnread = $recipient !== null && $this->hasUnread($conversation, $recipient);

        $message = DB::transaction(function () use ($sender, $conversation, $body, $images): Message {
            $message = Message::query()->create([
                'conversation_id' => $conversation->getKey(),
                'user_id' => $sender->getKey(),
                'body' => filled($body) ? trim((string) $body) : null,
            ]);

            foreach (array_slice($images, 0, self::MAX_ATTACHMENTS) as $image) {
                $this->sanitizer->sanitize($image->getRealPath());
                $message->addMedia($image->getRealPath())
                    ->usingFileName(bin2hex(random_bytes(8)).'.'.($image->guessExtension() ?: 'jpg'))
                    ->toMediaCollection('attachments');
            }

            DB::table('conversation_participants')
                ->where('conversation_id', $conversation->getKey())
                ->where('user_id', $sender->getKey())
                ->update(['last_read_at' => now(), 'archived_at' => null]);

            // A new message brings an archived conversation back for the other side too.
            DB::table('conversation_participants')
                ->where('conversation_id', $conversation->getKey())
                ->where('user_id', '!=', $sender->getKey())
                ->update(['archived_at' => null]);

            return $message;
        });

        if ($recipient !== null && ! $alreadyUnread && $conversation->subject !== null) {
            $recipient->notify(new NewInquiry(
                'message',
                $conversation->subject,
                ['name' => $sender->name, 'text' => $message->body ?? __('account.messages.photo')],
                localized_route('account.messages.show', ['conversation' => $conversation->getKey()], $recipient->preferredLocale()),
            ));
        }

        return $message;
    }

    private function hasUnread(Conversation $conversation, User $recipient): bool
    {
        $lastRead = DB::table('conversation_participants')
            ->where('conversation_id', $conversation->getKey())
            ->where('user_id', $recipient->getKey())
            ->value('last_read_at');

        return Message::query()
            ->where('conversation_id', $conversation->getKey())
            ->where('user_id', '!=', $recipient->getKey())
            ->when($lastRead !== null, fn ($query) => $query->where('created_at', '>', $lastRead))
            ->exists();
    }
}
