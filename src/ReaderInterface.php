<?php

declare(strict_types=1);

namespace Shufflingpixels\IO;

/** Describes a source of bytes that can be read sequentially. */
interface ReaderInterface
{
    /**
     * Reads up to $length bytes, returning fewer at end of stream.
     *
     * Returns false when no more bytes are available.
     */
    public function read(int $length): string|false;
}
