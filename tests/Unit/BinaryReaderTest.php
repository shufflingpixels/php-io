<?php

use Shufflingpixels\IO\BinaryReader;
use Shufflingpixels\IO\Buffer;
use Shufflingpixels\IO\ReaderInterface;

it('throws for negative read length', function () {
    $reader = new BinaryReader(new Buffer('abc'));

    expect(fn () => $reader->readExact(-1))->toThrow(InvalidArgumentException::class);
});

it('throws when stream returns fewer bytes than requested', function () {
    $stream = new class implements ReaderInterface {
        public function read(int $length): string|false { return 'x'; }
    };

    $reader = new BinaryReader($stream);

    expect(fn () => $reader->readExact(2))->toThrow(RuntimeException::class, 'Not enough bytes');
});

it('throws when stream returns false', function () {
    $stream = new class implements ReaderInterface {
        public function read(int $length): string|false { return false; }
    };

    $reader = new BinaryReader($stream);

    expect(fn () => $reader->readExact(1))->toThrow(RuntimeException::class, 'Not enough bytes');
});

it('reads 8 bit integers', function () {
    $reader = new BinaryReader(new Buffer("\x7f\x80\xff"));

    expect($reader->readUInt8())->toBe(127)
        ->and($reader->readInt8())->toBe(-128)
        ->and($reader->readInt8())->toBe(-1);
});

it('reads 16 bit integers in little and big endian', function () {
    $reader = new BinaryReader(new Buffer(pack('v', 0x1234) . pack('n', 0x5678) . pack('v', 0x8000) . pack('n', 0xffff)));

    expect($reader->readUInt16LE())->toBe(0x1234)
        ->and($reader->readUInt16BE())->toBe(0x5678)
        ->and($reader->readInt16LE())->toBe(-32768)
        ->and($reader->readInt16BE())->toBe(-1);
});

it('reads 32 bit integers in little and big endian', function () {
    $reader = new BinaryReader(new Buffer(pack('V', 0x12345678) . pack('N', 0x10203040) . pack('V', 0x80000000) . pack('N', 0xffffffff)));

    expect($reader->readUInt32LE())->toBe(0x12345678)
        ->and($reader->readUInt32BE())->toBe(0x10203040)
        ->and($reader->readInt32LE())->toBe(-2147483648)
        ->and($reader->readInt32BE())->toBe(-1);
});

it('reads a padded string with no padding present', function () {
    $reader = new BinaryReader(new Buffer('hello'));

    expect($reader->readPaddedString(5))->toBe('hello');
});

it('strips null bytes from the end of a padded string', function () {
    $reader = new BinaryReader(new Buffer("hello\x00\x00\x00"));

    expect($reader->readPaddedString(8))->toBe('hello');
});

it('returns empty string for an all-null padded string', function () {
    $reader = new BinaryReader(new Buffer("\x00\x00\x00"));

    expect($reader->readPaddedString(3))->toBe('');
});

it('advances the cursor by the full field length after readPaddedString', function () {
    $reader = new BinaryReader(new Buffer("hi\x00\x00" . 'end'));

    $reader->readPaddedString(4);

    expect($reader->readExact(3))->toBe('end');
});

it('throws when not enough bytes remain for readPaddedString', function () {
    $reader = new BinaryReader(new Buffer('ab'));

    expect(fn () => $reader->readPaddedString(5))->toThrow(RuntimeException::class, 'Not enough bytes');
});

it('strips custom padding characters from the end of a padded string', function () {
    $reader = new BinaryReader(new Buffer("hello   "));

    expect($reader->readPaddedString(8, ' '))->toBe('hello');
});
