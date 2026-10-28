<?php

namespace App\Services\Telegram;

use App\Actions\Moderation\ModerateListing;
use App\Contracts\Listing;
use App\Contracts\TelegramBot;
use App\Enums\ListingStatus;
use App\Enums\Permission;
use App\Enums\SiteFeature;
use App\Exceptions\InvalidStatusTransition;
use App\Filament\Support\Translatable;
use App\Models\User;
use App\Services\Account\OwnerListings;
use App\Support\Features;
use App\Support\GeorgianPhone;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Moderation from Telegram: every listing that enters the queue is posted to the staff who linked
 * Telegram (and to the optional staff group) with approve / reject buttons. A decision taken in
 * one chat updates the message in every other chat too.
 *
 * Callback data: "mod:{action}:{type}:{id}[:{reason}]" with action a (approve), r (show reasons),
 * x (reject with a template), o (reject with typed text), b (back).
 */
class ModerationBot
{
    /** How long the message ids are kept to update all copies. */
    private const int MESSAGES_TTL = 1209600;

    public function __construct(
        private TelegramBot $bot,
        private Cache $cache,
        private ModerateListing $moderate,
        private OwnerListings $listings,
    ) {}

    public function announce(Model&Listing $listing): int
    {
        if (! Features::active(SiteFeature::TelegramBot)) {
            return 0;
        }

        $listing->loadMissing(['user', 'media']);
        $html = $this->summary($listing);
        $photo = method_exists($listing, 'coverUrl') ? $listing->coverUrl() : null;
        $photo = $photo !== null && str_starts_with($photo, 'https://') ? $photo : null;
        $sent = [];

        foreach ($this->chats() as $chatId) {
            $id = $this->bot->send($chatId, $html, $this->decisionButtons($listing), $photo);

            if ($id !== null) {
                $sent[] = ['chat' => $chatId, 'id' => $id, 'caption' => $photo !== null];
            }
        }

        $this->cache->put($this->messagesKey($listing->getMorphClass(), (int) $listing->getKey()), $sent, self::MESSAGES_TTL);

        return count($sent);
    }

    /**
     * A button press. $fromId is the Telegram user who pressed it.
     */
    public function callback(string $callbackId, string $chatId, string $fromId, int $messageId, string $data): void
    {
        $parts = explode(':', $data);

        if (count($parts) < 4 || $parts[0] !== 'mod') {
            $this->bot->answerCallback($callbackId);

            return;
        }

        [, $action, $type, $id] = $parts;
        $moderator = $this->moderator($fromId);

        if ($moderator === null) {
            $this->bot->answerCallback($callbackId, __('telegram.moderation.not_allowed', [], 'ka'));

            return;
        }

        $listing = $this->listing($type, (int) $id);

        if ($listing === null || $listing->listingStatus() !== ListingStatus::Pending) {
            $this->bot->answerCallback($callbackId, __('telegram.moderation.already_done', [], 'ka'));

            if ($listing !== null) {
                $this->closeEverywhere($listing, $this->summary($listing)."\n\n".$this->statusLine($listing, null));
            }

            return;
        }

        $copy = $this->copy($listing, $chatId, $messageId);

        match ($action) {
            'a' => $this->decide($callbackId, $listing, $moderator, approve: true),
            'r' => $this->showReasons($callbackId, $listing, $chatId, $messageId, $copy['caption'] ?? false),
            'b' => $this->restore($callbackId, $listing, $chatId, $messageId, $copy['caption'] ?? false),
            'x' => $this->decide($callbackId, $listing, $moderator, approve: false, template: config('moderation.reasons')[(int) ($parts[4] ?? -1)] ?? null),
            'o' => $this->askForReason($callbackId, $listing, $chatId, $fromId),
            default => $this->bot->answerCallback($callbackId),
        };
    }

