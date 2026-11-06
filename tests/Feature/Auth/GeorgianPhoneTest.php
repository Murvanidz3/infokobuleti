<?php

use App\Support\GeorgianPhone;

it('normalizes Georgian mobile numbers typed in common ways', function (string $input) {
    expect(GeorgianPhone::normalize($input))->toBe('+995555123456');
})->with(['555123456', '555 12 34 56', '+995 555 12-34-56', '995555123456', '00995555123456', '(555) 12 34 56']);

it('rejects numbers that are not Georgian mobiles', function (?string $input) {
    expect(GeorgianPhone::normalize($input))->toBeNull();
})->with(['', null, '422290000', '0555123456', '55512345', '5551234567', '+7 900 123 45 67', 'abc']);

it('formats and masks numbers for display', function () {
    expect(GeorgianPhone::format('+995555123456'))->toBe('+995 555 12 34 56')
        ->and(GeorgianPhone::mask('+995555123456'))->toBe('+995 555 •• •• 56');
});
