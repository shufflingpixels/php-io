<?php

use Shufflingpixels\IO\FileMode;

it('defines expected fopen mode values', function () {
    expect(FileMode::READ->value)->toBe('r')
        ->and(FileMode::WRITE->value)->toBe('w')
        ->and(FileMode::RW->value)->toBe('r+');
});
