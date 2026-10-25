<?php

namespace App\Filament\Support;

use Closure;
use Filament\Forms\Components\Field;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Model;

/**
 * Translatable fields as KA / EN / RU tabs. Georgian is the required language.
 */
final class Translatable
{
    public const array LOCALES = ['ka', 'en', 'ru'];

    /**
     * @param  array<string, Closure(string): Field>  $fields  attribute => builder for "attribute.locale"
     * @param  list<string>  $required  attributes required in Georgian
     */
    public static function tabs(array $fields, array $required = []): Tabs
    {
        return Tabs::make('translations-'.implode('-', array_keys($fields)))
            ->label(__('admin.translations'))
            ->tabs(array_map(
                fn (string $locale): Tab => Tab::make(__('locales.'.$locale, locale: $locale))
                    ->schema(array_map(
                        fn (string $attribute, Closure $make): Field => $make("{$attribute}.{$locale}")
                            ->label(__('admin.fields.'.str_replace('.', '_', $attribute)))
                            ->required($locale === 'ka' && in_array($attribute, $required, true)),
                        array_keys($fields),
                        $fields,
                    )),
                self::LOCALES,
            ))
            ->columnSpanFull();
    }

    /**
     * A translatable attribute of any model as text in the panel language.
     */
    public static function of(?Model $record, string $attribute): string
    {
        if ($record === null) {
            return '';
        }

        return self::text(method_exists($record, 'getTranslations') ? $record->getTranslations($attribute) : $record->getAttribute($attribute));
    }

    /**
     * The value in the panel language, falling back to Georgian, for tables and infolists.
     */
    public static function text(mixed $value): string
    {
        if (! is_array($value)) {
            return (string) $value;
        }

        return (string) ($value[app()->getLocale()] ?? $value['ka'] ?? reset($value) ?: '');
    }
}
