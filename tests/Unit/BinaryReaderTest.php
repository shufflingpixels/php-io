<?php

use Shufflingpixels\IO\BinaryReader;
use Shufflingpixels\IO\Buffer;

it('creates reader from stream and string helpers', function () {
    $streamReader = BinaryReader::stream(new Buffer('abc'));
    $stringReader = BinaryReader::string('xyz');

    expect($streamReader)->toBeInstanceOf(BinaryReader::class)
        ->and($stringReader->read(3))->toBe('xyz');
});

it('proxies length tell eof and seek', function () {
    $reader = BinaryReader::string('abcd');

    expect($reader->length())->toBe(4)
        ->and($reader->tell())->toBe(0)
        ->and($reader->eof())->toBeFalse();

    $reader->seek(2);
    expect($reader->tell())->toBe(2)
        ->and($reader->read(1))->toBe('c');

    $reader->seek(-1, SEEK_END);
    expect($reader->read(1))->toBe('d')
        ->and($reader->eof())->toBeTrue();
});

it('throws for negative read length', function () {
    $reader = BinaryReader::string('abc');

    expect(fn () => $reader->read(-1))->toThrow(InvalidArgumentException::class);
});

it('throws when stream returns fewer bytes than requested', function () {
    $stream = new class implements \Psr\Http\Message\StreamInterface {
        public function __toString(): string { return ''; }
        public function close(): void {}
        public function detach(): mixed { return null; }
        public function getSize(): ?int { return 0; }
        public function eof(): bool { return false; }
        public function isSeekable(): bool { return false; }
        public function seek(int $position, int $whence = SEEK_SET): void {}
        public function rewind(): void {}
        public function tell(): int { return 0; }
        public function isWritable(): bool { return false; }
        public function write(string $string): int { return 0; }
        public function isReadable(): bool { return true; }
        public function read(int $length): string { return 'x'; }
        public function getContents(): string { return ''; }
        public function getMetadata(?string $key = null): mixed { return null; }
    };

    $reader = BinaryReader::stream($stream);

    expect(fn () => $reader->read(2))->toThrow(RuntimeException::class, 'Not enough bytes');
});

it('reads 8 bit integers', function () {
    $reader = BinaryReader::string("\x7f\x80\xff");

    expect($reader->readUInt8())->toBe(127)
        ->and($reader->readInt8())->toBe(-128)
        ->and($reader->readInt8())->toBe(-1);
});

it('reads 16 bit integers in little and big endian', function () {
    $reader = BinaryReader::string(pack('v', 0x1234) . pack('n', 0x5678) . pack('v', 0x8000) . pack('n', 0xffff));

    expect($reader->readUInt16LE())->toBe(0x1234)
        ->and($reader->readUInt16BE())->toBe(0x5678)
        ->and($reader->readInt16LE())->toBe(-32768)
        ->and($reader->readInt16BE())->toBe(-1);
});

it('reads 32 bit integers in little and big endian', function () {
    $reader = BinaryReader::string(pack('V', 0x12345678) . pack('N', 0x10203040) . pack('V', 0x80000000) . pack('N', 0xffffffff));

    expect($reader->readUInt32LE())->toBe(0x12345678)
        ->and($reader->readUInt32BE())->toBe(0x10203040)
        ->and($reader->readInt32LE())->toBe(-2147483648)
        ->and($reader->readInt32BE())->toBe(-1);
});

it('reads a padded string with no padding present', function () {
    $reader = BinaryReader::string('hello');

    expect($reader->readPaddedString(5))->toBe('hello');
});

it('strips null bytes from the end of a padded string', function () {
    $reader = BinaryReader::string("hello\x00\x00\x00");

    expect($reader->readPaddedString(8))->toBe('hello');
});

it('returns empty string for an all-null padded string', function () {
    $reader = BinaryReader::string("\x00\x00\x00");

    expect($reader->readPaddedString(3))->toBe('');
});

it('advances the cursor by the full field length after readPaddedString', function () {
    $reader = BinaryReader::string("hi\x00\x00" . 'end');

    $reader->readPaddedString(4);

    expect($reader->read(3))->toBe('end');
});

it('throws when not enough bytes remain for readPaddedString', function () {
    $reader = BinaryReader::string('ab');

    expect(fn () => $reader->readPaddedString(5))->toThrow(RuntimeException::class, 'Not enough bytes');
});

it('strips custom padding characters from the end of a padded string', function () {
    $reader = BinaryReader::string("hello   ");

    expect($reader->readPaddedString(8, ' '))->toBe('hello');
});
