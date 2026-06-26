<?php

declare(strict_types=1);


namespace Shufflingpixels\IO;

use InvalidArgumentException;
use OutOfBoundsException;
use Psr\Http\Message\StreamInterface;
use RuntimeException;

/**
 * An in-memory PSR-7 stream backed by a plain PHP string.
 *
 * Supports both reading and writing. Writing at the current cursor position
 * overwrites existing bytes and extends the buffer if the write goes past the end.
 */
class Buffer implements StreamInterface
{
    private int $position = 0;
    private bool $detached = false;

    public function __construct(protected string $data)
    {
    }

    /**
     * Detaches and discards the internal string.
     */
    public function close(): void
    {
        $this->detach();
    }

    /**
     * Detaches the internal string, clearing its contents, and returns null.
     */
    public function detach(): mixed
    {
        if ($this->detached) {
            return null;
        }

        $this->detached = true;
        $this->data = '';
        $this->position = 0;

        return null;
    }

    /**
     * Returns the byte length of the buffer, or null after detach.
     */
    public function getSize(): ?int
    {
        return $this->detached ? null : $this->length();
    }

    /**
     * Returns the total byte length of the buffer.
     */
    public function length(): int
    {
        return \strlen($this->data);
    }

    /**
     * Returns the number of bytes between the current cursor position and the end.
     */
    public function remaining() : int
    {
        if ($this->detached) {
            return 0;
        }

        return $this->length() - $this->position;
    }

    /**
     * Returns true when the cursor is at or past the end of the buffer.
     */
    public function eof(): bool
    {
        return $this->remaining() === 0;
    }

    /**
     * Returns the current byte offset of the cursor.
     *
     * @throws \RuntimeException if the stream is detached
     */
    public function tell() : int
    {
        $this->ensureAttached();

        return $this->position;
    }

    /**
     * Always returns true — buffers are always seekable.
     */
    public function isSeekable(): bool
    {
        return true;
    }

    /**
     * Moves the cursor to the given position.
     *
     * @param int $whence SEEK_SET, SEEK_CUR, or SEEK_END
     * @throws \InvalidArgumentException for an unrecognised $whence value
     * @throws \OutOfBoundsException if the resolved position is outside [0, length]
     * @throws \RuntimeException if the stream is detached
     */
    public function seek(int $offset, int $whence = SEEK_SET): void
    {
        $this->ensureAttached();

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
     * Moves the cursor to the start of the buffer.
     */
    public function rewind(): void
    {
        $this->seek(0);
    }

    /**
     * Always returns true — buffers are always readable.
     */
    public function isReadable(): bool
    {
        return true;
    }

    /**
     * Reads up to $length bytes from the current cursor position.
     *
     * Returns fewer bytes than requested when the end of the buffer is reached.
     *
     * @throws \InvalidArgumentException if $length is negative
     * @throws \RuntimeException if the stream is detached
     */
    public function read(int $length): string
    {
        $this->ensureAttached();

        if ($length < 0) {
            throw new InvalidArgumentException("Length must be >= 0");
        }

        if ($length === 0 || $this->eof()) {
            return '';
        }

        $result = substr($this->data, $this->position, min($length, $this->remaining()));
        $this->position += strlen($result);

        return $result;
    }

    /**
     * Always returns true — buffers are always writable.
     */
    public function isWritable(): bool
    {
        return true;
    }

    /**
     * Alias for {@see isWritable()}.
     */
    public function isWriteable(): bool
    {
        return $this->isWritable();
    }

    /**
     * Writes $string at the current cursor position, overwriting existing bytes.
     *
     * If the write extends past the end of the buffer, the buffer grows accordingly.
     * Returns the number of bytes written.
     *
     * @throws \RuntimeException if the stream is detached
     */
    public function write(string $string): int
    {
        $this->ensureAttached();

        $length = \strlen($string);

        if ($length === 0) {
            return 0;
        }

        $prefix = \substr($this->data, 0, $this->position);
        $suffixStart = $this->position + $length;
        $suffix = $suffixStart < $this->length()
            ? \substr($this->data, $suffixStart)
            : '';

        $this->data = $prefix . $string . $suffix;
        $this->position += $length;

        return $length;
    }

    /**
     * Returns all bytes from the current cursor position to the end and advances the cursor.
     *
     * @throws \RuntimeException if the stream is detached
     */
    public function getContents(): string
    {
        $this->ensureAttached();

        if ($this->eof()) {
            return '';
        }

        $result = substr($this->data, $this->position);
        $this->position = $this->length();

        return $result;
    }

    /**
     * Returns stream metadata, or a single key if $key is provided.
     *
     * Returns null for unknown keys.
     */
    public function getMetadata(?string $key = null): mixed
    {
        $metadata = [
            'seekable' => true,
            'readable' => true,
            'writable' => true,
            'uri' => null,
        ];

        return $key !== null ? $metadata[$key] ?? null : $metadata;
    }

    /**
     * Returns the full buffer contents regardless of cursor position. Returns '' after detach.
     */
    public function __toString(): string
    {
        if ($this->detached) {
            return '';
        }

        return $this->data;
    }

    /**
     * @throws \RuntimeException
     */
    private function ensureAttached(): void
    {
        if ($this->detached) {
            throw new RuntimeException('Stream is detached');
        }
    }
}
