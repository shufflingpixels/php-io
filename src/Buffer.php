<?php

declare(strict_types=1);


namespace Shufflingpixels\IO;

use InvalidArgumentException;
use OutOfBoundsException;
use Psr\Http\Message\StreamInterface;
use RuntimeException;

class Buffer implements StreamInterface
{
    private int $position = 0;
    private bool $detached = false;

    public function __construct(protected string $data)
    {
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
        $this->data = '';
        $this->position = 0;

        return null;
    }

    public function getSize(): ?int
    {
        return $this->detached ? null : $this->length();
    }

    public function length(): int
    {
        return \strlen($this->data);
    }

    public function remaining() : int
    {
        if ($this->detached) {
            return 0;
        }

        return $this->length() - $this->position;
    }

    public function eof(): bool
    {
        return $this->remaining() === 0;
    }

    public function tell() : int
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
            throw new InvalidArgumentException("Length must be >= 0");
        }

        if ($length === 0 || $this->eof()) {
            return '';
        }

        $result = substr($this->data, $this->position, min($length, $this->remaining()));
        $this->position += strlen($result);

        return $result;
    }

    public function isWritable(): bool
    {
        return true;
    }

    public function isWriteable(): bool
    {
        return $this->isWritable();
    }

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

    public function __toString(): string
    {
        if ($this->detached) {
            return '';
        }

        return $this->data;
    }

    private function ensureAttached(): void
    {
        if ($this->detached) {
            throw new RuntimeException('Stream is detached');
        }
    }
}
