<?php

namespace Shufflingpixels\IO;

use InvalidArgumentException;
use OutOfBoundsException;
use Psr\Http\Message\StreamInterface;
use RuntimeException;
use Shufflingpixels\IO\Exception\IOException;

/**
 *
 */
class LimitedResource implements StreamInterface
{
    private int $position = 0;
    private bool $detached = false;

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

    public function close(): void
    {
        $this->detach();
    }

    public function detach(): mixed
    {
        if ($this->detached) {
            return null;
        }

        $this->detached = true;
        $this->position = 0;

        return null;
    }

    public function getSize(): ?int
    {
        return $this->detached ? null : $this->length;
    }

    public function eof(): bool
    {
        return $this->detached || $this->position >= $this->length;
    }

    public function tell(): int
    {
        $this->ensureAttached();

        return $this->position;
    }

    public function isSeekable(): bool
    {
        return true;
    }

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

    public function rewind(): void
    {
        $this->seek(0);
    }

    public function isReadable(): bool
    {
        return true;
    }

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

    public function isWritable(): bool
    {
        return false;
    }

    public function write(string $string): int
    {
        throw new IOException('LimitedResource is read-only');
    }

    public function getContents(): string
    {
        $this->ensureAttached();

        if ($this->eof()) {
            return '';
        }

        return $this->read($this->length - $this->position);
    }

    public function getMetadata(?string $key = null): mixed
    {
        return null;
    }

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

    private function ensureAttached(): void
    {
        if ($this->detached) {
            throw new RuntimeException('Stream is detached');
        }
    }
}
