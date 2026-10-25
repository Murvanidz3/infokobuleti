<?php

use App\Enums\ListedBy;
use App\Enums\PropertyCondition;

dataset('enums', function () {
    foreach (glob(dirname(__DIR__, 2).'/app/Enums/*.php') ?: [] as $file) {
        $class = 'App\\Enums\\'.basename($file, '.php');

        yield class_basename($class) => [$class];
    }
});

it('has a label for every enum case in every locale', function (string $enum) {
    foreach (['ka', 'en', 'ru'] as $locale) {
        app()->setLocale($locale);

        foreach ($enum::cases() as $case) {
            expect($case->label())->not->toStartWith('enums.', "{$enum}::{$case->name} has no {$locale} label");
        }
    }
})->with('enums');

it('keys enum labels by the snake case enum name', function () {
    expect(ListedBy::langKey())->toBe('listed_by')
        ->and(PropertyCondition::langKey())->toBe('property_condition');
});
