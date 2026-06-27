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
}
