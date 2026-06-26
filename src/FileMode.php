<?php

declare(strict_types=1);


namespace Shufflingpixels\IO;

/**
 * File open modes passed to {@see File::open()}.
 *
 * READ  — read-only, file must exist.
 * WRITE — write-only, truncates or creates the file.
 * RW    — read/write, file must exist.
 */
enum FileMode : string
{
    case READ = 'r';
    case WRITE = 'w';
    case RW = 'r+';

    public function seekable(): bool
    {
        return true;
    }

    public function readable(): bool
    {
        return $this === self::READ || $this === self::RW;
    }

    public function writable(): bool
    {
        return $this === self::WRITE || $this === self::RW;
    }
}
