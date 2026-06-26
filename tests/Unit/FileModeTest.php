<?php

use Shufflingpixels\IO\FileMode;

it('defines expected fopen mode values', function () {
    expect(FileMode::READ->value)->toBe('r')
        ->and(FileMode::WRITE->value)->toBe('w')
        ->and(FileMode::RW->value)->toBe('r+');
});

it('reports read and write capabilities for each mode', function () {
    expect(FileMode::READ->readable())->toBeTrue()
        ->and(FileMode::READ->writable())->toBeFalse()
        ->and(FileMode::WRITE->readable())->toBeFalse()
        ->and(FileMode::WRITE->writable())->toBeTrue()
        ->and(FileMode::RW->readable())->toBeTrue()
        ->and(FileMode::RW->writable())->toBeTrue()
        ->and(FileMode::READ->seekable())->toBeTrue();
});
