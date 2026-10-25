<?php

use App\Models\Category;
use App\Support\AttributeSchema;
use Illuminate\Validation\ValidationException;

function carSchema(): array
{
    return [
        ['key' => 'make', 'type' => 'select', 'label' => ['ka' => 'მწარმოებელი', 'en' => 'Make', 'ru' => 'Марка'], 'required' => true,
            'options' => [['value' => 'toyota', 'label' => ['ka' => 'Toyota', 'en' => 'Toyota', 'ru' => 'Toyota']], ['value' => 'bmw', 'label' => ['ka' => 'BMW', 'en' => 'BMW', 'ru' => 'BMW']]]],
        ['key' => 'year', 'type' => 'number', 'label' => ['ka' => 'წელი', 'en' => 'Year', 'ru' => 'Год'], 'min' => 1980, 'max' => 2027],
        ['key' => 'hybrid', 'type' => 'boolean', 'label' => ['ka' => 'ჰიბრიდი', 'en' => 'Hybrid', 'ru' => 'Гибрид']],
        ['key' => 'extras', 'type' => 'multiselect', 'label' => ['ka' => 'დამატებითი', 'en' => 'Extras', 'ru' => 'Опции'],
            'options' => [['value' => 'ac', 'label' => ['ka' => 'კონდიციონერი', 'en' => 'A/C', 'ru' => 'Кондиционер']], ['value' => 'camera', 'label' => ['ka' => 'კამერა', 'en' => 'Camera', 'ru' => 'Камера']]]],
    ];
}

it('accepts values that match the category schema and drops unknown keys', function () {
    $values = (new AttributeSchema(carSchema()))->validate([
        'make' => 'toyota', 'year' => 2016, 'hybrid' => true, 'extras' => ['ac'], 'color' => 'red',
    ]);

    expect($values)->toBe(['make' => 'toyota', 'year' => 2016, 'hybrid' => true, 'extras' => ['ac']]);
});

it('rejects values outside the schema', function (array $values, string $errorKey) {
    try {
        (new AttributeSchema(carSchema()))->validate($values);
        $this->fail('Validation should have failed.');
    } catch (ValidationException $exception) {
        expect($exception->errors())->toHaveKey($errorKey);
    }
})->with([
    'missing required' => [['year' => 2016], 'attribute_values.make'],
    'unknown option' => [['make' => 'lada'], 'attribute_values.make'],
    'number out of range' => [['make' => 'bmw', 'year' => 1950], 'attribute_values.year'],
    'unknown multiselect option' => [['make' => 'bmw', 'extras' => ['jetpack']], 'attribute_values.extras.0'],
]);

it('inherits the schema of the parent category', function () {
    $cars = Category::factory()->withSchema(carSchema())->create();
    $sedans = Category::factory()->create(['parent_id' => $cars->id]);

    expect($sedans->load('parent')->schema()->fields())->toHaveCount(4)
        ->and(Category::factory()->create()->schema()->isEmpty())->toBeTrue();
});
