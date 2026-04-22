<?php

use Shufflingpixels\IO\Exception\IOException;
use Shufflingpixels\IO\File;
use Shufflingpixels\IO\FileMode;

it('opens readable files and reads contents', function () {
    $path = tempnam(sys_get_temp_dir(), 'php-io-');
    file_put_contents($path, 'hello');

    $file = File::open($path, FileMode::READ);

    expect($file->isReadable())->toBeTrue()
        ->and($file->isWriteable())->toBeFalse()
        ->and($file->length())->toBe(5)
        ->and($file->read(5))->toBe('hello');

    $file->close();
    unlink($path);
});

it('opens read-write files and persists writes', function () {
    $path = tempnam(sys_get_temp_dir(), 'php-io-');
    file_put_contents($path, 'abc');

    $file = File::open($path, FileMode::RW);
    $file->seek(0);

    expect($file->isReadable())->toBeTrue()
        ->and($file->isWriteable())->toBeTrue()
        ->and($file->write('X'))->toBe(1);

    $file->seek(0);
    expect($file->read(3))->toBe('Xbc');

    $file->close();
    unlink($path);
});

it('opens write mode files and truncates existing contents', function () {
    $path = tempnam(sys_get_temp_dir(), 'php-io-');
    file_put_contents($path, 'abcdef');

    $file = File::open($path, FileMode::WRITE);

    expect($file->isReadable())->toBeFalse()
        ->and($file->isWriteable())->toBeTrue()
        ->and($file->length())->toBe(0)
        ->and($file->write('xy'))->toBe(2);

    $file->close();
    expect(file_get_contents($path))->toBe('xy');
    unlink($path);
});

it('throws an io exception when opening a missing path', function () {
    $path = sys_get_temp_dir() . '/php-io-missing-dir/' . uniqid('', true) . '.txt';

    expect(fn () => File::open($path, FileMode::READ))->toThrow(IOException::class);
});