    /**
     * A typed message. Returns true when it was the rejection reason the moderator was asked for.
     */
    public function reasonMessage(string $chatId, string $fromId, string $text): bool
    {
        $pending = $this->cache->pull($this->reasonKey($chatId, $fromId));

        if (! is_string($pending)) {
            return false;
        }

        [$type, $id] = explode(':', $pending) + [1 => '0'];
        $moderator = $this->moderator($fromId);
        $listing = $this->listing($type, (int) $id);

        if ($moderator === null || $listing === null || $listing->listingStatus() !== ListingStatus::Pending) {
            $this->bot->send($chatId, e(__('telegram.moderation.already_done', [], 'ka')));

            return true;
        }

        $this->moderate->reject($listing, $moderator, null, mb_substr(trim($text), 0, 500));
        $this->closeEverywhere($listing, $this->summary($listing)."\n\n".$this->statusLine($listing, $moderator));
        $this->bot->send($chatId, e(__('telegram.moderation.rejected_short', [], 'ka')));

        return true;
    }

    private function decide(string $callbackId, Model&Listing $listing, User $moderator, bool $approve, ?string $template = null): void
    {
        try {
            $approve ? $this->moderate->approve($listing, $moderator) : $this->moderate->reject($listing, $moderator, $template);
        } catch (InvalidStatusTransition) {
            $this->bot->answerCallback($callbackId, __('telegram.moderation.already_done', [], 'ka'));

            return;
        }

        $this->bot->answerCallback($callbackId, __($approve ? 'telegram.moderation.approved_short' : 'telegram.moderation.rejected_short', [], 'ka'));
        $this->closeEverywhere($listing->refresh(), $this->summary($listing)."\n\n".$this->statusLine($listing, $moderator));
    }

    private function showReasons(string $callbackId, Model&Listing $listing, string $chatId, int $messageId, bool $caption): void
    {
        $key = $listing->getMorphClass().':'.$listing->getKey();
        $rows = [];

        foreach (config('moderation.reasons') as $index => $reason) {
            $rows[] = [[__('telegram.reasons.'.$reason, [], 'ka'), "mod:x:{$key}:{$index}"]];
        }

        $rows[] = [[__('telegram.moderation.other_reason', [], 'ka'), "mod:o:{$key}"], [__('telegram.moderation.back', [], 'ka'), "mod:b:{$key}"]];

        $this->bot->answerCallback($callbackId);
        $this->bot->edit($chatId, $messageId, $this->summary($listing)."\n\n<b>".e(__('telegram.moderation.choose_reason', [], 'ka')).'</b>', $rows, $caption);
    }

    private function restore(string $callbackId, Model&Listing $listing, string $chatId, int $messageId, bool $caption): void
    {
        $this->bot->answerCallback($callbackId);
        $this->bot->edit($chatId, $messageId, $this->summary($listing), $this->decisionButtons($listing), $caption);
    }

    private function askForReason(string $callbackId, Model&Listing $listing, string $chatId, string $fromId): void
    {
        $this->cache->put($this->reasonKey($chatId, $fromId), $listing->getMorphClass().':'.$listing->getKey(), 600);
        $this->bot->answerCallback($callbackId);
        $this->bot->send($chatId, e(__('telegram.moderation.type_reason', ['title' => $this->title($listing)], 'ka')));
    }

    /**
     * Replaces the buttons of every copy of the announcement with the final status.
     */
    private function closeEverywhere(Model&Listing $listing, string $html): void
    {
        $key = $this->messagesKey($listing->getMorphClass(), (int) $listing->getKey());

        foreach ((array) $this->cache->get($key, []) as $message) {
            $this->bot->edit((string) $message['chat'], (int) $message['id'], $html, [[[__('telegram.moderation.open_admin', [], 'ka'), $this->adminUrl($listing)]]], (bool) ($message['caption'] ?? false));
        }

        $this->cache->forget($key);
    }

    /**
     * @return array{chat: string, id: int, caption: bool}|null
     */
    private function copy(Model&Listing $listing, string $chatId, int $messageId): ?array
    {
        foreach ((array) $this->cache->get($this->messagesKey($listing->getMorphClass(), (int) $listing->getKey()), []) as $message) {
            if ((string) $message['chat'] === $chatId && (int) $message['id'] === $messageId) {
                return $message;
            }
        }

        return null;
    }

