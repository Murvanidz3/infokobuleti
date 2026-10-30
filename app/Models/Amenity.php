<?php

namespace App\Models;

use App\Enums\AmenityScope;
use App\Models\Concerns\LogsStaffActivity;
use Database\Factories\AmenityFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Spatie\Translatable\HasTranslations;

/**
 * Property features (and leftover amenity rows from the retired hotel module) in one table, split by scope.
 */
#[Fillable(['scope', 'group', 'name', 'slug', 'icon', 'sort', 'is_filterable'])]
class Amenity extends Model
{
    /** @use HasFactory<AmenityFactory> */
    use HasFactory, HasTranslations, LogsStaffActivity;

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
     * @return MorphToMany<Property, $this>
     */
    public function properties(): MorphToMany
    {
        return $this->morphedByMany(Property::class, 'amenityable');
    }
}
