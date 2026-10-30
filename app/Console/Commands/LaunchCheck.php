<?php

namespace App\Console\Commands;

use App\Contracts\HumanVerifier;
use App\Enums\Role;
use App\Models\Page;
use App\Models\Project;
use App\Models\Property;
use App\Models\User;
use App\Services\Monitoring\HealthChecks;
use App\Services\Settings;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

/**
 * The production launch checklist with the state of every item: run it on the server before and
 * after opening the site (php artisan launch:check).
 */
#[Signature('launch:check')]
#[Description('Show the production launch checklist with the status of each item')]
class LaunchCheck extends Command
{
    /** E-mail addresses of the demo staff accounts (DemoUserSeeder). */
    private const array DEMO_EMAILS = ['superadmin@infokobuleti.com', 'admin@infokobuleti.com', 'moderator@infokobuleti.com'];

    public function handle(Settings $settings, HealthChecks $health): int
    {
        $staff = User::query()->whereHas('roles', fn ($query) => $query->whereIn('name', array_map(fn (Role $role): string => $role->value, array_filter(Role::cases(), fn (Role $role): bool => $role->isStaff()))))->get();
        $legal = Page::query()->whereIn('slug', ['privacy', 'terms'])->where('is_published', true)->get();
        $checks = collect($health->last()['checks'])->keyBy('key');
        $lastCheck = $health->last()['at'];
        $gateways = array_diff((array) config('payments.gateways'), ['fake']);
        $listings = Property::query()->count() + Project::query()->count();
        $withoutPhotos = Property::query()->whereDoesntHave('media')->count() + Project::query()->whereDoesntHave('media')->count();

        $rows = [
            ['Environment is production', app()->isProduction(), app()->environment()],
            ['Debug mode off', ! config('app.debug'), config('app.debug') ? 'APP_DEBUG=true' : 'off'],
            ['Site address uses HTTPS', str_starts_with((string) config('app.url'), 'https://'), (string) config('app.url')],
            ['SSL certificate', $checks->get('ssl')['ok'] ?? null, $checks->get('ssl')['value'] ?? 'not checked yet'],
            ['Site open to visitors', ! $settings->maintenanceMode(), $settings->maintenanceMode() ? 'closed (maintenance page)' : 'open'],
            ['Search engines allowed (robots.txt)', app()->isProduction() && config('seo.indexable') && ! $settings->maintenanceMode(), config('seo.indexable') ? 'SEO_INDEXABLE=true' : 'SEO_INDEXABLE=false'],
            ['Sitemap generated', File::exists(public_path('sitemap.xml')), File::exists(public_path('sitemap.xml')) ? date('Y-m-d H:i', (int) filemtime(public_path('sitemap.xml'))) : 'run sitemap:generate'],
            ['Search Console verification', filled($settings->get('seo.google_verification')), 'Admin > Settings > SEO'],
            ['Google Analytics 4', filled($settings->get('seo.ga4_id')), (string) ($settings->get('seo.ga4_id') ?: 'Admin > Settings > SEO')],
            ['Transactional e-mail provider', ! in_array(config('mail.default'), ['log', 'array'], true), (string) config('mail.default')],
            ['Card payments (BOG / TBC)', $gateways !== [], $gateways === [] ? 'none (balance only)' : implode(', ', $gateways)],
            ['SMS provider for sign-in codes', config('sms.default') !== 'log', (string) config('sms.default')],
            ['Google sign-in', filled(config('services.google.client_id')), filled(config('services.google.client_id')) ? 'configured' : 'missing'],
            ['Cloudflare Turnstile on forms', app(HumanVerifier::class)->enabled(), app(HumanVerifier::class)->enabled() ? 'configured' : 'missing keys'],
            ['Telegram bot (moderation, alerts)', filled(config('services.telegram.bot_token')), filled(config('services.telegram.bot_token')) ? 'configured' : 'postponed'],
            ['Legal pages published (privacy, terms)', $legal->count() === 2 && $legal->every(fn (Page $page): bool => mb_strlen(strip_tags((string) $page->body)) > 200), $legal->count().' of 2 published'],
            ['No demo data', ! User::query()->whereIn('email', self::DEMO_EMAILS)->exists(), User::query()->whereIn('email', self::DEMO_EMAILS)->exists() ? 'demo staff accounts found' : 'clean'],
            ['Real photos on listings', $listings === 0 ? null : $withoutPhotos === 0, $listings === 0 ? 'no listings yet' : "{$withoutPhotos} of {$listings} properties / projects without photos"],
            ['A super admin exists', $staff->contains(fn (User $user): bool => $user->hasRole(Role::SuperAdmin->value)), $staff->count().' staff account(s)'],
            ['Every staff account has 2FA', $staff->isNotEmpty() && $staff->every(fn (User $user): bool => $user->hasTwoFactorEnabled()), $staff->reject(fn (User $user): bool => $user->hasTwoFactorEnabled())->pluck('email')->implode(', ') ?: 'all'],
            ['Background jobs running', $checks->get('horizon')['ok'] ?? null, $checks->get('horizon')['value'] ?? 'not checked yet'],
            ['Scheduler running', $lastCheck !== null && $lastCheck->greaterThan(now()->subMinutes(15)), $lastCheck?->diffForHumans() ?? 'monitor:check never ran'],
            ['Backup restore test passed', ($restore = Cache::get(BackupRestoreTest::CACHE_KEY))['ok'] ?? null, isset($restore['at']) ? $restore['at'].' '.$restore['detail'] : 'never ran'],
            ['Recent backup', $checks->get('backup')['ok'] ?? null, $checks->get('backup')['value'] ?? 'not checked yet'],
            ['App icons and share picture', File::exists(public_path('favicon.ico')) && File::exists(public_path('icons/icon-512.png')) && File::exists(public_path('icons/og-default.jpg')), 'favicon, icons, og-default'],
            ['Custom error pages', File::exists(resource_path('views/errors/404.blade.php')) && File::exists(resource_path('views/errors/500.blade.php')), '404, 500, 503'],
        ];

        $this->table(['', 'Item', 'Status'], array_map(fn (array $row): array => [match ($row[1]) {
            true => 'OK', false => 'NO', default => '--'
        }, $row[0], $row[2]], $rows));

        $done = count(array_filter($rows, fn (array $row): bool => $row[1] === true));
        $this->line("{$done} of ".count($rows).' items done.');

        return self::SUCCESS;
    }
}
