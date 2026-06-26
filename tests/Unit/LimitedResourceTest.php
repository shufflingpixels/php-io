<?php

use Shufflingpixels\IO\BinaryReader;
use Shufflingpixels\IO\Buffer;
use Shufflingpixels\IO\Exception\IOException;
use Shufflingpixels\IO\LimitedResource;

// --- Constructor validation ---

it('throws IOException when underlying stream is not seekable', function () {
    $stream = new class implements \Psr\Http\Message\StreamInterface {
        public function isSeekable(): bool { return false; }
        public function close(): void {}
        public function detach(): mixed { return null; }
        public function getSize(): ?int { return null; }
        public function eof(): bool { return true; }
        public function tell(): int { return 0; }
        public function seek(int $offset, int $whence = SEEK_SET): void {}
        public function rewind(): void {}
        public function isReadable(): bool { return false; }
        public function read(int $length): string { return ''; }
        public function isWritable(): bool { return false; }
        public function write(string $string): int { return 0; }
        public function getContents(): string { return ''; }
        public function getMetadata(?string $key = null): mixed { return null; }
        public function __toString(): string { return ''; }
    };

    expect(fn() => new LimitedResource($stream, 0, 5))->toThrow(IOException::class);
});

it('throws IOException for negative start offset', function () {
    expect(fn() => new LimitedResource(new Buffer('hello'), -1, 5))->toThrow(IOException::class);
});

it('throws IOException for negative length', function () {
    expect(fn() => new LimitedResource(new Buffer('hello'), 0, -1))->toThrow(IOException::class);
});

// --- Basic reads & position tracking ---

it('reads the correct bytes from a scoped window', function () {
    $limited = new LimitedResource(new Buffer('Hello, World!'), 7, 5);

    expect($limited->read(5))->toBe('World')
        ->and($limited->tell())->toBe(5)
        ->and($limited->eof())->toBeTrue();
});

it('advances position correctly across partial reads', function () {
    $limited = new LimitedResource(new Buffer('Hello, World!'), 7, 5);

    expect($limited->read(3))->toBe('Wor')
        ->and($limited->tell())->toBe(3);

    expect($limited->read(2))->toBe('ld')
        ->and($limited->tell())->toBe(5);
});

it('clamps read to window boundary without throwing', function () {
    $limited = new LimitedResource(new Buffer('Hello, World!'), 7, 5);

    expect($limited->read(9999))->toBe('World')
        ->and($limited->eof())->toBeTrue();
});

it('returns empty string when already at eof', function () {
    $limited = new LimitedResource(new Buffer('Hello, World!'), 7, 5);
    $limited->seek(5);

    expect($limited->read(1))->toBe('');
});

it('returns empty string for zero-length read', function () {
    $limited = new LimitedResource(new Buffer('Hello, World!'), 7, 5);

    expect($limited->read(0))->toBe('');
    expect($limited->tell())->toBe(0);
});

it('throws InvalidArgumentException for negative read length', function () {
    $limited = new LimitedResource(new Buffer('hello'), 0, 5);

    expect(fn() => $limited->read(-1))->toThrow(InvalidArgumentException::class);
});

// --- Seek modes ---

it('supports SEEK_SET within the window', function () {
    $limited = new LimitedResource(new Buffer('Hello, World!'), 7, 5);
    $limited->seek(2);

    expect($limited->tell())->toBe(2)
        ->and($limited->read(3))->toBe('rld');
});

it('supports SEEK_CUR within the window', function () {
    $limited = new LimitedResource(new Buffer('Hello, World!'), 7, 5);
    $limited->read(3);
    $limited->seek(-1, SEEK_CUR);

    expect($limited->tell())->toBe(2)
        ->and($limited->read(1))->toBe('r');
});

it('supports SEEK_END within the window', function () {
    $limited = new LimitedResource(new Buffer('Hello, World!'), 7, 5);
    $limited->seek(-2, SEEK_END);

    expect($limited->tell())->toBe(3)
        ->and($limited->read(2))->toBe('ld');
});

it('allows seeking to exactly the window length (eof position)', function () {
    $limited = new LimitedResource(new Buffer('Hello, World!'), 7, 5);
    $limited->seek(5);

    expect($limited->tell())->toBe(5)
        ->and($limited->eof())->toBeTrue();
});

it('throws OutOfBoundsException for seek past window end', function () {
    $limited = new LimitedResource(new Buffer('Hello, World!'), 7, 5);

    expect(fn() => $limited->seek(6))->toThrow(OutOfBoundsException::class);
});

it('throws OutOfBoundsException for negative seek position', function () {
    $limited = new LimitedResource(new Buffer('Hello, World!'), 7, 5);

    expect(fn() => $limited->seek(-1, SEEK_SET))->toThrow(OutOfBoundsException::class);
});

it('throws InvalidArgumentException for invalid seek whence', function () {
    $limited = new LimitedResource(new Buffer('Hello, World!'), 7, 5);

    expect(fn() => $limited->seek(0, 99))->toThrow(InvalidArgumentException::class);
});

// --- getSize / eof / rewind / getContents / __toString ---

it('getSize returns window length regardless of underlying stream size', function () {
    $limited = new LimitedResource(new Buffer('Hello, World!'), 7, 5);

    expect($limited->getSize())->toBe(5);
});

it('isSeekable returns true', function () {
    expect((new LimitedResource(new Buffer('hello'), 0, 5))->isSeekable())->toBeTrue();
});

it('isReadable returns true', function () {
    expect((new LimitedResource(new Buffer('hello'), 0, 5))->isReadable())->toBeTrue();
});

