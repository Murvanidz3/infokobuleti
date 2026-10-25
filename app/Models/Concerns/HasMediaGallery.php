<?php

namespace App\Models\Concerns;

use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Photo gallery with the design's image sizes, all WebP and queued. The first item by
 * order_column is the cover.
 */
trait HasMediaGallery
{
    use InteractsWithMedia;

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('gallery')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp', 'image/heic', 'image/avif']);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        foreach (['thumb' => 400, 'card' => 800, 'large' => 1600] as $name => $width) {
            $this->addMediaConversion($name)
                ->performOnCollections('gallery')
                ->fit(Fit::Max, $width, $width * 2)
                ->format('webp')
                ->quality(80);
        }

        $this->addMediaConversion('placeholder')
            ->performOnCollections('gallery')
            ->fit(Fit::Max, 32, 64)
            ->blur(8)
            ->format('webp')
            ->quality(40);
    }

    public function coverUrl(string $conversion = 'card'): ?string
    {
        $url = $this->getFirstMediaUrl('gallery', $conversion);

        return $url === '' ? null : $url;
    }
}
