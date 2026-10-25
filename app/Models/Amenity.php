<?php

namespace App\Models;

use App\Enums\AmenityScope;
use Database\Factories\AmenityFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Spatie\Translatable\HasTranslations;

/**
 * Hotel amenities, room amenities and property features in one table, split by scope.
 */
#[Fillable(['scope', 'group', 'name', 'slug', 'icon', 'sort', 'is_filterable'])]
class Amenity extends Model
{
    /** @use HasFactory<AmenityFactory> */
    use HasFactory, HasTranslations;

    /** @var list<string> */
    public array $translatable = ['name'];

    protected function casts(): array
    {
        return [
            'scope' => AmenityScope::class,
            'is_filterable' => 'boolean',
        ];
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeForScope(Builder $query, AmenityScope $scope): void
    {
        $query->where('scope', $scope)->orderBy('sort');
    }

    /**
     * @return MorphToMany<Hotel, $this>
     */
    public function hotels(): MorphToMany
    {
        return $this->morphedByMany(Hotel::class, 'amenityable');
    }

    /**
     * @return MorphToMany<RoomType, $this>
     */
    public function roomTypes(): MorphToMany
    {
        return $this->morphedByMany(RoomType::class, 'amenityable');
    }

    /**
     * @return MorphToMany<Property, $this>
     */
    public function properties(): MorphToMany
    {
        return $this->morphedByMany(Property::class, 'amenityable');
    }
}
