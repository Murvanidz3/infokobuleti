<?php

use App\Enums\ListingStatus;
use App\Enums\Role;
use App\Jobs\AnnounceToModerators;
use App\Models\Property;
use App\Models\User;
use App\Notifications\ListingModerated;
use App\Services\Telegram\TelegramLinks;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->bot = fakeTelegram();
    config(['services.telegram.moderation_chat_id' => '-100500']);

    $this->moderator = User::factory()->create(['telegram_chat_id' => '777']);
    $this->moderator->assignRole(Role::Moderator->value);
    User::factory()->create(['telegram_chat_id' => '888']); // linked, but not staff

    $this->listing = Property::factory()->pending()->create();
});

function callback(string $data, string $from = '777', string $chat = '777', int $message = 101): array
{
    return ['callback_query' => [
        'id' => 'cb-'.uniqid(),
        'from' => ['id' => (int) $from],
        'data' => $data,
        'message' => ['message_id' => $message, 'chat' => ['id' => (int) $chat, 'type' => 'private']],
    ]];
}

it('posts a listing that enters moderation to the moderators and the staff group', function () {
    AnnounceToModerators::dispatchSync('property', $this->listing->id);

    expect(collect($this->bot->sent)->pluck('chat')->sort()->values()->all())->toBe(['-100500', '777'])
        ->and($this->bot->sent[0]['html'])->toContain(e($this->listing->getTranslation('title', 'ka')))
        ->and($this->bot->sent[0]['buttons'][0][0][1])->toBe("mod:a:property:{$this->listing->id}");
});

it('approves from Telegram and updates every copy of the message', function () {
    Notification::fake();
    AnnounceToModerators::dispatchSync('property', $this->listing->id);
    $copy = collect($this->bot->sentTo('777'))->first();

    telegramUpdate(callback("mod:a:property:{$this->listing->id}", message: $copy['id']))->assertNoContent();

    expect($this->listing->fresh()->status)->toBe(ListingStatus::Published)
        ->and($this->listing->fresh()->moderated_by)->toBe($this->moderator->id)
        ->and(collect($this->bot->edited)->pluck('chat')->sort()->values()->all())->toBe(['-100500', '777']);

    Notification::assertSentTo($this->listing->user, ListingModerated::class);

    // A second press (or another moderator) changes nothing.
    telegramUpdate(callback("mod:a:property:{$this->listing->id}", message: $copy['id']));
    expect(end($this->bot->answers)['text'])->toBe(__('telegram.moderation.already_done', [], 'ka'));
});

it('rejects with a ready reason or a typed one', function () {
    AnnounceToModerators::dispatchSync('property', $this->listing->id);
    $copy = collect($this->bot->sentTo('777'))->first();

    telegramUpdate(callback("mod:r:property:{$this->listing->id}", message: $copy['id']));
    $reasons = end($this->bot->edited)['buttons'];
    expect($reasons[0][0][1])->toBe("mod:x:property:{$this->listing->id}:0");

    telegramUpdate(callback("mod:x:property:{$this->listing->id}:6", message: $copy['id']));
    expect($this->listing->fresh())->status->toBe(ListingStatus::Rejected)
        ->rejection_reason->toBe(__('moderation.reasons.prohibited', [], $this->listing->user->preferredLocale()));

    $other = Property::factory()->pending()->create();
    AnnounceToModerators::dispatchSync('property', $other->id);
    telegramUpdate(callback("mod:o:property:{$other->id}"));
    telegramUpdate(['message' => ['message_id' => 5, 'from' => ['id' => 777], 'chat' => ['id' => 777, 'type' => 'private'], 'text' => 'ფოტოები სხვა ობიექტისაა']]);

    expect($other->fresh())->status->toBe(ListingStatus::Rejected)->rejection_reason->toBe('ფოტოები სხვა ობიექტისაა');
});

it('ignores buttons pressed by people who may not moderate', function () {
    AnnounceToModerators::dispatchSync('property', $this->listing->id);

    telegramUpdate(callback("mod:a:property:{$this->listing->id}", from: '888', chat: '-100500'));

    expect($this->listing->fresh()->status)->toBe(ListingStatus::Pending)
        ->and(end($this->bot->answers)['text'])->toBe(__('telegram.moderation.not_allowed', [], 'ka'));
});

it('refuses updates without the webhook secret', function () {
    $this->postJson('/webhooks/telegram/test-secret', callback("mod:a:property:{$this->listing->id}"))->assertForbidden();
    $this->withHeaders(['X-Telegram-Bot-Api-Secret-Token' => 'test-secret'])->postJson('/webhooks/telegram/wrong', [])->assertForbidden();

    expect($this->listing->fresh()->status)->toBe(ListingStatus::Pending);
});

it('links and unlinks a Telegram chat to an account', function () {
    $user = User::factory()->create();
    $link = app(TelegramLinks::class)->deepLink($user);
    $token = substr((string) $link, strpos((string) $link, 'start=') + 6);

    expect($link)->toStartWith('https://t.me/infokobuleti_test_bot?start=');

    telegramUpdate(['message' => ['message_id' => 1, 'from' => ['id' => 4242], 'chat' => ['id' => 4242, 'type' => 'private'], 'text' => "/start {$token}"]])->assertNoContent();
    expect($user->fresh()->telegram_chat_id)->toBe('4242');

    // The token works once.
    telegramUpdate(['message' => ['message_id' => 2, 'from' => ['id' => 5555], 'chat' => ['id' => 5555, 'type' => 'private'], 'text' => "/start {$token}"]]);
    expect(User::query()->where('telegram_chat_id', '5555')->exists())->toBeFalse();

    telegramUpdate(['message' => ['message_id' => 3, 'from' => ['id' => 4242], 'chat' => ['id' => 4242, 'type' => 'private'], 'text' => '/stop']]);
    expect($user->fresh()->telegram_chat_id)->toBeNull();
});
