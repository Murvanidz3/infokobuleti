<?php

use App\Actions\Listings\SubmitListing;
use App\Enums\ListingStatus;
use App\Exceptions\ListingLimitReached;
use App\Models\ListingDraft;
use App\Models\Property;
use App\Models\User;
use App\Services\Settings;
use App\Support\Listings\ListingForm;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

it('keeps a published listing published when its price changes', function () {
    $user = User::factory()->create();
    [$property, $draft, $form] = editablePropertyDraft($user);
    $form['price'] = '42000';
    $draft->update(['data' => $form]);

    $listing = app(SubmitListing::class)->handle($user, $draft);

    expect($listing->fresh())
        ->status->toBe(ListingStatus::Published)
        ->price->toEqual(42000)
        ->and($draft->fresh())->toBeNull();
});

it('keeps a published listing published when the title changes', function () {
    $user = User::factory()->create();
    [$property, $draft, $form] = editablePropertyDraft($user);
    $form['title']['ka'] = 'ახალი სათაური ბინისთვის';
    $draft->update(['data' => $form]);

    $listing = app(SubmitListing::class)->handle($user, $draft);

    expect($listing->fresh()->status)->toBe(ListingStatus::Published)
        ->and($listing->fresh()->getTranslation('title', 'ka'))->toBe('ახალი სათაური ბინისთვის')
        ->and($property->fresh()->published_at)->not->toBeNull();
});

it('sends an archived listing back to moderation', function () {
    $user = User::factory()->create();
    [$property, $draft] = editablePropertyDraft($user, ListingStatus::Archived);

    $listing = app(SubmitListing::class)->handle($user, $draft);

    expect($listing->fresh()->status)->toBe(ListingStatus::Pending)
        ->and($property->fresh()->id)->toBe($listing->id);
});

it('sends a rejected listing back to moderation', function () {
    $user = User::factory()->create();
    [, $draft] = editablePropertyDraft($user, ListingStatus::Rejected);

    $listing = app(SubmitListing::class)->handle($user, $draft);

    expect($listing->fresh()->status)->toBe(ListingStatus::Pending);
});

it('rejects a tampered photo list instead of failing while saving', function (mixed $photos) {
    $user = User::factory()->create();
    [$property, $draft, $form] = editablePropertyDraft($user);
    $form['photos'] = $photos;
    $draft->update(['data' => $form]);

    $caught = null;

    try {
        app(SubmitListing::class)->handle($user, $draft);
    } catch (ValidationException $exception) {
        $caught = $exception;
    }

    expect($caught)->toBeInstanceOf(ValidationException::class)
        ->and(collect($caught->errors())->flatten()->first())->toBe(__('account.wizard.errors.photo'))
        ->and($property->fresh()->price)->toEqual(50000)
        ->and($property->fresh()->status)->toBe(ListingStatus::Published);
})->with([
    'string item' => [['nope']],
    'unknown source' => [[['source' => 'remote', 'id' => 1]]],
    'missing id' => [[['source' => 'listing']]],
    'not a list' => ['nope'],
]);

it('does not create a second listing once the monthly limit is used', function () {
    app(Settings::class)->put(['listings.free_limit.individual' => 1]);

    $owner = User::factory()->create();
    $template = editablePropertyDraft(User::factory()->create())[0];
    $makeDraft = function () use ($owner, $template): ListingDraft {
        $draft = ListingDraft::factory()->create([
            'user_id' => $owner->id,
            'listing_type' => 'property',
            'listing_id' => null,
            'step' => 6,
            'data' => ListingForm::fromListing($template),
        ]);
        $photo = wizardJpeg($draft, 'photos');
        $data = $draft->data;
        $data['photos'] = [['source' => 'draft', 'id' => $photo->id, 'url' => '/photo.jpg']];
        $draft->update(['data' => $data]);

        return $draft;
    };

    $created = app(SubmitListing::class)->handle($owner, $makeDraft());

    expect($created->status)->toBe(ListingStatus::Pending)
        ->and(fn () => app(SubmitListing::class)->handle($owner, $makeDraft()))->toThrow(ListingLimitReached::class)
        ->and(Property::query()->where('user_id', $owner->id)->count())->toBe(1);
});

