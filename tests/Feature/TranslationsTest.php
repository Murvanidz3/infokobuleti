<?php

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Symfony\Component\Finder\SplFileInfo;

/**
 * @return list<int|string>
 */
function langKeys(string $locale, string $file): array
{
    return array_keys(Arr::dot(require lang_path("{$locale}/{$file}")));
}

dataset('lang files', fn () => array_map('basename', glob(dirname(__DIR__, 2).'/lang/ka/*.php') ?: []));

it('has every Georgian key in English and Russian', function (string $file) {
    $keys = langKeys('ka', $file);

    foreach (['en', 'ru'] as $locale) {
        expect(File::exists(lang_path("{$locale}/{$file}")))->toBeTrue("lang/{$locale}/{$file} is missing");
        expect(langKeys($locale, $file))->toEqualCanonicalizing($keys);
    }
})->with('lang files');

it('keeps Georgian text out of code and templates', function () {
    $georgian = '/[\x{10A0}-\x{10FF}\x{1C90}-\x{1CBF}\x{2D00}-\x{2D2F}]/u';

    $offenders = collect(['app', 'config', 'routes', 'resources/views', 'resources/js', 'resources/css'])
        ->flatMap(fn (string $dir) => File::allFiles(base_path($dir)))
        ->filter(fn (SplFileInfo $file) => preg_match($georgian, $file->getContents()) === 1)
        ->map(fn (SplFileInfo $file) => $file->getRelativePathname())
        ->values()
        ->all();

    expect($offenders)->toBe([]);
});
