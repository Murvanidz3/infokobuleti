<?php

namespace App\Livewire\Account;

use App\Actions\Messaging\SendMessage;
use App\Enums\ReportReason;
use App\Exceptions\MessagingBlocked;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Report;
use App\Models\User;
use App\Services\Account\Inbox;
use App\Services\DateFormatter;
use App\Support\Listings\PublicUrl;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Inbox and conversation thread. New messages arrive by polling every few seconds (real-time
 * push with Reverb can replace the polling later without changing the pages).
 */
class Messages extends Component
{
    use WithFileUploads;

    public const int PAGE = 30;

    #[Locked]
    public ?int $conversationId = null;

    public string $body = '';

    /** @var array<int, mixed> */
    public array $images = [];

    public int $limit = self::PAGE;

    public string $reportReason = '';

    public string $reportComment = '';

    public function mount(Inbox $inbox, ?int $conversationId = null): void
    {
        if ($conversationId !== null) {
            $conversation = Conversation::query()->findOrFail($conversationId);
            abort_unless($inbox->isParticipant($this->user(), $conversation), 404);
            $this->conversationId = $conversation->id;
        }
    }

    public function send(SendMessage $action): void
    {
        $conversation = $this->conversation();

        if ($conversation === null) {
            return;
        }

        $this->validate([
            'body' => [$this->images === [] ? 'required' : 'nullable', 'string', 'max:2000'],
            'images' => ['array', 'max:'.SendMessage::MAX_ATTACHMENTS],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ], [], ['body' => __('account.messages.message'), 'images.*' => __('account.messages.photo')]);

        $key = 'send-message:'.$this->user()->id;

        if (RateLimiter::tooManyAttempts($key, 30)) {
            $this->addError('body', __('account.messages.too_fast'));

            return;
        }

        RateLimiter::hit($key, 60);

        try {
            $action->handle($this->user(), $conversation, $this->body, array_values(array_filter($this->images, fn ($image): bool => $image instanceof UploadedFile)));
        } catch (MessagingBlocked) {
            $this->addError('body', __('account.messages.blocked'));

            return;
        }

        $this->reset('body', 'images');
        $this->dispatch('message-sent');
    }

    public function removeImage(int $index): void
    {
        unset($this->images[$index]);
        $this->images = array_values($this->images);
    }

    public function loadOlder(): void
    {
        $this->limit += self::PAGE;
    }

    public function block(): void
    {
        $other = $this->conversation()?->otherParticipant($this->user());

        if ($other !== null) {
            $this->user()->blockedUsers()->syncWithoutDetaching([$other->id => ['created_at' => now()]]);
            $this->dispatch('toast', message: __('account.messages.blocked_done'), type: 'success');
        }
    }

    public function unblock(): void
    {
        $other = $this->conversation()?->otherParticipant($this->user());

        if ($other !== null) {
            $this->user()->blockedUsers()->detach($other->id);
            $this->dispatch('toast', message: __('account.messages.unblocked'), type: 'success');
        }
    }

    public function report(): void
    {
        $conversation = $this->conversation();

        if ($conversation === null) {
            return;
        }

        $data = $this->validate([
            'reportReason' => ['required', Rule::enum(ReportReason::class)],
            'reportComment' => ['nullable', 'string', 'max:1000'],
        ], [], ['reportReason' => __('account.messages.report_reason')]);

        Report::query()->firstOrCreate(
            ['user_id' => $this->user()->id, 'reportable_type' => $conversation->getMorphClass(), 'reportable_id' => $conversation->id, 'status' => 'open'],
            ['reason' => $data['reportReason'], 'comment' => $data['reportComment'] ?: null],
        );

        $this->reset('reportReason', 'reportComment');
        $this->dispatch('report-sent');
        $this->dispatch('toast', message: __('account.messages.reported'), type: 'success');
    }

    public function archive(): void
    {
        $conversation = $this->conversation();

        if ($conversation !== null) {
            DB::table('conversation_participants')
                ->where('conversation_id', $conversation->id)
                ->where('user_id', $this->user()->id)
                ->update(['archived_at' => now()]);

            $this->redirect(localized_route('account.messages'));
        }
    }

