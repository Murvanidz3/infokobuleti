<?php

use App\Contracts\AlertChannel;
use App\Enums\Role;
use App\Models\User;
use App\Services\Monitoring\Alerts;
use App\Services\Monitoring\HealthChecks;
use App\Services\Settings;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Spatie\Backup\Events\BackupHasFailed;

function recordingChannel(): AlertChannel
{
    return new class implements AlertChannel
    {
        /** @var list<array{0: string, 1: string}> */
        public array $sent = [];

        public function enabled(): bool
        {
            return true;
        }

        public function send(string $title, string $text): void
        {
            $this->sent[] = [$title, $text];
        }
    };
}

it('sends an alert once per repeat window and again after the problem was resolved', function () {
    $channel = recordingChannel();
    $alerts = new Alerts(Cache::store(), [$channel]);

    expect($alerts->raise('disk', 'Disk', 'full'))->toBeTrue()
        ->and($alerts->raise('disk', 'Disk', 'full'))->toBeFalse();

    $alerts->resolve('disk');
    $alerts->raise('disk', 'Disk', 'full again');
    $alerts->raise('test', 'Test', 'forced', force: true);
    $alerts->raise('test', 'Test', 'forced', force: true);

    expect($channel->sent)->toHaveCount(4);
});

it('checks the system and alerts on failed jobs', function () {
    config(['monitoring.disk_free_percent' => 0, 'monitoring.memory_free_percent' => 0]);
    $channel = recordingChannel();
    app()->instance(Alerts::class, new Alerts(Cache::store(), [$channel]));
    DB::table('failed_jobs')->insert(['uuid' => (string) str()->uuid(), 'connection' => 'redis', 'queue' => 'default', 'payload' => '{}', 'exception' => 'x', 'failed_at' => now()]);

    $this->artisan('monitor:check')->assertSuccessful();

    $last = app(HealthChecks::class)->last();
    $checks = collect($last['checks'])->keyBy('key');

    expect($last['at'])->not->toBeNull()
        ->and($checks['failed_jobs']['ok'])->toBeFalse()
        ->and($checks['memory']['ok'])->not->toBeFalse()
        ->and($channel->sent)->toHaveCount(1)
        ->and($channel->sent[0][0])->toContain(__('monitoring.checks.failed_jobs'));
});

it('writes test alerts to the alert log', function () {
    $this->artisan('monitor:test-alert')->assertSuccessful();

    expect(glob(storage_path('logs/alerts-*.log')))->not->toBe([]);
});

it('creates a staff account with a one-time password', function () {
    $this->seed(RoleSeeder::class);

    $this->artisan('staff:create', ['email' => 'Owner@Example.com', 'name' => 'Owner'])
        ->expectsOutputToContain('One-time password:')
        ->assertSuccessful();

    $user = User::query()->where('email', 'owner@example.com')->sole();
    expect($user->hasRole(Role::SuperAdmin->value))->toBeTrue()
        ->and($user->isStaff())->toBeTrue();

    $this->artisan('staff:create', ['email' => 'x@example.com', 'name' => 'X', '--role' => 'user'])->assertFailed();
});

it('prints the launch checklist', function () {
    $this->artisan('launch:check')
        ->expectsOutputToContain('Debug mode off')
        ->expectsOutputToContain('items done')
        ->assertSuccessful();
});

it('closes robots.txt while production is in maintenance', function () {
    app()->detectEnvironment(fn () => 'production');
    app(Settings::class)->put(['site.maintenance' => true]);

    $this->get('/robots.txt')->assertOk()->assertSee("Disallow: /\n", false);
    $this->get('/')->assertStatus(503);
    $this->get('/up')->assertOk();
});

it('uses contact@infokobuleti.com as the public address', function () {
    expect(config('site.contact.email'))->toBe('contact@infokobuleti.com');
    $this->get('/contact')->assertSee('contact@infokobuleti.com');
});

it('turns backup failures into alerts', function () {
    $channel = recordingChannel();
    app()->instance(Alerts::class, new Alerts(Cache::store(), [$channel]));

    event(new BackupHasFailed(new Exception('disk full'), 'backups', 'infokobuleti-testing'));

    expect($channel->sent)->toHaveCount(1)
        ->and($channel->sent[0])->toBe([__('monitoring.backup.failed'), 'disk full']);
});

it('opens Pulse to super admins only', function () {
    config(['pulse.enabled' => true]);
    [$admin] = staffMember(Role::SuperAdmin);

    $this->actingAs(User::factory()->create())->get('/pulse')->assertForbidden();
    $this->actingAs($admin)->get('/pulse')->assertOk()->assertHeaderMissing('Content-Security-Policy');
});

it('keeps backups of the database and the uploads on the backups disk', function () {
    expect(config('backup.backup.destination.disks'))->toBe(['backups'])
        ->and(config('backup.backup.source.databases'))->toBe(['pgsql'])
        ->and(config('backup.backup.source.files.include'))->toBe([storage_path('app')])
        ->and(config('backup.cleanup.default_strategy.keep_monthly_backups_for_months'))->toBe(6);
});
