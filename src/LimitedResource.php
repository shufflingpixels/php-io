<?php

declare(strict_types=1);


namespace Shufflingpixels\IO;

use InvalidArgumentException;
use OutOfBoundsException;
use Psr\Http\Message\StreamInterface;
use RuntimeException;
use Shufflingpixels\IO\Exception\IOException;


/**
 * A read-only PSR-7 stream window over a slice of another seekable stream.
 *
 * Presents the bytes [$start, $start + $length) of the underlying stream as an
 * independent stream with its own cursor starting at offset 0. The underlying
 * stream is seeked on every read, so LimitedResource instances over the same
 * base stream can be used independently without interfering with each other.
 */
class LimitedResource implements StreamInterface
{
    private int $position = 0;
    private bool $detached = false;

    /**
     * @param StreamInterface $stream The underlying stream to read from. Must be seekable.
     * @param int $start Byte offset in $stream where this window begins.
     * @param int $length Number of bytes this window exposes.
     * @throws IOException if $stream is not seekable, or $start or $length are negative
     */
    public function __construct(
        private StreamInterface $stream,
        private int $start,
        private int $length
    ) {
        if (!$stream->isSeekable()) {
            throw new IOException('Underlying stream must be seekable');
        }
        if ($start < 0) {
            throw new IOException('Start offset must be >= 0');
        }
        if ($length < 0) {
            throw new IOException('Length must be >= 0');
        }
    }

    /**
     * Detaches from the underlying stream. Does not close it.
     */
    public function close(): void
    {
        $this->detach();
    }

    /**
     * Detaches from the underlying stream and returns null. Does not close it.
     */
    public function detach(): mixed
    {
        if ($this->detached) {
            return null;
        }

        $this->detached = true;
        $this->position = 0;

        return null;
    }

    /**
     * Returns the window length in bytes, or null after detach.
     */
    public function getSize(): ?int
    {
        return $this->detached ? null : $this->length;
    }

    /**
     * Returns true when the cursor is at or past the end of the window, or after detach.
     */
    public function eof(): bool
    {
        return $this->detached || $this->position >= $this->length;
    }

    /**
     * Returns the current byte offset within the window (not the underlying stream).
     *
     * @throws \RuntimeException if the stream is detached
     */
    public function tell(): int
    {
        $this->ensureAttached();

        return $this->position;
    }

    /**
     * Always returns true — the window is always seekable.
     */
    public function isSeekable(): bool
    {
        return true;
    }

    /**
     * Moves the cursor to the given position within the window.
     *
     * @param int $whence SEEK_SET, SEEK_CUR, or SEEK_END (relative to the window, not the underlying stream)
     * @throws \InvalidArgumentException for an unrecognised $whence value
     * @throws \OutOfBoundsException if the resolved position is outside [0, length]
     * @throws \RuntimeException if the stream is detached
     */
    public function seek(int $offset, int $whence = SEEK_SET): void
    {
        $this->ensureAttached();

        $position = match ($whence) {
            SEEK_SET => $offset,
            SEEK_CUR => $this->position + $offset,
            SEEK_END => $this->length + $offset,
            default  => throw new InvalidArgumentException('Invalid seek mode'),
        };

        if ($position < 0 || $position > $this->length) {
            throw new OutOfBoundsException("Seek position out of bounds: {$position}");
        }

        $this->position = $position;
    }

    /**
     * Moves the cursor to the start of the window.
     */
    public function rewind(): void
    {
        $this->seek(0);
    }

    /**
     * Always returns true — the window is always readable.
     */
    public function isReadable(): bool
    {
        return true;
    }

    /**
     * Reads up to $length bytes from the current cursor position within the window.
     *
     * Clamps the read to the window boundary so it never reads into adjacent data.
     * Returns fewer bytes than requested when the end of the window is reached.
     *
     * @throws \InvalidArgumentException if $length is negative
     * @throws \RuntimeException if the stream is detached
     */
    public function read(int $length): string
    {
        $this->ensureAttached();

        if ($length < 0) {
            throw new InvalidArgumentException('Length must be >= 0');
        }

        if ($length === 0 || $this->eof()) {
            return '';
        }

        $toRead = min($length, $this->length - $this->position);
        $this->stream->seek($this->start + $this->position);
        $data = $this->stream->read($toRead);
        $this->position += \strlen($data);

        return $data;
    }

    /**
     * Always returns false — writing to a window is not supported.
     */
    public function isWritable(): bool
    {
        return false;
    }

    /**
     * @throws IOException always — the window is read-only
     */
    public function write(string $string): int
    {
        throw new IOException('LimitedResource is read-only');
    }

    /**
     * Returns all bytes from the current cursor position to the end of the window.
     *
     * @throws \RuntimeException if the stream is detached
     */
    public function getContents(): string
    {
        $this->ensureAttached();

        if ($this->eof()) {
            return '';
        }

        return $this->read($this->length - $this->position);
    }

    /**
     * Always returns null — no metadata is available for a window stream.
     */
    public function getMetadata(?string $key = null): mixed
    {
        return null;
    }

    /**
     * Returns the full window contents regardless of current cursor position.
     *
     * Returns '' after detach or if an error occurs during reading.
     */
    public function __toString(): string
    {
        if ($this->detached) {
            return '';
        }

        try {
            $this->seek(0);
            return $this->getContents();
        } catch (\Throwable) {
            return '';
        }
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
