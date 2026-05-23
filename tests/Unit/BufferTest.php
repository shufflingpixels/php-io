<?php

use Shufflingpixels\IO\Buffer;

it('reads, seeks and tracks position', function () {
    $buffer = new Buffer('abcdef');

    expect($buffer->getSize())->toBe(6)
        ->and($buffer->tell())->toBe(0)
        ->and($buffer->remaining())->toBe(6)
        ->and($buffer->eof())->toBeFalse();

    expect($buffer->read(2))->toBe('ab')
        ->and($buffer->tell())->toBe(2)
        ->and($buffer->remaining())->toBe(4);

    $buffer->seek(-1, SEEK_END);

    expect($buffer->tell())->toBe(5)
        ->and($buffer->read(1))->toBe('f')
        ->and($buffer->eof())->toBeTrue();
});

it('supports relative seek modes', function () {
    $buffer = new Buffer('abcdef');

    $buffer->seek(3);
    $buffer->seek(-2, SEEK_CUR);

    expect($buffer->tell())->toBe(1)
        ->and($buffer->read(2))->toBe('bc');
});

it('throws for out of bounds seek', function () {
    $buffer = new Buffer('abc');

    expect(fn () => $buffer->seek(4))->toThrow(OutOfBoundsException::class);
});

it('throws for negative read length', function () {
    $buffer = new Buffer('abc');

    expect(fn () => $buffer->read(-1))->toThrow(InvalidArgumentException::class);
});

it('returns available bytes when reading beyond end of stream', function () {
    $buffer = new Buffer('abc');

    expect($buffer->read(4))->toBe('abc')
        ->and($buffer->eof())->toBeTrue();
});

it('writes at current position and updates contents', function () {
    $buffer = new Buffer('abcdef');
    $buffer->seek(2);

    expect($buffer->write('XY'))->toBe(2)
        ->and($buffer->tell())->toBe(4);

    $buffer->seek(0);

    expect($buffer->read(6))->toBe('abXYef');
});

it('writes nothing for empty payload', function () {
    $buffer = new Buffer('abc');

    expect($buffer->write(''))->toBe(0)
        ->and($buffer->tell())->toBe(0)
        ->and($buffer->getSize())->toBe(3);
});

it('returns remaining contents and advances cursor', function () {
    $buffer = new Buffer('abcdef');
    $buffer->seek(2);

    expect($buffer->getContents())->toBe('cdef')
        ->and($buffer->tell())->toBe(6)
        ->and($buffer->getContents())->toBe('');
});
