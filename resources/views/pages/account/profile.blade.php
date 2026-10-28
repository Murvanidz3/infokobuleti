<x-account.layout :title="__('account.profile.title')" :subtitle="__('account.profile.subtitle')">
    <div class="flex flex-col gap-6">
        <livewire:account.profile />
        @unless (auth()->user()->isStaff())
            <livewire:account.business-profile-form />
        @endunless
    </div>
</x-account.layout>