it('rewind resets position to zero', function () {
    $limited = new LimitedResource(new Buffer('Hello, World!'), 7, 5);
    $limited->read(3);
    $limited->rewind();

    expect($limited->tell())->toBe(0)
        ->and($limited->read(5))->toBe('World');
});

it('getContents returns remaining window content from current position', function () {
    $limited = new LimitedResource(new Buffer('Hello, World!'), 7, 5);
    $limited->read(2);

    expect($limited->getContents())->toBe('rld')
        ->and($limited->getContents())->toBe('');
});

it('__toString returns full window content regardless of current position', function () {
    $limited = new LimitedResource(new Buffer('Hello, World!'), 7, 5);
    $limited->read(3);

    expect((string) $limited)->toBe('World');
});

// --- Zero-length window ---

it('handles zero-length window: immediate eof, reads return empty', function () {
    $limited = new LimitedResource(new Buffer('hello'), 3, 0);

    expect($limited->getSize())->toBe(0)
        ->and($limited->eof())->toBeTrue()
        ->and($limited->read(5))->toBe('')
        ->and($limited->getContents())->toBe('');
});

it('zero-length window allows SEEK_END with offset 0 without throwing', function () {
    $limited = new LimitedResource(new Buffer('hello'), 3, 0);
    $limited->seek(0, SEEK_END);

    expect($limited->tell())->toBe(0);
});

// --- Lifecycle ---

it('close does not close the underlying stream', function () {
    $buffer = new Buffer('Hello, World!');
    $limited = new LimitedResource($buffer, 7, 5);
    $limited->close();

    expect($buffer->read(5))->toBe('Hello');
});

it('detach returns null', function () {
    $limited = new LimitedResource(new Buffer('hello'), 0, 5);

    expect($limited->detach())->toBeNull();
});

it('getSize returns null after detach', function () {
    $limited = new LimitedResource(new Buffer('hello'), 0, 5);
    $limited->close();

    expect($limited->getSize())->toBeNull();
});

it('eof returns true after detach', function () {
    $limited = new LimitedResource(new Buffer('hello'), 0, 5);
    $limited->close();

    expect($limited->eof())->toBeTrue();
});

it('throws RuntimeException on read after detach', function () {
    $limited = new LimitedResource(new Buffer('hello'), 0, 5);
    $limited->close();

    expect(fn() => $limited->read(1))->toThrow(RuntimeException::class);
});

it('throws RuntimeException on tell after detach', function () {
    $limited = new LimitedResource(new Buffer('hello'), 0, 5);
    $limited->close();

    expect(fn() => $limited->tell())->toThrow(RuntimeException::class);
});

it('throws RuntimeException on seek after detach', function () {
    $limited = new LimitedResource(new Buffer('hello'), 0, 5);
    $limited->close();

    expect(fn() => $limited->seek(0))->toThrow(RuntimeException::class);
});

it('throws RuntimeException on getContents after detach', function () {
    $limited = new LimitedResource(new Buffer('hello'), 0, 5);
    $limited->close();

    expect(fn() => $limited->getContents())->toThrow(RuntimeException::class);
});

it('__toString returns empty string after detach', function () {
    $limited = new LimitedResource(new Buffer('hello'), 0, 5);
    $limited->close();

    expect((string) $limited)->toBe('');
});

// --- Write rejection ---

it('isWritable returns false', function () {
    expect((new LimitedResource(new Buffer('hello'), 0, 5))->isWritable())->toBeFalse();
});

it('write throws IOException', function () {
    $limited = new LimitedResource(new Buffer('hello'), 0, 5);

    expect(fn() => $limited->write('x'))->toThrow(IOException::class);
});

// --- Underlying position independence ---

it('reads correctly regardless of where the underlying stream cursor is', function () {
    $buffer = new Buffer('Hello, World!');
    $limited = new LimitedResource($buffer, 7, 5);

    $buffer->seek(0);

    expect($limited->read(5))->toBe('World');
});

it('two LimitedResource windows on the same stream read independently', function () {
    $buffer = new Buffer('Hello, World!');
    $hello  = new LimitedResource($buffer, 0, 5);
    $world  = new LimitedResource($buffer, 7, 5);

    expect($hello->read(3))->toBe('Hel')
        ->and($world->read(3))->toBe('Wor')
        ->and($hello->read(2))->toBe('lo')
        ->and($world->read(2))->toBe('ld');
});

// --- BinaryReader integration ---

it('integrates with BinaryReader for binary reads from a scoped section', function () {
    $value = 0xDEADBEEF;
    $data  = str_repeat("\x00", 4) . pack('V', $value) . str_repeat("\x00", 4);
    $buffer = new Buffer($data);

    $limited = new LimitedResource($buffer, 4, 4);
    $reader  = BinaryReader::stream($limited);

    expect($reader->length())->toBe(4)
        ->and($reader->readUInt32LE())->toBe($value);
});

it('BinaryReader seek works within the window', function () {
    $value = 0x0000CAFE;
    $data  = str_repeat("\x00", 8) . pack('V', $value);
    $buffer = new Buffer($data);

    $limited = new LimitedResource($buffer, 8, 4);
    $reader  = BinaryReader::stream($limited);

    $first = $reader->readUInt32LE();
    $reader->seek(0);
    $second = $reader->readUInt32LE();

    expect($first)->toBe($value)
        ->and($second)->toBe($value);
});

// --- getMetadata ---

it('getMetadata always returns null', function () {
    $limited = new LimitedResource(new Buffer('hello'), 0, 5);

    expect($limited->getMetadata())->toBeNull()
        ->and($limited->getMetadata('seekable'))->toBeNull();
});
