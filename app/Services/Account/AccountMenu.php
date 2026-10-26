<?php

namespace App\Services\Account;

use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Sections of the account area for the current user, with unread badges. Hotel owners and
 * employers get their extra inboxes only once they have a hotel or a vacancy.
 */
class AccountMenu
{
    /**
     * @return list<array{key: string, label: string, icon: string, url: string, active: bool, badge: int}>
     */
    public function items(User $user): array
    {
        $flags = DB::query()
            ->selectRaw('exists(select 1 from hotels where user_id = ? and deleted_at is null) as has_hotels', [$user->getKey()])
            ->selectRaw('exists(select 1 from vacancies where user_id = ? and deleted_at is null) as has_vacancies', [$user->getKey()])
            ->selectRaw('(select count(*) from notifications where notifiable_type = ? and notifiable_id = ? and read_at is null) as unread_notifications', [$user->getMorphClass(), $user->getKey()])
            ->first();

        $items = [
            ['dashboard', 'account', 'grid', 0],
            ['listings', 'account.listings', 'list', 0],
            ['messages', 'account.messages', 'message', app(Inbox::class)->unreadCount($user)],
            ['bookings', 'account.bookings', 'calendar', 0, (bool) ($flags->has_hotels ?? false)],
            ['applications', 'account.applications', 'briefcase', 0, (bool) ($flags->has_vacancies ?? false)],
            ['favorites', 'account.favorites', 'heart', 0],
            ['searches', 'account.searches', 'search', 0],
            ['notifications', 'account.notifications', 'inbox', (int) ($flags->unread_notifications ?? 0)],
            ['profile', 'account.profile', 'user', 0],
            ['security', 'account.security', 'shield', 0],
            ['billing', 'account.billing', 'file', 0],
        ];

        $menu = [];

        foreach ($items as $item) {
            [$key, $route, $icon, $badge] = $item;

            if (($item[4] ?? true) === false) {
                continue;
            }

            $pattern = $route === 'account' ? [$route, '*.'.$route] : [$route, '*.'.$route, $route.'.*', '*.'.$route.'.*'];

            $menu[] = [
                'key' => $key,
                'label' => __('account.menu.'.$key),
                'icon' => $icon,
                'url' => localized_route($route),
                'active' => request()->routeIs(...$pattern),
                'badge' => $badge,
            ];
        }

        return $menu;
    }
}
