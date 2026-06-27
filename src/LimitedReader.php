<?php

declare(strict_types=1);


namespace Shufflingpixels\IO;

use InvalidArgumentException;


/**
 * A read-only byte-count-limited wrapper around another reader.
 *
 * Forwards reads to the underlying reader but clamps them to a fixed number
 * of bytes, making it possible to hand a section of a sequential stream to a
 * consumer without the consumer reading past the end of that section.
 */
class LimitedReader implements ReaderInterface
{
    /**
     * @param ReaderInterface $r         The underlying reader to read from.
     * @param int             $remaining Maximum number of bytes that may be read.
     */
    public function __construct(
        private ReaderInterface $r,
        private int $remaining,
    ) {
    }

    /**
     * Reads up to $length bytes, clamped to the remaining byte budget.
     *
     * Returns false when the byte budget is exhausted.
     *
     * @throws \InvalidArgumentException if $length is negative
     */
    public function read(int $length): string|false
    {
        if ($length < 0) {
            throw new InvalidArgumentException('Length must be >= 0');
        }

        if ($this->remaining <= 0) {
            return false;
        }

        $data = $this->r->read(min($length, $this->remaining));
        $this->remaining -= \strlen($data);
        return $data;
    }
}
