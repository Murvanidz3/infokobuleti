{{-- Listing analytics (business package). Bars are drawn with plain elements, no chart library. --}}
<div class="flex flex-col gap-6">
    @if ($locked)
        <x-empty-state icon="chart" :title="__('billing.analytics.locked_title')" :text="__('billing.analytics.locked_text')">
            <x-slot:action>
                <x-button :href="localized_route('account.billing')" icon="briefcase">{{ __('billing.package.buy') }}</x-button>
            </x-slot:action>
        </x-empty-state>
    @else
        <div class="flex flex-wrap items-center justify-between gap-3">
            <x-account.choice-pills model="days" :options="$periods" :label="__('billing.analytics.period')" />
            <p class="text-[13px] text-secondary">{{ __('billing.package.active', ['date' => $until]) }}</p>
        </div>

        <section aria-labelledby="totals-title" class="rounded-panel border border-line bg-white p-5 shadow-card md:p-6">
            <h2 id="totals-title" class="sr-only">{{ __('billing.analytics.totals') }}</h2>
            <dl class="grid grid-cols-2 gap-4 md:grid-cols-4">
                @foreach (['views' => 'eye', 'phone_reveals' => 'phone', 'favorites' => 'heart', 'messages' => 'message'] as $metric => $icon)
                    <div class="flex items-center gap-3">
                        <span class="grid size-11 shrink-0 place-items-center rounded-full bg-surface-alt text-navy"><x-icon :name="$icon" :size="20" /></span>
                        <div>
                            <dd class="text-[20px] leading-tight font-semibold text-navy">{{ number_format($totals[$metric], 0, '.', ' ') }}</dd>
                            <dt class="text-[13px] text-secondary">{{ __('account.stats.'.$metric) }}</dt>
                        </div>
                    </div>
                @endforeach
            </dl>
        </section>

        <section aria-labelledby="chart-title" class="rounded-panel border border-line bg-white p-5 shadow-card md:p-6">
            <h2 id="chart-title" class="font-serif text-[18px] font-semibold text-navy">{{ __('billing.analytics.views_per_day') }}</h2>
            <div class="mt-4 flex h-44 items-end gap-[3px]" role="img" aria-label="{{ __('billing.analytics.views_per_day') }}">
                @foreach ($series as $point)
                    <div class="group relative flex h-full min-w-0 flex-1 flex-col justify-end" title="{{ $point['date'] }}: {{ $point['views'] }}">
                        <div class="rounded-t-[3px] bg-navy transition-colors duration-200 group-hover:bg-coral" style="height: {{ max(2, round($point['views'] / $max * 100)) }}%"></div>
                    </div>
                @endforeach
            </div>
            <div class="mt-2 flex justify-between text-[12px] text-secondary">
                <span>{{ $series[0]['short'] ?? '' }}</span>
                <span>{{ $series[count($series) - 1]['short'] ?? '' }}</span>
            </div>
        </section>

        <section aria-labelledby="top-title">
            <h2 id="top-title" class="font-serif text-[20px] font-semibold text-navy">{{ __('billing.analytics.top') }}</h2>
            @if ($top === [])
                <p class="mt-2 text-[14.5px] text-secondary">{{ __('billing.analytics.empty') }}</p>
            @else
                <div class="mt-3 overflow-x-auto rounded-card border border-line bg-white shadow-card">
                    <table class="w-full min-w-[560px] text-[14px]">
                        <thead class="bg-surface-alt text-left text-[12.5px] text-secondary">
                            <tr>
                                <th class="px-4 py-2.5 font-semibold">{{ __('billing.analytics.listing') }}</th>
                                @foreach (['views', 'phone_reveals', 'favorites', 'messages'] as $metric)
                                    <th class="px-3 py-2.5 text-right font-semibold">{{ __('account.stats.'.$metric) }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @foreach ($top as $item)
                                <tr>
                                    <td class="max-w-[280px] px-4 py-2.5">
                                        <span class="block truncate font-semibold text-navy">@if ($item['url'])<a href="{{ $item['url'] }}" class="hover:text-coral-pressed">{{ $item['title'] }}</a>@else{{ $item['title'] }}@endif</span>
                                        <span class="text-[12.5px] text-secondary">{{ $item['module'] }}</span>
                                    </td>
                                    @foreach (['views', 'phone_reveals', 'favorites', 'messages'] as $metric)
                                        <td class="px-3 py-2.5 text-right text-navy">{{ $item[$metric] }}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    @endif
</div>
