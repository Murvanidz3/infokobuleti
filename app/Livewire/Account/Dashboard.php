<?php

namespace App\Livewire\Account;

use App\Enums\ListingGroup;
use App\Services\Account\OwnerListings;
use Illuminate\Contracts\View\View;

/**
 * Account home: listing counts per group, statistics, what needs attention and recent listings,
 * with the same quick actions as the listings page.
 */
class Dashboard extends MyListings
{
    public function mount(OwnerListings $listings): void {}

    public function render(OwnerListings $listings): View
    {
        $user = $this->user();
        $attention = $listings->needingAttention($user);
        $attentionKeys = array_column($attention, 'key');

        return view('livewire.account.dashboard', [
            'counts' => $listings->counts($user)['all'],
            'totals' => $listings->totals($user),
            'groups' => ListingGroup::cases(),
            'attention' => $attention,
            'recent' => array_values(array_filter($listings->recent($user), fn (array $row): bool => ! in_array($row['key'], $attentionKeys, true))),
        ]);
    }
}
