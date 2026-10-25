<?php

namespace App\Filament\Support;

use Illuminate\Database\Eloquent\Model;

/**
 * For create and edit pages of translatable models: empty languages are dropped instead of being
 * saved as blank strings, and clearing a language on edit really removes it.
 */
trait SavesTranslations
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return self::cleanTranslations($data, static::getModel());
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $data = self::cleanTranslations($data, $record::class);

        foreach ($this->translatableAttributes($record) as $attribute) {
            if (array_key_exists($attribute, $data) && is_array($data[$attribute]) && method_exists($record, 'replaceTranslations')) {
                $record->replaceTranslations($attribute, $data[$attribute]);
                unset($data[$attribute]);
            }
        }

        $record->fill($data)->save();

        return $record;
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  class-string<Model>  $model
     * @return array<string, mixed>
     */
    public static function cleanTranslations(array $data, string $model): array
    {
        foreach ((new $model)->translatable ?? [] as $attribute) {
            if (isset($data[$attribute]) && is_array($data[$attribute])) {
                $data[$attribute] = array_filter($data[$attribute], fn ($value): bool => filled($value));
            }
        }

        return $data;
    }

    /**
     * @return list<string>
     */
    private function translatableAttributes(Model $record): array
    {
        return method_exists($record, 'getTranslatableAttributes') ? $record->getTranslatableAttributes() : [];
    }
}
