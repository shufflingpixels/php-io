<?php

namespace Shufflingpixels\IO;

use InvalidArgumentException;
use OutOfBoundsException;
use Shufflingpixels\IO\Exception\EndOfStreamException;

class Buffer implements StreamInterface
{
    private int $position = 0;

    public function __construct(protected string $data)
    {
    }

    public function close(): void
    {
    }

    public function length(): int
    {
        return \strlen($this->data);
    }

    public function remaining() : int
    {
        return $this->length() - $this->position;
    }

    public function eof(): bool
    {
        return $this->remaining() === 0;
    }

    public function tell() : int
    {
        return $this->position;
    }

    public function isSeekable(): bool
    {
        return true;
    }

    public function seek(int $offset, SeekMode $mode = SeekMode::SET): void
    {
        $position = match($mode) {
            SeekMode::SET => $offset,
            SeekMode::CUR => $this->position + $offset,
            SeekMode::END => $this->length() + $offset,
            default => throw new InvalidArgumentException("Invalid seek mode")
        };

        if ($position > $this->length()) {
            throw new OutOfBoundsException("Seek position out of bounds: {$position}");
        }

        $this->position = $position;
    }

    public function isReadable(): bool
    {
        return true;
    }

    public function read(int $length): string
    {
        if ($length < 0) {
            throw new InvalidArgumentException("Length must be >= 0");
        }

        if ($this->remaining() < $length) {
            throw new EndOfStreamException(
                "Not enough bytes to read {$length} byte(s), {$this->remaining()} remaining"
            );
        }

        $result = substr($this->data, $this->position, $length);
        $this->position += $length;

        return $result;
    }

    public function isWriteable(): bool
    {
        return true;
    }

    public function write(string $data): int
    {
        $length = \strlen($data);

        if ($length === 0) {
            return 0;
        }

        $prefix = \substr($this->data, 0, $this->position);
        $suffixStart = $this->position + $length;
        $suffix = $suffixStart < $this->length()
            ? \substr($this->data, $suffixStart)
            : '';

        $this->data = $prefix . $data . $suffix;
        $this->position += $length;

        return $length;
    }
}