    public function render(Inbox $inbox, DateFormatter $dates): View
    {
        $user = $this->user();
        $conversation = $this->conversation();
        $thread = null;

        if ($conversation !== null) {
            $inbox->markRead($user, $conversation);
            $thread = $this->thread($conversation, $dates);
        }

        $conversations = $inbox->conversations($user)->limit(60)->get()
            ->map(fn (Conversation $item): array => $this->summary($item, $user, $dates))
            ->all();

        return view('livewire.account.messages', [
            'conversations' => $conversations,
            'thread' => $thread,
            'reasons' => ReportReason::options(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function thread(Conversation $conversation, DateFormatter $dates): array
    {
        $user = $this->user();
        $conversation->loadMissing(['participants', 'subject']);
        $other = $conversation->otherParticipant($user);

        $total = Message::query()->where('conversation_id', $conversation->id)->count();
        $messages = Message::query()
            ->where('conversation_id', $conversation->id)
            ->with('media')
            ->latest('created_at')
            ->latest('id')
            ->limit($this->limit)
            ->get()
            ->reverse()
            ->values();

        $lastReadByOther = $other !== null ? DB::table('conversation_participants')->where('conversation_id', $conversation->id)->where('user_id', $other->id)->value('last_read_at') : null;

        $items = [];
        $previousDay = null;

        foreach ($messages as $message) {
            $created = $message->created_at ?? now();
            $day = $dates->relativeDay($created);

            $items[] = [
                'id' => $message->id,
                'mine' => $message->user_id === $user->id,
                'body' => $message->body,
                'time' => $dates->time($created),
                'day' => $day !== $previousDay ? $day : null,
                'read' => $message->user_id === $user->id && $lastReadByOther !== null && $created->lessThanOrEqualTo($lastReadByOther),
                'images' => $message->getMedia('attachments')->map(fn (Media $media): string => localized_route('account.messages.attachment', ['conversation' => $conversation->id, 'media' => $media->id]))->all(),
            ];
            $previousDay = $day;
        }

        return [
            'id' => $conversation->id,
            'other' => $other->name ?? __('account.messages.deleted_user'),
            'initials' => $other?->initials() ?? '',
            'subject' => $this->subjectData($conversation->subject, withImage: true),
            'messages' => $items,
            'hasOlder' => $total > count($items),
            'blockedByMe' => $other !== null && $user->blockedUsers()->whereKey($other->id)->exists(),
            'blocked' => $other !== null && $user->isBlockedWith($other),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function summary(Conversation $conversation, User $user, DateFormatter $dates): array
    {
        $other = $conversation->participants->first();
        $last = $conversation->latestMessage;

        return [
            'id' => $conversation->id,
            'url' => localized_route('account.messages.show', ['conversation' => $conversation->id]),
            'name' => $other->name ?? __('account.messages.deleted_user'),
            'initials' => $other?->initials() ?? '',
            'subject' => $this->subjectData($conversation->subject)['title'] ?? null,
            'preview' => $last === null ? '' : ($last->user_id === $user->id ? __('account.messages.you').': ' : '').($last->body ?? __('account.messages.photo')),
            'time' => $conversation->last_message_at !== null ? $dates->relative($conversation->last_message_at) : '',
            'unread' => (int) $conversation->getAttribute('unread_count'),
            'active' => $conversation->id === $this->conversationId,
        ];
    }

    /**
     * @return array{title: string, url: ?string, image: ?string}|null
     */
    private function subjectData(?Model $subject, bool $withImage = false): ?array
    {
        if ($subject === null) {
            return null;
        }

        if ($withImage && method_exists($subject, 'coverUrl')) {
            $subject->loadMissing('media');
        }

        return [
            'title' => (string) ($subject->getAttribute('title') ?? $subject->getAttribute('name')),
            'url' => PublicUrl::for($subject),
            'image' => method_exists($subject, 'coverUrl') && $subject->relationLoaded('media') ? $subject->coverUrl('thumb') : null,
        ];
    }

    private function conversation(): ?Conversation
    {
        if ($this->conversationId === null) {
            return null;
        }

        return Conversation::query()
            ->whereKey($this->conversationId)
            ->whereHas('participants', fn ($query) => $query->whereKey($this->user()->id))
            ->first();
    }

    private function user(): User
    {
        /** @var User */
        return auth()->user();
    }
}
