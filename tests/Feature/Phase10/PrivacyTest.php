<?php

use App\Actions\Privacy\ExportUserData;
use App\Enums\Role;
use App\Livewire\Account\Privacy;
use App\Models\Favorite;
use App\Models\Hotel;
use App\Models\Message;
use App\Models\Property;
use App\Models\User;
use App\Notifications\DataExportReady;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

it('exports the user data and sends a signed link that only the owner can use', function () {
    Storage::fake('local');
    Notification::fake();
    $user = User::factory()->create(['email' => 'me@example.com']);
    Property::factory()->published()->for($user)->create();

    Livewire::actingAs($user)->test(Privacy::class)
        ->call('requestExport')->assertDispatched('toast', type: 'success')
        ->call('requestExport')->assertDispatched('toast', type: 'error');

    $file = null;
    Notification::assertSentTo($user, DataExportReady::class, function (DataExportReady $notification, array $channels) use (&$file): bool {
        $file = $notification->file;

        return $channels === ['database', 'mail'];
    });

    $data = json_decode(Storage::disk('local')->get(ExportUserData::DIRECTORY.'/'.$file), true);
    expect($data['account']['email'])->toBe('me@example.com')
        ->and($data['listings']['properties'])->toHaveCount(1)
        ->and(json_encode($data))->not->toContain('remember_token');

    $url = (new DataExportReady($file))->toArray($user)['url'];
    $this->actingAs($user)->get($url)->assertOk()->assertDownload();
    $this->actingAs(User::factory()->create())->get($url)->assertNotFound();
    $this->actingAs($user)->get(str_replace('signature=', 'signature=x', $url))->assertForbidden();
});

it('deletes the account: listings go, the person is anonymized, records stay', function () {
    $user = User::factory()->create(['email' => 'gone@example.com', 'phone' => '+995555000111']);
    $hotel = Hotel::factory()->published()->for($user)->create();
    Favorite::query()->create(['user_id' => $user->id, 'favoritable_type' => 'hotel', 'favoritable_id' => Hotel::factory()->published()->create()->id]);

    Livewire::actingAs($user)->test(Privacy::class)
        ->set('confirming', true)
        ->set('confirmation', 'წაშალე')
        ->call('deleteAccount')->assertHasErrors('confirmation')
        ->set('confirmation', __('privacy.delete.word'))
        ->call('deleteAccount')->assertRedirect(localized_route('home'));

    $deleted = User::withTrashed()->findOrFail($user->id);

    expect($deleted->trashed())->toBeTrue()
        ->and($deleted->email)->toEndWith('@deleted.invalid')
        ->and($deleted->phone)->toBeNull()
        ->and($deleted->name)->toBe(__('privacy.deleted_user'))
        ->and(Hotel::query()->find($hotel->id))->toBeNull()
        ->and(Favorite::query()->where('user_id', $user->id)->count())->toBe(0)
        ->and(auth()->check())->toBeFalse();

    // The phone number is free for a new account.
    expect(User::factory()->create(['phone' => '+995555000111'])->exists)->toBeTrue();
});

it('does not let staff delete their own account', function () {
    [$staff] = staffMember(Role::Admin);

    Livewire::actingAs($staff)->test(Privacy::class)
        ->set('confirmation', __('privacy.delete.word'))
        ->call('deleteAccount')
        ->assertDispatched('toast', type: 'error');

    expect($staff->fresh()->trashed())->toBeFalse();
});

it('removes old export files', function () {
    Storage::fake('local');
    Storage::disk('local')->put('exports/1-old.json', '{}');
    Storage::disk('local')->put('exports/1-new.json', '{}');
    touch(Storage::disk('local')->path('exports/1-old.json'), now()->subDays(8)->getTimestamp());

    $this->artisan('privacy:prune-exports')->assertSuccessful();

    expect(Storage::disk('local')->exists('exports/1-old.json'))->toBeFalse()
        ->and(Storage::disk('local')->exists('exports/1-new.json'))->toBeTrue();
});

it('shows the cookie choice and the privacy section', function () {
    $this->get('/')->assertSee(__('privacy.cookies.accept'))->assertSee(__('privacy.cookies.settings'));
    $this->actingAs(User::factory()->create())->get('/account/security')->assertOk()->assertSee(__('privacy.export.title'))->assertSee(__('privacy.delete.title'));
    expect(Message::query()->count())->toBe(0);
});
