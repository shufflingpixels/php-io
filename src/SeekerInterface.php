<?php

declare(strict_types=1);

namespace Shufflingpixels\IO;

/**
 * Describes a seekable byte stream that can report and change its cursor position.
 */
interface SeekerInterface
{
    /**
     * Moves the cursor to the given position.
     *
     * @param int $whence SEEK_SET, SEEK_CUR, or SEEK_END
     * @throws \InvalidArgumentException for an unrecognized $whence value
     */
    public function seek(int $offset, int $whence = SEEK_SET): void;

    /** Returns the current byte offset of the cursor. */
    public function tell(): int;

    /** Returns true when the cursor is at the end of the stream. */
    public function eof(): bool;

    /** Returns the total byte length of the stream. */
    public function length(): int;
}
