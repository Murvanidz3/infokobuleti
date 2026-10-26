<div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_300px] xl:items-start">
    <section aria-labelledby="inbox-title">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 id="inbox-title" class="sr-only">{{ __('account.notifications.list') }}</h2>
            <div class="flex gap-2">
                <x-pill :active="! $unread" wire:click="$set('unread', false)">{{ __('account.notifications.all') }}</x-pill>
                <x-pill :active="$unread" wire:click="$set('unread', true)">
                    {{ __('account.notifications.unread') }}
                    @if ($unreadCount > 0)<span @class(['text-xs', 'text-white/70' => $unread, 'text-secondary' => ! $unread])>{{ $unreadCount }}</span>@endif
                </x-pill>
            </div>
            @if ($unreadCount > 0)
                <x-button size="sm" variant="ghost" icon="check" wire:click="markAllRead">{{ __('account.notifications.mark_all') }}</x-button>
            @endif
        </div>

        <ul class="mt-4 overflow-hidden rounded-card border border-line bg-white shadow-card" wire:loading.class="opacity-60" wire:target="unread, markAllRead">
            @forelse ($items as $item)
                <li wire:key="notification-{{ $item['id'] }}" @class(['relative flex gap-3.5 border-b border-line px-4 py-4 last:border-b-0 md:px-5', 'bg-coral-tint/40' => $item['unread']])>
                    <span @class([
                        'grid size-10 shrink-0 place-items-center rounded-full',
                        'bg-coral-tint text-coral-pressed' => $item['alert'],
                        'bg-surface-alt text-navy' => ! $item['alert'],
                    ])><x-icon :name="$item['icon']" :size="18" /></span>
                    <div class="min-w-0 flex-1">
                        @if ($item['url'])
                            <button type="button" wire:click="open('{{ $item['id'] }}')" class="text-left text-[15px] leading-snug font-semibold text-navy after:absolute after:inset-0 hover:text-coral-pressed">{{ $item['title'] }}</button>
                        @else
                            <p class="text-[15px] leading-snug font-semibold text-navy">{{ $item['title'] }}</p>
                        @endif
                        @if ($item['text'])
                            <p class="mt-1 line-clamp-2 text-[14px] leading-normal text-secondary">{{ $item['text'] }}</p>
                        @endif
                        <p class="mt-1.5 text-[12.5px] text-secondary">{{ $item['time'] }}</p>
                    </div>
                    @if ($item['unread'])
                        <button type="button" wire:click="markRead('{{ $item['id'] }}')" class="relative z-10 grid size-11 shrink-0 place-items-center self-center rounded-full text-secondary hover:bg-white hover:text-navy"
                                aria-label="{{ __('account.notifications.mark_read') }}" title="{{ __('account.notifications.mark_read') }}">
                            <span class="size-2.5 rounded-full bg-coral"></span>
                        </button>
                    @endif
                </li>
            @empty
                <li>
                    <x-empty-state class="border-0" icon="inbox" :title="__('account.notifications.empty_title')" :text="__('account.notifications.empty_text')" />
                </li>
            @endforelse
        </ul>

        <x-pagination :paginator="$paginator" class="mt-8" />
    </section>

    <aside aria-labelledby="preferences-title" class="rounded-panel border border-line bg-white p-5 shadow-card">
        <h2 id="preferences-title" class="font-serif text-[19px] font-semibold text-navy">{{ __('account.notifications.preferences') }}</h2>
        <p class="mt-1.5 text-[13.5px] leading-normal text-secondary">
            {{ $hasEmail ? __('account.notifications.preferences_hint') : __('account.notifications.no_email') }}
        </p>
        <form wire:submit="savePreferences" class="mt-4 flex flex-col">
            @foreach ($topics as $topic)
                <label @class(['flex min-h-11 cursor-pointer items-center justify-between gap-3 border-b border-line py-2 text-[14.5px] text-navy last:border-b-0', 'opacity-60' => ! $hasEmail])>
                    <span>{{ $topic->label() }}</span>
                    <input type="checkbox" wire:model="preferences.{{ $topic->value }}" @disabled(! $hasEmail) class="size-5 shrink-0 cursor-pointer rounded-[5px] accent-coral">
                </label>
            @endforeach
            @if ($hasEmail)
                <x-button type="submit" size="sm" class="mt-4 self-start">{{ __('account.common.save') }}</x-button>
            @else
                <x-button size="sm" variant="secondary" class="mt-4 self-start" :href="localized_route('account.profile')">{{ __('account.notifications.add_email') }}</x-button>
            @endif
        </form>
    </aside>
</div>
