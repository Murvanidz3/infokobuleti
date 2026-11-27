<?php

use App\Enums\CategoryModule;
use App\Models\Category;
use App\Support\Listings\ListingForm;
use Illuminate\Support\Facades\Validator;

it('uses the translated attribute label when brand is missing', function (string $locale, string $label) {
    $phones = Category::factory()->create([
        'module' => CategoryModule::Classifieds,
        'parent_id' => null,
        'slug' => 'phones-'.$locale,
        'attribute_schema' => [[
            'key' => 'brand',
            'type' => 'select',
            'label' => ['ka' => 'ბრენდი', 'en' => 'Brand', 'ru' => 'Бренд'],
            'required' => true,
            'options' => [[
                'value' => 'apple',
                'label' => ['ka' => 'Apple', 'en' => 'Apple', 'ru' => 'Apple'],
            ]],
        ]],
    ]);

    app()->setLocale($locale);

    $form = [
        'category_id' => (string) $phones->id,
        'attribute_values' => ['brand' => ''],
        'title' => ['ka' => 'ტელეფონი იყიდება'],
        'description' => ['ka' => 'კარგ მდგომარეობაში, ერთი მფლობელი, სრული კომპლექტაცია.'],
    ];

    $message = Validator::make(
        ['form' => $form],
        ListingForm::allRules('classified', $form),
        [],
        ListingForm::validationAttributes($form),
    )->errors()->first('form.attribute_values.brand');

    expect($message)->toContain($label)
        ->and($message)->not->toContain('form.attribute');
})->with([
    'georgian' => ['ka', 'ბრენდი'],
    'english' => ['en', 'Brand'],
    'russian' => ['ru', 'Бренд'],
]);
