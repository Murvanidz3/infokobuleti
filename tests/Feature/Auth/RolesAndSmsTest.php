<?php

use App\Enums\Permission;
use App\Enums\Role;
use App\Enums\SmsStatus;
use App\Exceptions\SmsDeliveryFailed;
use App\Http\Controllers\Webhooks\TwilioStatusController;
use App\Models\SmsMessage;
use App\Models\User;
use App\Services\Sms\SmsManager;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Http;

it('gives each staff role its permissions', function () {
    $this->seed(RoleSeeder::class);

    $make = fn (Role $role) => User::factory()->create()->assignRole($role->value);

    $superAdmin = $make(Role::SuperAdmin);
    $admin = $make(Role::Admin);
    $moderator = $make(Role::Moderator);
    $user = $make(Role::User);

    expect($superAdmin->can(Permission::ManageSettings->value))->toBeTrue()
        ->and($superAdmin->can('anything-at-all'))->toBeTrue()
        ->and($admin->can(Permission::ManageUsers->value))->toBeTrue()
        ->and($admin->can(Permission::ManageSettings->value))->toBeFalse()
        ->and($moderator->can(Permission::ModerateContent->value))->toBeTrue()
        ->and($moderator->can(Permission::ManageUsers->value))->toBeFalse()
        ->and($user->can(Permission::AccessAdmin->value))->toBeFalse();
});

it('can re-run the role seeder safely', function () {
    $this->seed(RoleSeeder::class);
    $this->seed(RoleSeeder::class);

    expect(Spatie\Permission\Models\Role::count())->toBe(count(Role::cases()))
        ->and(Spatie\Permission\Models\Permission::count())->toBe(count(Permission::cases()));
});

it('sends SMS through Twilio', function () {
    config([
        'sms.default' => 'twilio',
        'sms.drivers.twilio' => ['account_sid' => 'AC123', 'auth_token' => 'token', 'from' => 'INFOKOBULETI', 'messaging_service_sid' => null, 'status_callback' => true],
    ]);
    Http::fake(['api.twilio.com/*' => Http::response(['sid' => 'SM42'], 201)]);

    $id = app(SmsManager::class)->driver('twilio')->send('+995555123456', 'Hello');

    expect($id)->toBe('SM42');
    Http::assertSent(fn ($request) => $request->url() === 'https://api.twilio.com/2010-04-01/Accounts/AC123/Messages.json'
        && $request['To'] === '+995555123456'
        && $request['From'] === 'INFOKOBULETI'
        && $request['StatusCallback'] === route('webhooks.sms.twilio'));
});

it('reports Twilio errors as failed deliveries', function () {
    config(['sms.drivers.twilio' => ['account_sid' => 'AC123', 'auth_token' => 'token', 'from' => 'X', 'messaging_service_sid' => null, 'status_callback' => false]]);
    Http::fake(['api.twilio.com/*' => Http::response(['code' => 21211, 'message' => 'Invalid To'], 400)]);

    app(SmsManager::class)->driver('twilio')->send('+995555123456', 'Hello');
})->throws(SmsDeliveryFailed::class, 'Invalid To');

it('records Twilio delivery receipts with a valid signature only', function () {
    config(['sms.drivers.twilio.auth_token' => 'token']);
    $message = SmsMessage::query()->create(['phone' => '+995555123456', 'purpose' => 'otp.login', 'driver' => 'twilio', 'status' => SmsStatus::Sent, 'provider_message_id' => 'SM42']);
    $params = ['MessageSid' => 'SM42', 'MessageStatus' => 'delivered'];

    $this->post('/webhooks/sms/twilio', $params, ['X-Twilio-Signature' => 'forged'])->assertForbidden();

    $data = route('webhooks.sms.twilio');
    ksort($params);
    foreach ($params as $key => $value) {
        $data .= $key.$value;
    }
    $signature = base64_encode(hash_hmac('sha1', $data, 'token', true));

    $this->post('/webhooks/sms/twilio', $params, ['X-Twilio-Signature' => $signature])->assertNoContent();

    expect($message->fresh())->status->toBe(SmsStatus::Delivered)->delivered_at->not->toBeNull();
    expect(TwilioStatusController::class)->toBeString();
});
