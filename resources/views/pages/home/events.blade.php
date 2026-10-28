@if ($events !== [])
<x-section id="events" :background="$alt ? 'alt' : 'white'" aria-labelledby="events-title">
    <x-section-header id="events-title" :title="__('home.events.title')" :subtitle="__('home.events.subtitle')" :href="localized_route('events.index')" :link-label="__('home.events.all')" />
    <div class="grid grid-cols-[repeat(auto-fill,minmax(min(100%,280px),1fr))] gap-6">
        @foreach ($events as $event)
            <x-card.event :tag="$event['tag']" :title="$event['title']" :day="$event['day']" :month="$event['month']" :when="$event['when']" :venue="$event['venue']" :price="$event['price']" :url="$event['url']" :image="$event['image']" />
        @endforeach
    </div>
</x-section>
@endif
