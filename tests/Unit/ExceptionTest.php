<?php

use Shufflingpixels\IO\Exception\EndOfStreamException;
use Shufflingpixels\IO\Exception\IOException;

it('keeps the exception hierarchy stable', function () {
    expect(new IOException('io'))->toBeInstanceOf(Exception::class)
        ->and(new EndOfStreamException('eos'))->toBeInstanceOf(IOException::class);
});
