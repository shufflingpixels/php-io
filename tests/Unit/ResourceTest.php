<?php

use Shufflingpixels\IO\Exception\IOException;
use Shufflingpixels\IO\Resource;

it('reads, writes, seeks and reports stream state', function () {
    $handle = fopen('php://temp', 'r+');
    fwrite($handle, 'hello');
    rewind($handle);

    $stream = new class($handle, true, true, true) extends Resource {
        public function __construct($resource, bool $seekable, bool $readable, bool $writeable)
        {
            parent::__construct($resource, $seekable, $readable, $writeable);
        }
    };

    expect($stream->length())->toBe(5)
        ->and($stream->tell())->toBe(0)
        ->and($stream->isSeekable())->toBeTrue()
        ->and($stream->isReadable())->toBeTrue()
        ->and($stream->isWriteable())->toBeTrue()
        ->and($stream->read(2))->toBe('he');

    $stream->seek(0);
    expect($stream->write('H'))->toBe(1);

    $stream->seek(0);
    expect($stream->read(5))->toBe('Hello')
        ->and($stream->length())->toBe(5)
        ->and($stream->eof())->toBeFalse();

    $stream->read(1);
    expect($stream->eof())->toBeTrue();

    $stream->close();
});

it('throws when seeking or getting length on non-seekable stream', function () {
    $handle = fopen('php://temp', 'r+');

    $stream = new class($handle, false, true, false) extends Resource {
        public function __construct($resource, bool $seekable, bool $readable, bool $writeable)
        {
            parent::__construct($resource, $seekable, $readable, $writeable);
        }
    };

    expect(fn () => $stream->length())->toThrow(IOException::class)
        ->and(fn () => $stream->seek(0))->toThrow(IOException::class);

    $stream->close();
});

it('throws when writing to a non-writeable stream', function () {
    $handle = fopen('php://temp', 'r+');

    $stream = new class($handle, true, true, false) extends Resource {
        public function __construct($resource, bool $seekable, bool $readable, bool $writeable)
        {
            parent::__construct($resource, $seekable, $readable, $writeable);
        }
    };

    expect(fn () => $stream->write('x'))->toThrow(IOException::class, 'not writeable');

    $stream->close();
});
