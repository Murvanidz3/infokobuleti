<?php

use App\Models\ListingDraft;
use App\Models\User;
use App\Services\Listings\ListingPhotos;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

it('deletes the stored file of a discarded photo', function () {
    $draft = ListingDraft::factory()->for(User::factory())->create();
    $path = tempnam(sys_get_temp_dir(), 'ik').'.jpg';
    $image = imagecreatetruecolor(8, 8);
    imagejpeg($image, $path, 90);
    imagedestroy($image);

    $media = $draft->addMedia($path)->usingFileName('discard.jpg')->toMediaCollection('photos');
    $relative = $media->getPathRelativeToRoot();

    expect(Storage::disk($media->disk)->exists($relative))->toBeTrue();

    app(ListingPhotos::class)->deleteFiles($media);

    expect(Storage::disk($media->disk)->exists($relative))->toBeFalse()
        ->and(Media::query()->whereKey($media->id)->exists())->toBeTrue();
});
