<x-section :background="$alt ? 'alt' : 'white'" id="classifieds">
    <div class="grid grid-cols-[repeat(auto-fit,minmax(min(100%,460px),1fr))] gap-x-8 gap-y-[clamp(40px,3vw,48px)]">
        <div aria-labelledby="classifieds-title" role="region">
            <x-section-header size="sm" id="classifieds-title" :title="__('home.classifieds.title')" :href="localized_route('classifieds.index')" />
            <x-list-panel>
                @foreach ($classifieds as $classified)
                    <x-card.classified :title="$classified['title']" :meta="$classified['meta']" :price="$classified['price']" :time="$classified['time']" :icon="$classified['icon']" :is-new="$classified['isNew']" :url="$classified['url']" :promotion="$classified['promotion'] ?? null" />
                @endforeach
            </x-list-panel>
        </div>
        <div id="jobs" aria-labelledby="jobs-title" role="region">
            <x-section-header size="sm" id="jobs-title" :title="__('home.jobs.title')" :href="localized_route('jobs.index')" />
            <x-list-panel>
                @foreach ($jobs as $job)
                    <x-card.job :title="$job['title']" :employer="$job['employer']" :salary="$job['salary']" :period="$job['period']" :type="$job['type']" :seasonal="$job['seasonal']" :posted="$job['posted']" :url="$job['url']" :promotion="$job['promotion'] ?? null" :logo="$job['logo'] ?? null" />
                @endforeach
            </x-list-panel>
        </div>
    </div>
</x-section>
