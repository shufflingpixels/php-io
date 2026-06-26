<?php

declare(strict_types=1);


namespace Shufflingpixels\IO;

use InvalidArgumentException;
use Psr\Http\Message\StreamInterface;

class BinaryWriter
{
    public function __construct(protected StreamInterface $stream)
    {
    }

    public function tell(): int
    {
        return $this->stream->tell();
    }

    public function seek(int $position, int $whence = SEEK_SET): void
    {
        $this->stream->seek($position, $whence);
    }

    public function write(string $data): int
    {
        return $this->stream->write($data);
    }

    public function writeUInt8(int $value): int
    {
        return $this->write(\chr($value & 0xff));
    }

    public function writeInt8(int $value): int
    {
        return $this->writeUInt8($value < 0 ? $value + 0x100 : $value);
    }

    public function writeUInt16LE(int $value): int
    {
        return $this->write(\pack('v', $value));
    }

    public function writeUInt16BE(int $value): int
    {
        return $this->write(\pack('n', $value));
    }

    public function writeInt16LE(int $value): int
    {
        return $this->writeUInt16LE($value < 0 ? $value + 0x1_0000 : $value);
    }

    public function writeInt16BE(int $value): int
    {
        return $this->writeUInt16BE($value < 0 ? $value + 0x1_0000 : $value);
    }

    public function writeUInt32LE(int $value): int
    {
        return $this->write(\pack('V', $value));
    }

    public function writeUInt32BE(int $value): int
    {
        return $this->write(\pack('N', $value));
    }

    public function writeInt32LE(int $value): int
    {
        return $this->writeUInt32LE($value < 0 ? $value + 0x1_0000_0000 : $value);
    }

    public function writeInt32BE(int $value): int
    {
        return $this->writeUInt32BE($value < 0 ? $value + 0x1_0000_0000 : $value);
    }

    /**
     * Writes a string padded or truncated to exactly $length bytes.
     */
    public function writePaddedString(string $data, int $length, string $pad_char = "\x00"): int
    {
        if (\strlen($pad_char) !== 1) {
            throw new InvalidArgumentException('pad_char must be exactly one byte');
        }

        return $this->write(\substr(\str_pad($data, $length, $pad_char), 0, $length));
    }
}