it('still allows an edit when the monthly limit is already used', function () {
    app(Settings::class)->put(['listings.free_limit.individual' => 1]);
    $user = User::factory()->create();
    [$property, $draft, $form] = editablePropertyDraft($user);
    $form['price'] = '42000';
    $draft->update(['data' => $form]);

    $listing = app(SubmitListing::class)->handle($user, $draft);

    expect($listing->fresh()->status)->toBe(ListingStatus::Published)
        ->and(Property::withTrashed()->where('user_id', $user->id)->count())->toBe(1);
});

it('locks the owner row before inserting a new listing', function () {
    $owner = User::factory()->create();
    $template = editablePropertyDraft(User::factory()->create())[0];
    $draft = ListingDraft::factory()->create([
        'user_id' => $owner->id,
        'listing_type' => 'property',
        'listing_id' => null,
        'step' => 6,
        'data' => ListingForm::fromListing($template),
    ]);
    $photo = wizardJpeg($draft, 'photos');
    $data = $draft->data;
    $data['photos'] = [['source' => 'draft', 'id' => $photo->id, 'url' => '/photo.jpg']];
    $draft->update(['data' => $data]);

    $queries = [];
    DB::listen(function ($query) use (&$queries): void {
        $queries[] = strtolower($query->sql);
    });

    app(SubmitListing::class)->handle($owner, $draft);

    $lockAt = null;
    $insertAt = null;

    foreach ($queries as $index => $sql) {
        if ($lockAt === null && str_contains($sql, 'for update') && str_contains($sql, 'users')) {
            $lockAt = $index;
        }

        if ($insertAt === null && str_contains($sql, 'insert into') && str_contains($sql, 'properties')) {
            $insertAt = $index;
        }
    }

    expect($lockAt)->toBeInt()
        ->and($insertAt)->toBeInt()->toBeGreaterThan($lockAt);
});

it('leaves photo files in place when saving the listing rolls back', function () {
    $owner = User::factory()->create();
    $template = editablePropertyDraft(User::factory()->create())[0];
    $draft = ListingDraft::factory()->create([
        'user_id' => $owner->id,
        'listing_type' => 'property',
        'listing_id' => null,
        'step' => 6,
        'data' => ListingForm::fromListing($template),
    ]);
    $photo = wizardJpeg($draft, 'photos');
    $data = $draft->data;
    $data['photos'] = [['source' => 'draft', 'id' => $photo->id, 'url' => '/photo.jpg']];
    $draft->update(['data' => $data]);
    $path = $photo->getPathRelativeToRoot();

    $GLOBALS['fail_pending_property_save'] = true;
    Property::saved(function (Property $property): void {
        if (($GLOBALS['fail_pending_property_save'] ?? false) && $property->status === ListingStatus::Pending) {
            throw new RuntimeException('stop after photos');
        }
    });

    try {
        expect(fn () => app(SubmitListing::class)->handle($owner, $draft))->toThrow(RuntimeException::class);
    } finally {
        $GLOBALS['fail_pending_property_save'] = false;
    }

    expect(Storage::disk($photo->disk)->exists($path))->toBeTrue()
        ->and(Media::query()->find($photo->id))
        ->model_type->toBe('listing_draft')
        ->model_id->toBe($draft->id)
        ->and(Property::query()->where('user_id', $owner->id)->count())->toBe(0)
        ->and($draft->fresh())->not->toBeNull();
});

it('keeps a moved photo on the same file and deletes a removed photo only after commit', function () {
    $user = User::factory()->create();
    [$property, $draft, $form] = editablePropertyDraft($user);
    $kept = $property->getFirstMedia('gallery');
    $dropped = wizardJpeg($property, 'gallery');
    $added = wizardJpeg($draft, 'photos');
    $droppedPath = $dropped->getPathRelativeToRoot();
    $addedPath = $added->getPathRelativeToRoot();

    $form['photos'] = [
        ['source' => 'draft', 'id' => $added->id, 'url' => '/new.jpg'],
        ['source' => 'listing', 'id' => $kept->id, 'url' => '/kept.jpg'],
    ];
    $draft->update(['data' => $form]);

    app(SubmitListing::class)->handle($user, $draft);

    expect(Media::query()->find($added->id))
        ->model_type->toBe('property')
        ->model_id->toBe($property->id)
        ->collection_name->toBe('gallery')
        ->and(Storage::disk($added->disk)->exists($addedPath))->toBeTrue()
        ->and(Storage::disk($kept->disk)->exists($kept->getPathRelativeToRoot()))->toBeTrue()
        ->and(Media::query()->whereKey($dropped->id)->exists())->toBeFalse()
        ->and(Storage::disk($dropped->disk)->exists($droppedPath))->toBeFalse();
});
