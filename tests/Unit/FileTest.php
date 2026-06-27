<?php

use Shufflingpixels\IO\Exception\IOException;
use Shufflingpixels\IO\File;
use Shufflingpixels\IO\FileMode;

it('opens a file and reads its contents', function () {
    $path = tempnam(sys_get_temp_dir(), 'php-io-');
    file_put_contents($path, 'hello');

    $file = File::open($path, FileMode::READ);

    expect($file->length())->toBe(5)
        ->and($file->read(5))->toBe('hello');

    $file->close();
    unlink($path);
});

it('opens a file for reading and writing', function () {
    $path = tempnam(sys_get_temp_dir(), 'php-io-');
    file_put_contents($path, 'abc');

    $file = File::open($path, FileMode::RW);
    $file->seek(0);

    expect($file->write('X'))->toBe(1);

    $file->seek(0);
    expect($file->read(3))->toBe('Xbc');

    $file->close();
    unlink($path);
});

it('opens a file for writing and truncates existing contents', function () {
    $path = tempnam(sys_get_temp_dir(), 'php-io-');
    file_put_contents($path, 'abcdef');

    $file = File::open($path, FileMode::WRITE);

    expect($file->length())->toBe(0)
        ->and($file->write('xy'))->toBe(2);

    $file->close();
    expect(file_get_contents($path))->toBe('xy');
    unlink($path);
});

it('throws when writing to a read-only file', function () {
    $path = tempnam(sys_get_temp_dir(), 'php-io-');
    file_put_contents($path, 'hello');

    $file = File::open($path, FileMode::READ);

    expect(fn () => $file->write('x'))->toThrow(IOException::class);

    $file->close();
    unlink($path);
});

it('throws an IOException when opening a missing path', function () {
    $path = sys_get_temp_dir() . '/php-io-missing-dir/' . uniqid('', true) . '.txt';

    expect(fn () => File::open($path, FileMode::READ))->toThrow(IOException::class);
});
