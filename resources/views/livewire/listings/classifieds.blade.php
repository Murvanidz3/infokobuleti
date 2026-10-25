<div>
    <x-listing.page :total="$total" :chips="$chips" :sort-options="$sortOptions" :views="$views" :view="$display" :paginator="$paginator"
                    :has-more="$hasMore" :markers="$markers" :active-count="$activeCount" :targets="$targets" :search-placeholder="__('home.search.placeholders.query')">
        <x-slot:filters>
            <x-filters.group :title="__('home.search.fields.category')">
                <div wire:key="root-{{ $rootSlug }}">
                    <x-form.select name="root" :options="$roots" :selected="$rootSlug" :placeholder="__('site.filters.all_categories')" x-on:change="$wire.set('category', $event.target.value)" />
                </div>
                @if ($children)
                    <x-filters.pills model="category" :multiple="false" class="mt-3" :options="[$rootSlug => __('site.filters.all_in_category')] + $children" />
                @endif
            </x-filters.group>

            @foreach ($fields as $field)
                <x-filters.group :title="$field['name'].(isset($field['unit']) ? ', '.$field['unit'] : '')" wire:key="field-{{ $field['key'] }}">
                    @switch($field['type'])
                        @case('select')
                            <x-form.select :name="'attrs-'.$field['key']" wire:model.live="attrs.{{ $field['key'] }}" :options="$field['choices']" :placeholder="__('site.filters.any')" />
                            @break
                        @case('multiselect')
                            <x-filters.pills :model="'attrs.'.$field['key']" :options="$field['choices']" />
                            @break
                        @case('boolean')
                            <label class="inline-flex min-h-10 cursor-pointer items-center gap-3 text-[15px] text-navy">
                                <input type="checkbox" wire:model.live="attrs.{{ $field['key'] }}" value="1" class="size-5 shrink-0 cursor-pointer rounded-[5px] accent-coral">
                                {{ __('site.filters.yes') }}
                            </label>
                            @break
                        @case('number')
                            <x-filters.range :from="'attrs.'.$field['key'].'_min'" :to="'attrs.'.$field['key'].'_max'" :label="$field['name']" />
                            @break
                    @endswitch
                </x-filters.group>
            @endforeach

            <x-filters.group :title="__('home.search.fields.price')">
                <x-filters.range from="price_min" to="price_max" prefix="₾" :label="__('home.search.fields.price')" />
            </x-filters.group>

            <x-filters.group :title="__('site.filters.condition')">
                <x-filters.pills model="condition" :multiple="false" :options="['' => __('site.filters.any')] + $conditionOptions" />
            </x-filters.group>

            @if ($locationOptions)
                <x-filters.group :title="__('home.search.fields.location')">
                    <x-form.select name="location" wire:model.live="location" :options="$locationOptions" :placeholder="__('site.filters.all_locations')" />
                </x-filters.group>
            @endif

            <x-filters.group :title="__('site.filters.more')">
                <label class="inline-flex min-h-10 cursor-pointer items-center gap-3 text-[15px] text-navy">
                    <input type="checkbox" wire:model.live="photos" class="size-5 shrink-0 cursor-pointer rounded-[5px] accent-coral">
                    {{ __('site.filters.photos_only') }}
                </label>
            </x-filters.group>
        </x-slot:filters>

        @if ($display === 'list')
            <x-list-panel>
                @foreach ($items as $classified)
                    <x-card.classified wire:key="classified-{{ $classified['favorite'] }}" :title="$classified['title']" :meta="$classified['meta']" :price="$classified['price']" :time="$classified['time']" :icon="$classified['icon']" :is-new="$classified['isNew']" :url="$classified['url']" />
                @endforeach
            </x-list-panel>
        @else
            <div class="grid grid-cols-[repeat(auto-fill,minmax(min(100%,220px),1fr))] gap-4">
                @foreach ($items as $classified)
                    <x-card.classified-tile wire:key="classified-{{ $classified['favorite'] }}" :title="$classified['title']" :meta="$classified['meta']" :price="$classified['price']" :time="$classified['time']" :icon="$classified['icon']" :is-new="$classified['isNew']" :url="$classified['url']" :image="$classified['image']" :saved="$classified['saved']" :favorite="$classified['favorite']" />
                @endforeach
            </div>
        @endif
    </x-listing.page>
</div>
