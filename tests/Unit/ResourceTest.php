<?php

use Shufflingpixels\IO\Exception\IOException;
use Shufflingpixels\IO\Resource;

function makeResource(string $mode = 'r+', string $initial = ''): Resource
{
    $handle = fopen('php://temp', $mode);
    if ($initial !== '') {
        fwrite($handle, $initial);
        rewind($handle);
    }

    return new class($handle) extends Resource {
        public function __construct(mixed $resource) { parent::__construct($resource); }
    };
}

it('reads, writes, seeks and reports length', function () {
    $stream = makeResource('r+', 'hello');

    expect($stream->length())->toBe(5)
        ->and($stream->tell())->toBe(0)
        ->and($stream->read(2))->toBe('he');

    $stream->seek(0);
    expect($stream->write('H'))->toBe(1);

    $stream->seek(0);
    expect($stream->read(5))->toBe('Hello');
});

it('length is recalculated after a write', function () {
    $stream = makeResource('r+', 'abc');

    expect($stream->length())->toBe(3);

    $stream->seek(0, SEEK_END);
    $stream->write('de');

    expect($stream->length())->toBe(5);
});

it('eof is true only after reading past the end', function () {
    $stream = makeResource('r+', 'ab');

    expect($stream->eof())->toBeFalse();

    $stream->read(3);

    expect($stream->eof())->toBeTrue();
});

it('returns false when reading at end of stream', function () {
    $stream = makeResource('r+', 'ab');
    $stream->read(3);

    expect($stream->read(1))->toBeFalse();
});

it('seek throws IOException on failure', function () {
    $stream = makeResource('r+', 'abc');

    expect(fn () => $stream->seek(-999))->toThrow(IOException::class);
});

it('write throws IOException when the underlying fwrite fails', function () {
    $handle = fopen('php://temp', 'r');
    $stream = new class($handle) extends Resource {
        public function __construct(mixed $resource) { parent::__construct($resource); }
    };

    expect(fn () => $stream->write('x'))->toThrow(IOException::class);
});

it('close releases the resource', function () {
    $stream = makeResource('r+', 'abc');
    $stream->close();

    expect(fn () => $stream->read(1))->toThrow(\TypeError::class);
});

it('detach returns the underlying resource', function () {
    $handle = fopen('php://temp', 'r+');
    $stream = new class($handle) extends Resource {
        public function __construct(mixed $resource) { parent::__construct($resource); }
    };

    $detached = $stream->detach();

    expect($detached)->toBe($handle);
});
