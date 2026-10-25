<?php

use App\Enums\ListingStatus;
use App\Enums\Role;
use App\Filament\Pages\ModerationQueue;
use App\Filament\Pages\ModerationReview;
use App\Filament\Resources\Hotels\Pages\ListHotels;
use App\Models\AdminListing;
use App\Models\Hotel;
use App\Models\Property;
use App\Models\User;
use App\Notifications\ListingModerated;
use App\Services\Auth\TwoFactorAuthenticator;
use Database\Seeders\RoleSeeder;
use Filament\Actions\Testing\TestAction;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    Notification::fake();

    [$this->moderator] = staffMember(Role::Moderator);
    $this->actingAs($this->moderator);

    $this->owner = User::factory()->create(['locale' => 'ru', 'email' => 'owner@example.com']);
    $this->property = Property::factory()->create(['user_id' => $this->owner->id, 'status' => ListingStatus::Pending]);
});

it('lists pending items from every module, newest first', function () {
    $hotel = Hotel::factory()->create(['status' => ListingStatus::Pending, 'created_at' => now()->addMinute()]);
    Hotel::factory()->create(['status' => ListingStatus::Published]);

    Livewire::test(ModerationQueue::class)
        ->assertCanSeeTableRecords([AdminListing::find('hotel:'.$hotel->id), AdminListing::find('property:'.$this->property->id)], inOrder: true)
        ->assertCountTableRecords(2);
});

it('approves from the queue and tells the owner', function () {
    Livewire::test(ModerationQueue::class)
        ->callAction(TestAction::make('approve')->table(AdminListing::find('property:'.$this->property->id)))
        ->assertHasNoErrors();

    $property = $this->property->fresh();

    expect($property->status)->toBe(ListingStatus::Published)
        ->and($property->moderated_by)->toBe($this->moderator->id)
        ->and($property->published_at)->not->toBeNull();

    Notification::assertSentTo($this->owner, ListingModerated::class, fn (ListingModerated $notification, array $channels, $notifiable, ?string $locale) => $notification->approved && $locale === 'ru' && in_array('mail', $channels, true));

    expect(Activity::query()->where('log_name', 'moderation')->where('subject_id', $property->id)->value('causer_id'))->toBe($this->moderator->id);
});

it('rejects with a reason template in the owner language and a note', function () {
    Livewire::test(ModerationQueue::class)
        ->callAction(TestAction::make('reject')->table(AdminListing::find('property:'.$this->property->id)), data: [
            'template' => 'bad_photos',
            'note' => 'Please upload real photos.',
        ])
        ->assertHasNoActionErrors();

    $property = $this->property->fresh();
    $expected = __('moderation.reasons.bad_photos', locale: 'ru').' Please upload real photos.';

    expect($property->status)->toBe(ListingStatus::Rejected)
        ->and($property->rejection_reason)->toBe($expected);

    Notification::assertSentTo($this->owner, ListingModerated::class, fn (ListingModerated $notification) => ! $notification->approved && $notification->reason === $expected);
});

it('requires a reason to reject', function () {
    Livewire::test(ModerationQueue::class)
        ->callAction(TestAction::make('reject')->table(AdminListing::find('property:'.$this->property->id)), data: ['template' => null, 'note' => null])
        ->assertHasActionErrors(['template' => 'required']);

    expect($this->property->fresh()->status)->toBe(ListingStatus::Pending);
    Notification::assertNothingSent();
});

it('renders the notification mail in the owner language', function () {
    app()->setLocale('ru');
    $mail = (new ListingModerated($this->property, approved: false, reason: 'x'))->toMail($this->owner);

    expect($mail->subject)->toBe(__('moderation.notification.rejected.subject', locale: 'ru'));
});

it('walks through the queue on the review screen', function () {
    $older = Hotel::factory()->create(['status' => ListingStatus::Pending, 'created_at' => now()->subHour()]);
    $this->property->forceFill(['created_at' => now()])->save();

    Livewire::test(ModerationReview::class, ['key' => 'property:'.$this->property->id])
        ->assertSee($this->property->getTranslation('title', 'ka'))
        ->callAction('approve')
        ->assertRedirect(ModerationReview::getUrl(['key' => 'hotel:'.$older->id]));

    Livewire::test(ModerationReview::class, ['key' => 'hotel:'.$older->id])
        ->callAction('skip')
        ->assertRedirect(ModerationQueue::getUrl());

    expect($older->fresh()->status)->toBe(ListingStatus::Pending);
});

it('approves from a module table too', function () {
    [$admin] = [User::factory()->staff()->create()->assignRole(Role::Admin->value)];
    app(TwoFactorAuthenticator::class)->enable($admin, 'JBSWY3DPEHPK3PXP');
    $this->actingAs($admin->fresh());

    $hotel = Hotel::factory()->create(['status' => ListingStatus::Pending]);

    Livewire::test(ListHotels::class)->callAction(TestAction::make('approve')->table($hotel));

    expect($hotel->fresh()->status)->toBe(ListingStatus::Published);
});
