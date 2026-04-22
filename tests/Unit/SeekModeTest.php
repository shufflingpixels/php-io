<?php

use Shufflingpixels\IO\SeekMode;

it('maps to native seek constants', function () {
    expect(SeekMode::SET->value)->toBe(SEEK_SET)
        ->and(SeekMode::CUR->value)->toBe(SEEK_CUR)
        ->and(SeekMode::END->value)->toBe(SEEK_END);
});
