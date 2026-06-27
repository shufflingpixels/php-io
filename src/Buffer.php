<?php

declare(strict_types=1);


namespace Shufflingpixels\IO;

use InvalidArgumentException;
use OutOfBoundsException;

/**
 * An in-memory byte stream backed by a plain PHP string.
 *
 * Supports both reading and writing. Writing at the current cursor position
 * overwrites existing bytes and extends the buffer if the write goes past the end.
 */
class Buffer implements ReadWriteSeekerInterface
{
    private int $position = 0;

    public function __construct(protected string $data)
    {
    }

    /**
     * Returns the total byte length of the buffer.
     */
    public function length(): int
    {
        return \strlen($this->data);
    }

    /**
     * Returns true when the cursor is at or past the end of the buffer.
     */
    public function eof(): bool
    {
        return $this->position >= $this->length();
    }

    /** Returns the current byte offset of the cursor. */
    public function tell() : int
    {
        return $this->position;
    }

    /**
     * Moves the cursor to the given position.
     *
     * @param int $whence SEEK_SET, SEEK_CUR, or SEEK_END
     * @throws \InvalidArgumentException for an unrecognised $whence value
     * @throws \OutOfBoundsException if the resolved position is outside [0, length]
     */
    public function seek(int $offset, int $whence = SEEK_SET): void
    {
        $position = match($whence) {
            SEEK_SET => $offset,
            SEEK_CUR => $this->position + $offset,
            SEEK_END => $this->length() + $offset,
            default => throw new InvalidArgumentException("Invalid seek mode")
        };

        if ($position < 0 || $position > $this->length()) {
            throw new OutOfBoundsException("Seek position out of bounds: {$position}");
        }

        $this->position = $position;
    }

    /**
     * Reads up to $length bytes from the current cursor position.
     *
     * Returns a partial result when a read extends past the end of the buffer.
     * Returns false when already at end of stream.
     *
     * @throws \InvalidArgumentException if $length is negative
     */
    public function read(int $length): string|false
    {
        if ($length < 0) {
            throw new InvalidArgumentException("Length must be >= 0");
        }

        if ($this->eof()) {
            return false;
        }

        $result = substr($this->data, $this->position, $length);
        $this->position += \strlen($result);
        return $result;
    }

    /**
     * Writes $string at the current cursor position, overwriting existing bytes.
     *
     * If the write extends past the end of the buffer, the buffer grows accordingly.
     * Returns the number of bytes written.
     */
    public function write(string $string): int
    {
        $length = \strlen($string);

        if ($length === 0) {
            return 0;
        }

        $prefix = \substr($this->data, 0, $this->position);
        $suffixStart = $this->position + $length;
        $suffix = $suffixStart < $this->length()
            ? \substr($this->data, $suffixStart)
            : '';

        $this->data = "{$prefix}{$string}{$suffix}";
        $this->position += $length;

        return $length;
    }
}
