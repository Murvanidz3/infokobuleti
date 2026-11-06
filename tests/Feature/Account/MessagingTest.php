<?php

use App\Actions\Messaging\SendMessage;
use App\Actions\Messaging\StartConversation;
use App\Livewire\Account\Messages;
use App\Models\Conversation;
use App\Models\Hotel;
use App\Models\Message;
use App\Models\Report;
use App\Models\User;
use App\Notifications\NewInquiry;
use App\Services\Account\Inbox;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->owner = User::factory()->create();
    $this->guest = User::factory()->create();
    $this->hotel = Hotel::factory()->published()->for($this->owner)->create();
    $this->conversation = app(StartConversation::class)->handle($this->guest, $this->owner, $this->hotel);
});

it('lists conversations with unread counts and opens a thread', function () {
    app(SendMessage::class)->handle($this->guest, $this->conversation, 'Is the room free in July?');
    app(SendMessage::class)->handle($this->guest, $this->conversation, 'For two adults.');

    expect(app(Inbox::class)->unreadCount($this->owner))->toBe(1)
        ->and(app(Inbox::class)->unreadCount($this->guest))->toBe(0);

    $this->actingAs($this->owner)->get('/account/messages')->assertOk()->assertSee('For two adults.');
    $this->actingAs($this->owner)->get("/account/messages/{$this->conversation->id}")->assertOk()->assertSee('For two adults.');

    expect(app(Inbox::class)->unreadCount($this->owner))->toBe(0);
});

it('keeps other people out of a conversation', function () {
    $stranger = User::factory()->create();

    $this->actingAs($stranger)->get("/account/messages/{$this->conversation->id}")->assertNotFound();
    Livewire::actingAs($stranger)->test(Messages::class, ['conversationId' => $this->conversation->id])->assertNotFound();
});

it('replies with text and photos and notifies only for the first unread message', function () {
    Notification::fake();
    Storage::fake('local');

    Livewire::actingAs($this->owner)->test(Messages::class, ['conversationId' => $this->conversation->id])
        ->set('body', 'Yes, it is free.')
        ->set('images', [UploadedFile::fake()->image('room.jpg', 800, 600)])
        ->call('send')
        ->assertHasNoErrors()
        ->assertSet('body', '')
        ->set('body', 'We also have parking.')
        ->call('send');

    expect(Message::query()->where('user_id', $this->owner->id)->count())->toBe(2);
    $message = Message::query()->where('user_id', $this->owner->id)->oldest('id')->first();
    expect($message->getMedia('attachments'))->toHaveCount(1);

    Notification::assertSentToTimes($this->guest, NewInquiry::class, 1);

    // Attachments are private: participants only.
    $media = $message->getFirstMedia('attachments');
    $this->actingAs($this->guest)->get("/account/messages/{$this->conversation->id}/attachments/{$media->id}")->assertOk();
    $this->actingAs(User::factory()->create())->get("/account/messages/{$this->conversation->id}/attachments/{$media->id}")->assertNotFound();
});

it('requires text or a photo', function () {
    Livewire::actingAs($this->owner)->test(Messages::class, ['conversationId' => $this->conversation->id])
        ->call('send')
        ->assertHasErrors('body');
});

it('blocks and unblocks the other user', function () {
    Livewire::actingAs($this->owner)->test(Messages::class, ['conversationId' => $this->conversation->id])
        ->call('block')
        ->assertSee(__('account.messages.blocked'));

    expect($this->guest->isBlockedWith($this->owner))->toBeTrue();

    Livewire::actingAs($this->guest)->test(Messages::class, ['conversationId' => $this->conversation->id])
        ->set('body', 'Hello?')
        ->call('send')
        ->assertHasErrors('body');

    $this->actingAs($this->guest)->post("/message/hotel/{$this->hotel->id}", ['body' => 'Please answer'])->assertSessionHas('toast', fn (array $toast) => $toast['type'] === 'error');

    Livewire::actingAs($this->owner)->test(Messages::class, ['conversationId' => $this->conversation->id])->call('unblock');
    expect($this->guest->isBlockedWith($this->owner))->toBeFalse();
});

it('reports a conversation to the moderators', function () {
    Livewire::actingAs($this->guest)->test(Messages::class, ['conversationId' => $this->conversation->id])
        ->set('reportReason', 'fraud')
        ->set('reportComment', 'Asks for prepayment by card')
        ->call('report')
        ->assertHasNoErrors();

    expect(Report::query()->sole())
        ->reportable_type->toBe('conversation')
        ->reportable_id->toBe($this->conversation->id)
        ->reason->value->toBe('fraud');
});

it('archives a conversation until a new message arrives', function () {
    app(SendMessage::class)->handle($this->guest, $this->conversation, 'First');

    Livewire::actingAs($this->owner)->test(Messages::class, ['conversationId' => $this->conversation->id])->call('archive');
    expect(app(Inbox::class)->conversations($this->owner)->count())->toBe(0);

    app(SendMessage::class)->handle($this->guest, $this->conversation, 'Second');
    expect(app(Inbox::class)->conversations($this->owner)->count())->toBe(1);
});

it('links the first message on a listing to the inbox', function () {
    $other = Hotel::factory()->published()->for($this->owner)->create();

    $this->actingAs($this->guest)->post("/message/hotel/{$other->id}", ['body' => 'Do you have parking?'])
        ->assertSessionHas('toast', fn (array $toast) => str_contains($toast['action']['url'], '/account/messages/'));

    expect(Conversation::query()->where('subject_id', $other->id)->sole()->messages()->count())->toBe(1);
});
