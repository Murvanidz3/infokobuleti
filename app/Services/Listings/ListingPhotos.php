<?php

namespace App\Services\Listings;

use App\Contracts\Listing;
use App\Models\ListingDraft;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\MediaLibrary\Conversions\FileManipulator;
use Spatie\MediaLibrary\MediaCollections\Filesystem;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Applies the wizard's photo list to a listing's gallery.
 *
 * Files live at a path derived from the media id, so pointing a row at another model does not
 * move bytes. Discarded rows are deleted in the database transaction; their files are removed
 * only after that transaction commits. A rollback then restores the rows and the files are
 * still there. The reverse (delete the file first) leaves a restored row pointing at nothing.
 */
class ListingPhotos
{
    /**
     * @param  list<mixed>  $photos
     *
     * @throws ValidationException when an entry is not a draft or listing photo
     */
    public function sync(Model&Listing $listing, ListingDraft $draft, array $photos): void
    {
        $entries = $this->entries($photos);
        $gallery = $listing->getMedia('gallery');
        $draftMedia = $draft->getMedia('photos')->keyBy(fn (Media $media): int => (int) $media->getKey());
        $keep = collect($entries)->where('source', 'listing')->pluck('id')->all();

        $removed = $gallery
            ->reject(fn (Media $media): bool => in_array((int) $media->getKey(), $keep, true))
            ->merge($draftMedia->reject(function (Media $media) use ($entries): bool {
                return collect($entries)->contains(
                    fn (array $entry): bool => $entry['source'] === 'draft' && $entry['id'] === (int) $media->getKey(),
                );
            }))
            ->values();

        $order = [];
        $attached = [];

        foreach ($entries as $entry) {
            if ($entry['source'] === 'listing') {
                if ($gallery->contains(fn (Media $media): bool => (int) $media->getKey() === $entry['id'])) {
                    $order[] = $entry['id'];
                }

                continue;
            }

            $media = $draftMedia->get($entry['id']);

            if (! $media instanceof Media) {
                continue;
            }

            $media->forceFill([
                'model_type' => $listing->getMorphClass(),
                'model_id' => $listing->getKey(),
                'collection_name' => 'gallery',
            ])->save();

            $order[] = (int) $media->getKey();
            $attached[] = $media;
        }

        if ($removed->isNotEmpty()) {
            Media::withoutEvents(function () use ($removed): void {
                $removed->each(fn (Media $media) => $media->delete());
            });
        }

        if ($order !== []) {
            Media::setNewOrder($order);
        }

        $this->commit($removed->all(), $attached);
    }

    /**
     * Deletes the stored files of a media row that has already been removed from the database.
     */
    public function deleteFiles(Media $media): void
    {
        app(Filesystem::class)->removeAllFiles($media);
    }

    /**
     * @param  list<Media>  $removed
     * @param  list<Media>  $attached
     */
    public function commit(array $removed, array $attached): void
    {
        $apply = function () use ($removed, $attached): void {
            foreach ($removed as $media) {
                $this->deleteFiles($media);
            }

            $manipulator = app(FileManipulator::class);

            foreach ($attached as $media) {
                $manipulator->createDerivedFiles($media->fresh() ?? $media);
            }
        };

        if (DB::transactionLevel() === 0) {
            $apply();

            return;
        }

        DB::afterCommit($apply);
    }

    /**
     * @param  list<mixed>  $photos
     * @return list<array{source: string, id: int}>
     *
     * @throws ValidationException
     */
    private function entries(array $photos): array
    {
        $entries = [];

        foreach ($photos as $index => $photo) {
            $source = is_array($photo) ? ($photo['source'] ?? null) : null;
            $id = is_array($photo) ? ($photo['id'] ?? null) : null;

            if (! in_array($source, ['draft', 'listing'], true) || ! is_numeric($id) || (int) $id < 1) {
                throw ValidationException::withMessages([
                    'form.photos.'.$index => __('account.wizard.errors.photo'),
                ]);
            }

            $entries[] = ['source' => $source, 'id' => (int) $id];
        }

        return $entries;
    }
}
