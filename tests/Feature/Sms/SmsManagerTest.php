<?php

use App\Enums\AccountType;
use App\Enums\Role;
use App\Enums\SmsAudience;
use App\Enums\SmsCampaignStatus;
use App\Enums\SmsStatus;
use App\Filament\Pages\SmsManager;
use App\Filament\Resources\SmsCampaigns\Pages\CreateSmsCampaign;
use App\Filament\Resources\SmsCampaigns\Pages\EditSmsCampaign;
use App\Filament\Resources\SmsCampaigns\Pages\ViewSmsCampaign;
use App\Filament\Resources\SmsMessages\Pages\ManageSmsMessages;
use App\Filament\Resources\SmsOptOuts\Pages\ManageSmsOptOuts;
use App\Filament\Resources\SmsTemplates\Pages\ManageSmsTemplates;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Jobs\SendSmsCampaign;
use App\Models\SmsCampaign;
use App\Models\SmsMessage;
use App\Models\SmsOptOut;
use App\Models\SmsTemplate;
use App\Models\User;
use App\Services\Settings;
use App\Services\Sms\Campaigns;
use App\Services\Sms\RecipientImport;
use App\Support\SmsText;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    config(['sms.default' => 'array', 'sms.sender' => 'KOBULETI']);
    [$this->admin] = staffMember(Role::SuperAdmin);
    $this->actingAs($this->admin);
    Filament::setServingStatus();
});

function campaign(array $attributes = []): SmsCampaign
{
    $campaign = SmsCampaign::query()->create([
        'name' => 'Summer',
        'body' => 'გამარჯობა {name}, ზაფხულის შეთავაზება!',
        'audience' => SmsAudience::Manual,
        'audience_options' => ['numbers' => "555 11 11 11\n555222222, +995555333333"],
        'append_footer' => false,
        ...$attributes,
    ]);

    return app(Campaigns::class)->prepare($campaign);
}

it('counts characters and SMS parts for Latin and Georgian texts', function () {
    expect(SmsText::segments('Hello'))->toBe(1)
        ->and(SmsText::segments(str_repeat('a', 160)))->toBe(1)
        ->and(SmsText::segments(str_repeat('a', 161)))->toBe(2)
        ->and(SmsText::segments(str_repeat('ა', 70)))->toBe(1)
        ->and(SmsText::segments(str_repeat('ა', 71)))->toBe(2)
        ->and(SmsText::segments(str_repeat('ა', 135)))->toBe(3)
        ->and(SmsText::length('[]'))->toBe(4)
        ->and(SmsText::render('Hi {name}, ok', ['phone' => '+995555111111', 'name' => null]))->toBe('Hi, ok')
        ->and(SmsText::render('Hi {name}!', ['phone' => '+995555111111', 'name' => 'Nika']))->toBe('Hi Nika!');
});

it('cleans typed numbers: format, duplicates, invalid entries and the opt-out list', function () {
    SmsOptOut::query()->create(['phone' => '+995577000000']);

    $result = app(RecipientImport::class)->fromText("555 12 34 56\n+995 555 12 34 56, 577000000; 12345\n599111111 599222222");

    expect(array_column($result['recipients'], 'phone'))->toBe(['+995555123456', '+995599111111', '+995599222222'])
        ->and($result['report'])->toMatchArray(['invalid_count' => 1, 'invalid' => ['12345'], 'duplicates' => 1, 'opted_out' => 1]);
});

it('reads CSV files with a header, a BOM and semicolons, or without a header', function () {
    $withHeader = tempnam(sys_get_temp_dir(), 'csv');
    file_put_contents($withHeader, "\xEF\xBB\xBFსახელი;ტელეფონი\nნიკა;555 11 11 11\nანა;+995555222222\n;bad\n");
    $plain = tempnam(sys_get_temp_dir(), 'csv');
    file_put_contents($plain, "Giorgi,555333333\nLika,555444444\n");

    $first = app(RecipientImport::class)->fromCsv($withHeader);
    $second = app(RecipientImport::class)->fromCsv($plain);

    expect($first['recipients'])->toBe([
        ['phone' => '+995555111111', 'name' => 'ნიკა', 'user_id' => null],
        ['phone' => '+995555222222', 'name' => 'ანა', 'user_id' => null],
    ])->and($first['report']['invalid_count'])->toBe(1)
        ->and($second['recipients'][1])->toBe(['phone' => '+995555444444', 'name' => 'Lika', 'user_id' => null]);
});

