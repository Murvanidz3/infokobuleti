<div wire:poll.5s.visible class="overflow-hidden rounded-panel border border-line bg-white shadow-card lg:grid lg:h-[min(720px,calc(100vh-220px))] lg:grid-cols-[320px_minmax(0,1fr)]">
    {{-- Conversation list (hidden on phones while a thread is open) --}}
    <aside @class(['flex min-h-0 flex-col border-line lg:border-r', 'max-lg:hidden' => $thread !== null]) aria-label="{{ __('account.messages.list') }}">
        @if ($conversations === [])
            <x-empty-state class="m-4 border-0" icon="message" :title="__('account.messages.empty_title')" :text="__('account.messages.empty_text')" />
        @else
            <ul class="min-h-0 flex-1 overflow-y-auto">
                @foreach ($conversations as $item)
                    <li wire:key="conversation-{{ $item['id'] }}">
                        <a href="{{ $item['url'] }}" @if ($item['active']) aria-current="page" @endif
                           class="flex gap-3 border-b border-line px-4 py-3.5 transition-colors hover:bg-surface-alt aria-[current=page]:bg-surface-alt">
                            <span class="grid size-11 shrink-0 place-items-center rounded-full bg-navy text-[14px] font-semibold text-white">
                                @if ($item['initials'] !== ''){{ $item['initials'] }}@else<x-icon name="user" :size="18" />@endif
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="flex items-baseline justify-between gap-2">
                                    <span @class(['truncate text-[15px] text-navy', 'font-semibold' => $item['unread'] > 0, 'font-medium' => $item['unread'] === 0])>{{ $item['name'] }}</span>
                                    <span class="shrink-0 text-[12px] text-secondary">{{ $item['time'] }}</span>
                                </span>
                                @if ($item['subject'])
                                    <span class="block truncate text-[12.5px] font-medium text-secondary">{{ $item['subject'] }}</span>
                                @endif
                                <span class="mt-0.5 flex items-center gap-2">
                                    <span @class(['min-w-0 flex-1 truncate text-[13.5px]', 'text-navy' => $item['unread'] > 0, 'text-secondary' => $item['unread'] === 0])>{{ $item['preview'] }}</span>
                                    @if ($item['unread'] > 0)
                                        <span class="grid h-5 min-w-5 shrink-0 place-items-center rounded-full bg-coral-pressed px-1.5 text-[11px] font-semibold text-white">{{ $item['unread'] }}</span>
                                    @endif
                                </span>
                            </span>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </aside>

    {{-- Thread --}}
    @if ($thread !== null)
        <section class="flex min-h-[70vh] flex-col lg:min-h-0" aria-labelledby="thread-title" x-data="{ menu: false }">
            <header class="flex items-center gap-3 border-b border-line px-4 py-3">
                <a href="{{ localized_route('account.messages') }}" class="grid size-11 shrink-0 place-items-center rounded-full text-navy hover:bg-surface-alt lg:hidden" aria-label="{{ __('account.common.back') }}">
                    <x-icon name="chevron-left" :size="20" />
                </a>
                @if ($thread['subject'] && $thread['subject']['image'])
                    <img src="{{ $thread['subject']['image'] }}" alt="" class="size-11 shrink-0 rounded-tile object-cover">
                @endif
                <div class="min-w-0 flex-1">
                    <h2 id="thread-title" class="truncate text-[15.5px] font-semibold text-navy">{{ $thread['other'] }}</h2>
                    @if ($thread['subject'])
                        @if ($thread['subject']['url'])
                            <a href="{{ $thread['subject']['url'] }}" class="block truncate text-[13px] font-medium text-secondary hover:text-coral-pressed">{{ $thread['subject']['title'] }}</a>
                        @else
                            <p class="truncate text-[13px] text-secondary">{{ $thread['subject']['title'] }}</p>
                        @endif
                    @endif
                </div>
                <div class="relative">
                    <button type="button" x-on:click="menu = ! menu" x-bind:aria-expanded="menu" aria-haspopup="true" aria-label="{{ __('account.messages.actions') }}"
                            class="grid size-11 place-items-center rounded-full text-navy hover:bg-surface-alt">
                        <x-icon name="menu" :size="20" />
                    </button>
                    <div x-show="menu" x-cloak x-on:click.outside="menu = false" x-transition.opacity
                         class="absolute top-[calc(100%+6px)] right-0 z-20 w-[230px] overflow-hidden rounded-tile border border-line bg-white shadow-overlay">
                        <button type="button" x-on:click="menu = false; $dispatch('open-dialog', 'report-conversation')" class="flex min-h-11 w-full items-center gap-3 px-4 text-left text-[14.5px] font-medium text-navy hover:bg-surface-alt">
                            <x-icon name="flag" :size="18" class="text-secondary" />{{ __('account.messages.report') }}
                        </button>
                        @if ($thread['blockedByMe'])
                            <button type="button" wire:click="unblock" x-on:click="menu = false" class="flex min-h-11 w-full items-center gap-3 px-4 text-left text-[14.5px] font-medium text-navy hover:bg-surface-alt">
                                <x-icon name="check" :size="18" class="text-secondary" />{{ __('account.messages.unblock') }}
                            </button>
                        @else
                            <button type="button" wire:click="block" wire:confirm="{{ __('account.messages.confirm_block') }}" x-on:click="menu = false" class="flex min-h-11 w-full items-center gap-3 px-4 text-left text-[14.5px] font-medium text-navy hover:bg-surface-alt">
                                <x-icon name="shield" :size="18" class="text-secondary" />{{ __('account.messages.block') }}
                            </button>
                        @endif
                        <button type="button" wire:click="archive" class="flex min-h-11 w-full items-center gap-3 border-t border-line px-4 text-left text-[14.5px] font-medium text-navy hover:bg-surface-alt">
                            <x-icon name="inbox" :size="18" class="text-secondary" />{{ __('account.messages.archive') }}
                        </button>
                    </div>
                </div>
            </header>

            <div class="min-h-0 flex-1 overflow-y-auto bg-surface-alt px-3 py-4 md:px-5"
                 x-data
                 x-init="$el.scrollTop = $el.scrollHeight"
                 x-on:message-sent.window="$nextTick(() => $el.scrollTop = $el.scrollHeight)">
                @if ($thread['hasOlder'])
                    <div class="mb-3 text-center">
                        <button type="button" wire:click="loadOlder" class="hit-44 relative text-[13.5px] font-semibold text-navy hover:text-coral-pressed">{{ __('account.messages.older') }}</button>
                    </div>
                @endif
                <ol class="flex flex-col gap-1.5" aria-live="polite">
                    @foreach ($thread['messages'] as $message)
                        @if ($message['day'])
                            <li class="my-2 text-center text-[12px] font-semibold text-secondary">{{ $message['day'] }}</li>
                        @endif
                        <li wire:key="message-{{ $message['id'] }}" @class(['flex', 'justify-end' => $message['mine']])>
                            <div @class([
                                'max-w-[82%] rounded-card px-3.5 py-2.5 text-[14.5px] leading-normal md:max-w-[70%]',
                                'rounded-br-[6px] bg-navy text-white' => $message['mine'],
                                'rounded-bl-[6px] border border-line bg-white text-navy' => ! $message['mine'],
                            ])>
                                @if ($message['images'] !== [])
                                    <div @class(['mb-1.5 grid gap-1.5', 'grid-cols-2' => count($message['images']) > 1])>
                                        @foreach ($message['images'] as $image)
                                            <a href="{{ $image }}" target="_blank" rel="noopener" class="block overflow-hidden rounded-tile">
                                                <img src="{{ $image }}" alt="{{ __('account.messages.photo') }}" loading="lazy" class="aspect-square w-full max-w-[220px] object-cover">
                                            </a>
                                        @endforeach
                                    </div>
                                @endif
                                @if ($message['body'])
                                    <p class="break-words whitespace-pre-line">{{ $message['body'] }}</p>
                                @endif
                                <p @class(['mt-1 flex items-center justify-end gap-1 text-[11.5px]', 'text-white/65' => $message['mine'], 'text-secondary' => ! $message['mine']])>
                                    {{ $message['time'] }}
                                    @if ($message['mine'])
                                        <x-icon :name="$message['read'] ? 'check-circle' : 'check'" :size="13" />
                                        <span class="sr-only">{{ $message['read'] ? __('account.messages.read') : __('account.messages.sent') }}</span>
                                    @endif
                                </p>
                            </div>
                        </li>
                    @endforeach
                </ol>
            </div>

            @if ($thread['blocked'])
                <p class="border-t border-line px-4 py-4 text-center text-[14px] text-secondary">{{ __('account.messages.blocked') }}</p>
            @else
                <form wire:submit="send" class="border-t border-line bg-white px-3 py-3 md:px-4">
                    @if ($images !== [])
                        <ul class="mb-2 flex gap-2">
                            @foreach ($images as $index => $image)
                                <li class="relative size-16 overflow-hidden rounded-tile bg-surface-alt" wire:key="image-{{ $index }}">
                                    @if (method_exists($image, 'isPreviewable') && $image->isPreviewable())
                                        <img src="{{ $image->temporaryUrl() }}" alt="" class="size-full object-cover">
                                    @endif
                                    <button type="button" wire:click="removeImage({{ $index }})" class="absolute top-0.5 right-0.5 grid size-7 place-items-center rounded-full bg-navy/80 text-white" aria-label="{{ __('account.wizard.photos.remove') }}">
                                        <x-icon name="close" :size="14" />
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                    @error('body')<p class="mb-2 text-[13px] font-medium text-coral-pressed">{{ $message }}</p>@enderror
                    @error('images.*')<p class="mb-2 text-[13px] font-medium text-coral-pressed">{{ $message }}</p>@enderror
                    <div class="flex items-end gap-2">
                        <label class="grid size-11 shrink-0 cursor-pointer place-items-center rounded-full text-navy hover:bg-surface-alt" title="{{ __('account.messages.attach') }}">
                            <x-icon name="image" :size="20" />
                            <span class="sr-only">{{ __('account.messages.attach') }}</span>
                            <input type="file" wire:model="images" accept="image/jpeg,image/png,image/webp" multiple class="sr-only">
                        </label>
                        <label for="message-body" class="sr-only">{{ __('account.messages.message') }}</label>
                        <textarea id="message-body" wire:model="body" rows="1" maxlength="2000"
                                  x-data x-on:input="$el.style.height = 'auto'; $el.style.height = Math.min($el.scrollHeight, 160) + 'px'"
                                  x-on:keydown.enter="if (! $event.shiftKey && window.matchMedia('(pointer: fine)').matches) { $event.preventDefault(); $wire.send() }"
                                  placeholder="{{ __('account.messages.placeholder') }}"
                                  class="block max-h-40 min-h-11 flex-1 resize-none rounded-field border border-line bg-white px-4 py-2.5 text-[15px] text-navy hover:border-line-strong focus:border-navy"></textarea>
                        <button type="submit" wire:loading.attr="disabled" wire:target="send, images" aria-label="{{ __('account.messages.send') }}"
                                class="grid size-11 shrink-0 place-items-center rounded-full bg-coral-pressed text-white transition-colors hover:bg-coral disabled:opacity-50">
                            <x-icon name="send" :size="19" />
                        </button>
                    </div>
                </form>
            @endif

            {{-- wire:ignore.self keeps the dialog open while polling refreshes the page. --}}
            <x-modal name="report-conversation" :title="__('account.messages.report')" wire:ignore.self>
                <form wire:submit="report" x-on:report-sent.window="$dispatch('close-dialog', 'report-conversation')" class="flex flex-col gap-4">
                    <x-account.choice-pills model="reportReason" :options="$reasons" :label="__('account.messages.report_reason')" />
                    @error('reportReason')<p class="text-[13px] font-medium text-coral-pressed">{{ $message }}</p>@enderror
                    <textarea wire:model="reportComment" rows="3" maxlength="1000" placeholder="{{ __('account.messages.report_comment') }}"
                              class="block w-full rounded-field border border-line bg-white px-4 py-3 text-[15px] text-navy hover:border-line-strong focus:border-navy"></textarea>
                    <x-button type="submit" class="self-start">{{ __('account.messages.report_send') }}</x-button>
                </form>
            </x-modal>
        </section>
    @else
        <div class="hidden flex-col items-center justify-center gap-3 p-10 text-center text-secondary lg:flex">
            <span class="grid size-16 place-items-center rounded-full bg-surface-alt"><x-icon name="message" :size="28" /></span>
            <p class="text-[15px]">{{ __('account.messages.pick') }}</p>
        </div>
    @endif
</div>
