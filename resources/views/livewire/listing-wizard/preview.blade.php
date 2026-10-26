{{-- Step 6: how the listing will read, then submit to moderation. --}}
@php($photos = $form['photos'] ?? [])

<div class="flex flex-col gap-6">
    @if ($errors->any())
        <div role="alert" class="rounded-tile bg-coral-tint px-4 py-3 text-[14px] leading-normal text-coral-pressed">
            <p class="flex items-center gap-2 font-semibold"><x-icon name="alert" :size="18" />{{ __('account.wizard.fix_errors') }}</p>
            <ul class="mt-1.5 list-disc pl-6">
                @foreach ($errors->all() as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if ($photos !== [])
        <div class="grid grid-cols-4 gap-2 md:grid-cols-[2fr_1fr_1fr] md:grid-rows-2">
            @foreach (array_slice($photos, 0, 5) as $index => $photo)
                <div @class(['relative overflow-hidden rounded-tile bg-surface-alt', 'col-span-4 aspect-4/3 md:col-span-1 md:row-span-2 md:aspect-auto' => $index === 0, 'aspect-square max-md:hidden md:aspect-auto' => $index > 2, 'aspect-square md:aspect-auto' => $index > 0 && $index <= 2])>
                    <img src="{{ $photo['url'] }}" alt="" class="absolute inset-0 size-full object-cover">
                </div>
            @endforeach
        </div>
    @endif

    <div>
        <p class="text-[13px] font-semibold text-secondary">{{ __('account.types.'.$type) }}</p>
        <h2 class="mt-1 font-serif text-[24px] leading-snug font-semibold text-navy">{{ $form['title']['ka'] ?? '' }}</h2>
        <p class="mt-3 text-[15px] leading-[1.7] whitespace-pre-line text-secondary">{{ \Illuminate\Support\Str::limit($form['description']['ka'] ?? '', 600) }}</p>
    </div>

    @if ($summary !== [])
        <dl class="grid gap-x-6 gap-y-3 rounded-tile bg-surface-alt p-4 md:grid-cols-2 md:p-5">
            @foreach ($summary as $fact)
                <div class="flex flex-col gap-0.5 border-b border-line pb-3 md:border-0 md:pb-0">
                    <dt class="text-[12.5px] text-secondary">{{ $fact['label'] }}</dt>
                    <dd class="text-[15px] font-semibold text-navy">{{ $fact['value'] }}</dd>
                </div>
            @endforeach
        </dl>
    @endif

    <div class="flex items-start gap-3 rounded-tile border border-line p-4 text-[14px] leading-normal text-navy">
        <x-icon name="shield" :size="20" class="mt-px shrink-0 text-secondary" />
        <p>{{ $editing ? __('account.wizard.moderation_edit') : __('account.wizard.moderation') }}</p>
    </div>

    <div class="flex flex-wrap gap-x-5 gap-y-2 text-[14px]">
        @foreach (['details' => 2, 'location' => 3, 'photos' => 4, 'price' => 5] as $name => $number)
            <button type="button" wire:click="goTo({{ $number }})" class="hit-44 relative font-semibold text-navy underline decoration-line-strong underline-offset-4 hover:decoration-coral">{{ __('account.wizard.edit_step', ['step' => mb_strtolower(__('account.wizard.steps.'.$name))]) }}</button>
        @endforeach
        <button type="button" wire:click="discard" wire:confirm="{{ __('account.wizard.confirm_discard') }}" class="hit-44 relative ml-auto font-semibold text-coral-pressed hover:underline">{{ $editing ? __('account.wizard.discard_changes') : __('account.wizard.discard') }}</button>
    </div>
</div>