it('picks registered users with a verified phone by filters', function () {
    $this->admin->forceFill(['phone' => null])->save();
    $business = User::factory()->create(['phone' => '+995555111111', 'phone_verified_at' => now(), 'account_type' => AccountType::Business, 'name' => 'Biz']);
    User::factory()->create(['phone' => '+995555222222', 'phone_verified_at' => now(), 'account_type' => AccountType::Individual]);
    User::factory()->create(['phone' => '+995555333333', 'phone_verified_at' => null]);
    User::factory()->create(['phone' => '+995555444444', 'phone_verified_at' => now(), 'is_blocked' => true]);

    $all = app(RecipientImport::class)->fromUsers([]);
    $businesses = app(RecipientImport::class)->fromUsers(['account_type' => 'business']);

    expect(array_column($all['recipients'], 'phone'))->toEqualCanonicalizing(['+995555111111', '+995555222222'])
        ->and($businesses['recipients'])->toBe([['phone' => '+995555111111', 'name' => 'Biz', 'user_id' => $business->id]]);
});

it('creates a campaign as a checked draft from the admin and shows the review', function () {
    Livewire::test(CreateSmsCampaign::class)
        ->fillForm([
            'name' => 'Opening',
            'body' => 'Welcome {name}',
            'audience' => 'manual',
            'audience_options' => ['numbers' => "555111111\n555111111\nwrong"],
            'append_footer' => false,
            'respect_stop_list' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $campaign = SmsCampaign::query()->sole();

    expect($campaign)->status->toBe(SmsCampaignStatus::Draft)->recipients_count->toBe(1)->created_by->toBe($this->admin->id)
        ->and($campaign->import_report)->toMatchArray(['duplicates' => 1, 'invalid_count' => 1, 'sms_total' => 1]);

    Livewire::test(ViewSmsCampaign::class, ['record' => $campaign->getRouteKey()])
        ->assertOk()
        ->assertSee('+995 555 11 11 11')
        ->assertActionVisible('send')
        ->assertActionVisible('schedule');
});

it('imports recipients from an uploaded CSV file', function () {
    Storage::fake('local');
    $file = UploadedFile::fake()->createWithContent('list.csv', "name,phone\nNika,555111111\nAna,555222222\n");

    Livewire::test(CreateSmsCampaign::class)
        ->fillForm([
            'name' => 'CSV',
            'body' => 'Hi {name}',
            'audience' => 'csv',
            'audience_options' => ['csv' => $file],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $campaign = SmsCampaign::query()->sole();

    expect($campaign->recipients_count)->toBe(2)
        ->and($campaign->recipients[0])->toMatchArray(['phone' => '+995555111111', 'name' => 'Nika']);

    $campaign->delete();
    Storage::disk('local')->assertMissing((string) $campaign->audience_options['csv']);
});

it('sends a campaign in batches with personal texts and the footer, once', function () {
    app(Settings::class)->put(['sms.campaign_footer' => 'STOP: 90000']);
    $campaign = campaign(['append_footer' => true, 'recipients' => null]);
    $campaign->forceFill(['recipients' => [
        ['phone' => '+995555111111', 'name' => 'Nika', 'user_id' => null],
        ['phone' => '+995555222222', 'name' => 'Ana', 'user_id' => null],
        ['phone' => '+995555333333', 'name' => null, 'user_id' => null],
    ]])->save();

    Livewire::test(ViewSmsCampaign::class, ['record' => $campaign->getRouteKey()])->callAction('send')->assertNotified();

    $campaign->refresh();
    $texts = array_column(smsGateway()->messages, 'text', 'to');

    expect($campaign)->status->toBe(SmsCampaignStatus::Sent)->sent_count->toBe(3)->failed_count->toBe(0)->finished_at->not->toBeNull()
        ->and(smsGateway()->batches)->toBe(2)
        ->and($texts['+995555111111'])->toBe("გამარჯობა Nika, ზაფხულის შეთავაზება!\nSTOP: 90000")
        ->and($texts['+995555333333'])->toBe("გამარჯობა, ზაფხულის შეთავაზება!\nSTOP: 90000")
        ->and(collect(smsGateway()->messages)->every(fn (array $message): bool => $message['stop_list'] && ! $message['otp']))->toBeTrue()
        ->and(SmsMessage::query()->where('campaign_id', $campaign->id)->pluck('provider_message_id')->unique()->sort()->values()->all())->toBe(['batch-1', 'batch-2']);

    // Running the job again (a duplicate dispatch) sends nothing.
    $campaign->forceFill(['status' => SmsCampaignStatus::Sending])->save();
    (new SendSmsCampaign($campaign->id))->handle(app(App\Services\Sms\SmsManager::class), app(Campaigns::class));

    expect(smsGateway()->messages)->toHaveCount(3);
});

it('skips numbers that opted out after the campaign was prepared', function () {
    $campaign = campaign();
    SmsOptOut::query()->create(['phone' => '+995555222222']);

    app(Campaigns::class)->start($campaign);

    expect(array_column(smsGateway()->messages, 'to'))->toBe(['+995555111111', '+995555333333']);
});

it('marks a failed batch as failed and keeps the error', function () {
    smsGateway()->failing = true;
    $campaign = campaign();

    app(Campaigns::class)->start($campaign);

    expect($campaign->fresh())->status->toBe(SmsCampaignStatus::Failed)->failed_count->toBe(3)->error->toContain('fake gateway');
});

it('refuses to send when the balance is too low', function () {
    smsGateway()->balance = 2;
    $campaign = campaign();

    Livewire::test(ViewSmsCampaign::class, ['record' => $campaign->getRouteKey()])->callAction('send')->assertNotified(__('sms.notifications.not_done'));

    expect($campaign->fresh()->status)->toBe(SmsCampaignStatus::Draft)
        ->and(smsGateway()->messages)->toBe([]);
});

it('schedules, starts on time and can be cancelled', function () {
    $campaign = campaign();
    $later = campaign(['name' => 'Later']);

    Livewire::test(ViewSmsCampaign::class, ['record' => $campaign->getRouteKey()])
        ->callAction('schedule', data: ['scheduled_at' => now()->addMinutes(30)->format('Y-m-d H:i')])
        ->assertHasNoActionErrors();

    expect($campaign->fresh()->status)->toBe(SmsCampaignStatus::Scheduled);

    app(Campaigns::class)->start($later, now()->addDay());
    Livewire::test(ViewSmsCampaign::class, ['record' => $later->getRouteKey()])->callAction(TestAction::make('cancel'));
    expect($later->fresh()->status)->toBe(SmsCampaignStatus::Cancelled);

    $this->artisan('sms:send-scheduled')->assertSuccessful();
    expect($campaign->fresh()->status)->toBe(SmsCampaignStatus::Scheduled);

    $this->travel(31)->minutes();
    $this->artisan('sms:send-scheduled')->expectsOutputToContain('Started 1')->assertSuccessful();

    expect($campaign->fresh()->status)->toBe(SmsCampaignStatus::Sent)
        ->and($later->fresh()->status)->toBe(SmsCampaignStatus::Cancelled)
        ->and(smsGateway()->messages)->toHaveCount(3);
});

it('sends a test SMS without touching the campaign', function () {
    $campaign = campaign();

    Livewire::test(ViewSmsCampaign::class, ['record' => $campaign->getRouteKey()])
        ->callAction('test', data: ['phone' => '599 00 00 00'])
        ->assertHasNoActionErrors();

    expect(smsGateway()->last())->toMatchArray(['to' => '+995599000000', 'text' => 'გამარჯობა, ზაფხულის შეთავაზება!'])
        ->and($campaign->testMessages()->count())->toBe(1)
        ->and($campaign->messages()->count())->toBe(0);

    app(Campaigns::class)->start($campaign->fresh());
    expect($campaign->fresh()->sent_count)->toBe(3);
});

it('updates campaign figures from delivery reports and retries the failed numbers', function () {
    $campaign = campaign();
    app(Campaigns::class)->start($campaign);
    smsGateway()->reports['batch-1'] = ['995555111111' => 1, '995555222222' => 2];

    Livewire::test(ViewSmsCampaign::class, ['record' => $campaign->getRouteKey()])->callAction('refreshReports');

    expect($campaign->fresh())->delivered_count->toBe(1)->failed_count->toBe(1)->sent_count->toBe(2);

    Livewire::test(ViewSmsCampaign::class, ['record' => $campaign->getRouteKey()])->callAction('retryFailed');

    $retry = SmsCampaign::query()->latest('id')->firstOrFail();
    expect($retry)->status->toBe(SmsCampaignStatus::Draft)->recipients_count->toBe(1)
        ->and($retry->recipients[0]['phone'])->toBe('+995555222222')
        ->and($retry->name)->toStartWith(__('sms.campaigns.retry_prefix'));
});

it('exports the results of a campaign', function () {
    $campaign = campaign();
    app(Campaigns::class)->start($campaign);

    Livewire::test(ViewSmsCampaign::class, ['record' => $campaign->getRouteKey()])->callAction('export')->assertFileDownloaded('sms-campaign-'.$campaign->id.'.csv');
});

it('keeps sent campaigns read-only', function () {
    $campaign = campaign();
    app(Campaigns::class)->start($campaign);

    Livewire::test(ViewSmsCampaign::class, ['record' => $campaign->getRouteKey()])
        ->assertActionHidden('send')
        ->assertActionHidden('schedule')
        ->assertActionHidden('edit');

    Livewire::test(EditSmsCampaign::class, ['record' => $campaign->getRouteKey()])->assertRedirect();
    expect(fn () => app(Campaigns::class)->start($campaign->fresh()))->toThrow(RuntimeException::class);
});

it('sends a single SMS from the manager without the OTP flag and keeps the text', function () {
    $template = SmsTemplate::query()->create(['name' => 'Thanks', 'body' => 'Thank you!']);

    Livewire::test(SmsManager::class)
        ->assertOk()
        ->callAction('sendSms', data: ['phone' => '555 12 34 56', 'template_id' => $template->id, 'body' => 'Thank you!'])
        ->assertHasNoActionErrors();

    $message = SmsMessage::query()->sole();

    expect($message)->purpose->toBe(SmsMessage::PURPOSE_SINGLE)->body->toBe('Thank you!')->sent_by->toBe($this->admin->id)->status->toBe(SmsStatus::Sent)
        ->and(smsGateway()->last())->toMatchArray(['to' => '+995555123456', 'otp' => false]);

    Livewire::test(SmsManager::class)->callAction('sendSms', data: ['phone' => '12345', 'body' => 'x'])->assertHasActionErrors(['phone']);
});

it('sends an SMS to a user from the users table', function () {
    $user = User::factory()->create(['phone' => '+995555777777', 'phone_verified_at' => now()]);

    Livewire::test(ListUsers::class)
        ->callAction(TestAction::make('sendSms')->table($user), data: ['phone' => '+995 555 77 77 77', 'body' => 'Hello'])
        ->assertHasNoActionErrors();

    expect(SmsMessage::query()->sole()->user_id)->toBe($user->id);
});

it('saves the campaign footer and the test number', function () {
    Livewire::test(SmsManager::class)
        ->fillForm(['campaign_footer' => 'STOP 90000', 'test_phone' => '599 11 22 33'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(app(Settings::class)->get('sms.campaign_footer'))->toBe('STOP 90000')
        ->and(app(Settings::class)->get('sms.test_phone'))->toBe('+995599112233');
});

it('lists the history with sign-in code texts hidden and exports it', function () {
    SmsMessage::query()->create(['phone' => '+995555123456', 'purpose' => 'otp.login', 'driver' => 'array', 'status' => SmsStatus::Sent]);
    SmsMessage::query()->create(['phone' => '+995555654321', 'purpose' => 'admin.single', 'body' => 'Visible text', 'driver' => 'array', 'status' => SmsStatus::Delivered]);

    Livewire::test(ManageSmsMessages::class)
        ->assertOk()
        ->assertSee(__('sms.history.code_hidden'))
        ->assertSee('Visible text')
        ->searchTable('555 65 43 21')
        ->assertCanSeeTableRecords(SmsMessage::query()->where('phone', '+995555654321')->get())
        ->assertCanNotSeeTableRecords(SmsMessage::query()->where('phone', '+995555123456')->get())
        ->callAction('export')
        ->assertFileDownloaded();
});

it('adds numbers to the opt-out list one by one and by import', function () {
    Livewire::test(ManageSmsOptOuts::class)
        ->callAction('create', data: ['phone' => '555 11 11 11', 'reason' => 'asked'])
        ->assertHasNoActionErrors()
        ->callAction('create', data: ['phone' => '+995555111111'])
        ->assertHasActionErrors(['phone']);

    Livewire::test(ManageSmsOptOuts::class)
        ->callAction('import', data: ['numbers' => "555111111\n555222222\n555333333"])
        ->assertNotified(__('sms.notifications.opt_outs_imported', ['count' => 2]));

    expect(SmsOptOut::query()->orderBy('phone')->pluck('phone')->all())->toBe(['+995555111111', '+995555222222', '+995555333333'])
        ->and(SmsOptOut::query()->where('phone', '+995555111111')->value('created_by'))->toBe($this->admin->id);
});

it('manages templates', function () {
    Livewire::test(ManageSmsTemplates::class)
        ->callAction('create', data: ['name' => 'Promo', 'body' => 'Sale today'])
        ->assertHasNoActionErrors();

    expect(SmsTemplate::query()->sole()->body)->toBe('Sale today');
});

it('renders every SMS manager page for super admins only', function () {
    $campaign = campaign();

    foreach (['/admin/sms', '/admin/sms/campaigns', '/admin/sms/campaigns/create', "/admin/sms/campaigns/{$campaign->id}", "/admin/sms/campaigns/{$campaign->id}/edit", '/admin/sms/history', '/admin/sms/templates', '/admin/sms/opt-outs'] as $url) {
        $this->get($url)->assertOk();
    }

    User::query()->whereKey($this->admin->id)->update(['email' => 'old@example.com']);
    [$admin] = staffMember(Role::Admin);
    $this->flushSession();

    $this->actingAs($admin)->get('/admin/sms')->assertForbidden();
    $this->actingAs($admin)->get('/admin/sms/history')->assertForbidden();
});
