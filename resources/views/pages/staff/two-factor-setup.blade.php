<x-layout.app :noindex="true" :title="__('auth.staff.setup_title')">
    <x-auth.card :title="__('auth.staff.setup_title')" :subtitle="__('auth.staff.setup_subtitle')" icon="shield" wide>
        <ol class="flex flex-col gap-6">
            <li class="flex gap-4">
                <span class="grid size-8 shrink-0 place-items-center rounded-full bg-navy text-sm font-semibold text-white">1</span>
                <p class="pt-1 text-[15px] leading-[1.6] text-secondary">{{ __('auth.staff.setup_step_app') }}</p>
            </li>
            <li class="flex gap-4">
                <span class="grid size-8 shrink-0 place-items-center rounded-full bg-navy text-sm font-semibold text-white">2</span>
                <div class="flex flex-1 flex-col gap-4 pt-1">
                    <p class="text-[15px] leading-[1.6] text-secondary">{{ __('auth.staff.setup_step_scan') }}</p>
                    <div class="flex flex-col items-start gap-4 xs:flex-row xs:items-center">
                        <div class="rounded-tile border border-line bg-white p-2 [&>svg]:block [&>svg]:size-[180px]">{!! $qrCode !!}</div>
                        <div class="flex flex-col gap-1.5">
                            <span class="text-[13px] font-semibold text-navy">{{ __('auth.staff.setup_manual') }}</span>
                            <code class="rounded-chip bg-surface-alt px-3 py-2 font-mono text-[14px] tracking-[0.08em] break-all text-navy select-all">{{ $secret }}</code>
                        </div>
                    </div>
                </div>
            </li>
            <li class="flex gap-4">
                <span class="grid size-8 shrink-0 place-items-center rounded-full bg-navy text-sm font-semibold text-white">3</span>
                <form method="POST" action="{{ localized_route('staff.two-factor.setup.store') }}" class="flex flex-1 flex-col gap-4 pt-1">
                    @csrf
                    <x-form.field :label="__('auth.staff.setup_step_confirm')" name="code">
                        <input type="text" name="code" id="code" inputmode="numeric" autocomplete="one-time-code" pattern="\d{6}" maxlength="6" placeholder="••••••" required autofocus
                               @class([
                                   'block min-h-14 w-full max-w-[280px] rounded-field border bg-white px-4 text-center text-[24px] font-semibold tracking-[0.5em] text-navy transition-colors duration-200 placeholder:text-secondary/40 hover:border-line-strong focus:border-navy',
                                   'border-coral-pressed' => $errors->has('code'),
                                   'border-line' => ! $errors->has('code'),
                               ])>
                    </x-form.field>
                    <x-button type="submit" size="lg" class="self-start">{{ __('auth.staff.setup_enable') }}</x-button>
                </form>
            </li>
        </ol>
    </x-auth.card>
</x-layout.app>
