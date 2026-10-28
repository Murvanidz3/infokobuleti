@props([
    'owner',
    // Morph alias and id of the item ("hotel", 12): used by the phone and message endpoints.
    'type',
    'id',
    'hasPhone' => true,
    'canMessage' => true,
    'title' => null,
])

<div {{ $attributes->class(['rounded-card border border-line bg-white p-5 shadow-card']) }}>
    <div class="flex items-center gap-3.5">
        <span class="grid size-12 shrink-0 place-items-center rounded-full bg-navy text-[17px] font-semibold text-white">{{ $owner['initials'] }}</span>
        <div class="min-w-0">
            <div class="flex items-center gap-1.5">
                @if ($owner['url'])
                    <a href="{{ $owner['url'] }}" class="truncate text-[16px] font-semibold text-navy hover:text-coral-pressed">{{ $owner['name'] }}</a>
                @else
                    <span class="truncate text-[16px] font-semibold text-navy">{{ $owner['name'] }}</span>
                @endif
                @if ($owner['verified'])
                    <span title="{{ __('site.owner.verified') }}" class="shrink-0 text-sea"><x-icon name="check-circle" :size="18" /><span class="sr-only">{{ __('site.owner.verified') }}</span></span>
                @endif
            </div>
            <p class="mt-0.5 text-[13px] text-secondary">{{ collect([$owner['kind'], $owner['since']])->filter()->implode(' · ') }}</p>
        </div>
    </div>

    @if ($owner['phoneVerified'] || $owner['verified'] || ($owner['ownerVerified'] ?? false))
        <ul class="mt-4 flex flex-wrap gap-2">
            @if ($owner['verified'])
                <li><x-badge variant="tint" class="gap-1"><x-icon name="shield" :size="14" />{{ __('site.owner.verified_business') }}</x-badge></li>
            @endif
            @if ($owner['ownerVerified'] ?? false)
                <li><x-badge variant="tint" class="gap-1" title="{{ __('site.owner.owner_verified_help') }}"><x-icon name="check-circle" :size="14" />{{ __('site.owner.owner_verified') }}</x-badge></li>
            @endif
            @if ($owner['phoneVerified'])
                <li><x-badge variant="tint" class="gap-1"><x-icon name="smartphone" :size="14" />{{ __('site.owner.verified_phone') }}</x-badge></li>
            @endif
        </ul>
    @endif

    <div class="mt-5 flex flex-col gap-2.5">
        @if ($hasPhone)
            <div x-data="phoneReveal(@js($type), {{ (int) $id }})">
                <template x-if="!phone">
                    <x-button class="w-full" icon="phone" x-on:click="reveal()" x-bind:disabled="busy">{{ __('site.owner.show_phone') }}</x-button>
                </template>
                <template x-if="phone">
                    <div class="flex flex-col gap-2.5">
                        <a x-bind:href="tel" class="inline-flex min-h-11 items-center justify-center gap-2.5 rounded-button bg-navy px-5 text-[15.5px] font-semibold text-white hover:bg-navy-hover">
                            <x-icon name="phone" :size="18" /><span x-text="phone"></span>
                        </a>
                        <div class="grid grid-cols-2 gap-2.5" x-show="whatsapp || viber">
                            <a x-bind:href="whatsapp" x-show="whatsapp" target="_blank" rel="noopener" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-button border border-line text-[14px] font-semibold text-navy hover:border-line-strong">
                                <x-icon name="whatsapp" :size="18" class="text-[#1f9d55]" />WhatsApp
                            </a>
                            <a x-bind:href="viber" x-show="viber" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-button border border-line text-[14px] font-semibold text-navy hover:border-line-strong">
                                <x-icon name="viber" :size="18" class="text-[#7360f2]" />Viber
                            </a>
                        </div>
                    </div>
                </template>
            </div>
        @endif
        @if ($canMessage)
            <div x-data="authAction(@js(auth()->check()), 'message')">
                <x-button variant="secondary" class="w-full" icon="message" x-on:click="run()">{{ __('site.owner.write') }}</x-button>
            </div>
        @endif
    </div>
    {{ $slot }}
</div>

@if ($canMessage)
    @auth
        <x-modal name="message" :title="__('site.message.title')">
            <form method="post" action="{{ localized_route('message.store', ['type' => $type, 'id' => $id]) }}" class="flex flex-col gap-4">
                @csrf
                @if ($title)
                    <p class="text-[14px]">{{ __('site.message.about', ['title' => $title]) }}</p>
                @endif
                <x-form.field :label="__('site.message.body')" for="message-body" name="body" :error="$errors->message->first('body')">
                    <textarea id="message-body" name="body" rows="5" required minlength="2" maxlength="2000" class="block w-full rounded-field border border-line bg-white px-4 py-3 text-[15px] text-navy hover:border-line-strong focus:border-navy">{{ old('body') }}</textarea>
                </x-form.field>
                <div class="flex justify-end gap-3">
                    <x-button variant="ghost" x-on:click="$dispatch('close-dialog', 'message')">{{ __('site.form.cancel') }}</x-button>
                    <x-button type="submit" icon="send">{{ __('site.message.send') }}</x-button>
                </div>
            </form>
        </x-modal>
        @if ($errors->message->any())
            <div x-data x-init="$nextTick(() => $dispatch('open-dialog', 'message'))"></div>
        @endif
    @endauth
@endif