    public function summary(Model&Listing $listing): string
    {
        $row = $this->listings->rows([$listing])[0];
        $owner = ModerateListing::owner($listing->loadMissing('user'));
        $text = trim(Translatable::text(method_exists($listing, 'getTranslations') ? $listing->getTranslations('description') : null));

        $lines = [
            '<b>'.e(__('telegram.moderation.heading', [], 'ka')).'</b> · '.e(__('account.types.'.$listing->getMorphClass(), [], 'ka')),
            '',
            '<b>'.e($this->title($listing)).'</b>',
        ];

        if ($row['price']) {
            $lines[] = e((string) $row['price']);
        }

        if ($row['location']) {
            $lines[] = e((string) $row['location']);
        }

        if ($text !== '') {
            $lines[] = '';
            $lines[] = e(mb_strimwidth($text, 0, 400, '…'));
        }

        $lines[] = '';
        $lines[] = e(__('telegram.moderation.owner', [
            'name' => $owner->name ?? '—',
            'phone' => $owner?->phone !== null ? GeorgianPhone::format($owner->phone) : '—',
        ], 'ka'));

        return implode("\n", $lines);
    }

    /**
     * @return list<list<array{0: string, 1: string}>>
     */
    private function decisionButtons(Model&Listing $listing): array
    {
        $key = $listing->getMorphClass().':'.$listing->getKey();

        return [
            [[__('telegram.moderation.approve', [], 'ka'), "mod:a:{$key}"], [__('telegram.moderation.reject', [], 'ka'), "mod:r:{$key}"]],
            [[__('telegram.moderation.open_admin', [], 'ka'), $this->adminUrl($listing)]],
        ];
    }

    private function statusLine(Model&Listing $listing, ?User $moderator): string
    {
        $status = $listing->listingStatus();
        $line = match ($status) {
            ListingStatus::Published => __('telegram.moderation.approved', ['name' => $moderator->name ?? ''], 'ka'),
            ListingStatus::Rejected => __('telegram.moderation.rejected', ['name' => $moderator->name ?? '', 'reason' => (string) $listing->getAttribute('rejection_reason')], 'ka'),
            default => __('telegram.moderation.status', ['status' => $status->label()], 'ka'),
        };

        return '<b>'.e(trim($line)).'</b>';
    }

    /**
     * Private chats of staff who may moderate, plus the staff group.
     *
     * @return Collection<int, string>
     */
    private function chats(): Collection
    {
        $staff = User::query()
            ->whereNotNull('telegram_chat_id')
            ->where('is_blocked', false)
            ->with('roles.permissions', 'permissions')
            ->get()
            ->filter(fn (User $user): bool => $user->can(Permission::ModerateContent->value))
            ->pluck('telegram_chat_id')
            ->map(fn (mixed $id): string => (string) $id);

        $group = config('services.telegram.moderation_chat_id');

        return $staff->when(filled($group), fn (Collection $chats) => $chats->push((string) $group))->unique()->values();
    }

    private function moderator(string $fromId): ?User
    {
        $user = User::query()->where('telegram_chat_id', $fromId)->where('is_blocked', false)->first();

        return $user !== null && $user->can(Permission::ModerateContent->value) ? $user : null;
    }

    private function listing(string $type, int $id): (Model&Listing)|null
    {
        $class = OwnerListings::TYPES[$type] ?? null;

        if ($class === null) {
            return null;
        }

        /** @var (Model&Listing)|null */
        return $class::query()->with('user')->find($id);
    }

    private function title(Model $listing): string
    {
        return Translatable::text(method_exists($listing, 'getTranslations') ? $listing->getTranslations('title') : null);
    }

    private function adminUrl(Model $listing): string
    {
        return url('/admin/moderation/review/'.$listing->getMorphClass().':'.$listing->getKey());
    }

    private function messagesKey(string $type, int $id): string
    {
        return "telegram:moderation:{$type}:{$id}";
    }

    private function reasonKey(string $chatId, string $fromId): string
    {
        return "telegram:reason:{$chatId}:{$fromId}";
    }
}
