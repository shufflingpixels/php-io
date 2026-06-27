<?php

declare(strict_types=1);


namespace Shufflingpixels\IO;

/** Describes a sink of bytes that can be written to sequentially. */
interface WriterInterface
{
    /** Writes $data and returns the number of bytes written. */
    public function write(string $data): int;
}
