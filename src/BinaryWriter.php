<?php

declare(strict_types=1);


namespace Shufflingpixels\IO;

use InvalidArgumentException;
use Psr\Http\Message\StreamInterface;

/**
 * Writes primitive binary types to a PSR-7 stream.
 *
 * Integer methods follow the naming convention write{Signedness}{Bits}{Endianness},
 * e.g. writeInt16LE for a signed 16-bit little-endian integer.
 *
 * All write methods return the number of bytes written, as forwarded from the stream.
 */
class BinaryWriter
{
    public function __construct(protected StreamInterface $stream)
    {
    }

    /**
     * Returns the current byte offset of the stream cursor.
     */
    public function tell(): int
    {
        return $this->stream->tell();
    }

    /**
     * Moves the stream cursor to the given position.
     *
     * @param int $whence SEEK_SET, SEEK_CUR, or SEEK_END
     */
    public function seek(int $position, int $whence = SEEK_SET): void
    {
        $this->stream->seek($position, $whence);
    }

    /**
     * Writes raw bytes to the stream.
     */
    public function write(string $data): int
    {
        return $this->stream->write($data);
    }

    /**
     * Writes an unsigned 8-bit integer (0–255).
     */
    public function writeUInt8(int $value): int
    {
        return $this->write(\chr($value & 0xff));
    }

    /**
     * Writes a signed 8-bit integer (-128–127).
     */
    public function writeInt8(int $value): int
    {
        return $this->writeUInt8($value < 0 ? $value + 0x100 : $value);
    }

    /**
     * Writes an unsigned 16-bit little-endian integer.
     */
    public function writeUInt16LE(int $value): int
    {
        return $this->write(\pack('v', $value));
    }

    /**
     * Writes an unsigned 16-bit big-endian integer.
     */
    public function writeUInt16BE(int $value): int
    {
        return $this->write(\pack('n', $value));
    }

    /**
     * Writes a signed 16-bit little-endian integer.
     */
    public function writeInt16LE(int $value): int
    {
        return $this->writeUInt16LE($value < 0 ? $value + 0x1_0000 : $value);
    }

    /**
     * Writes a signed 16-bit big-endian integer.
     */
    public function writeInt16BE(int $value): int
    {
        return $this->writeUInt16BE($value < 0 ? $value + 0x1_0000 : $value);
    }

    /**
     * Writes an unsigned 32-bit little-endian integer.
     */
    public function writeUInt32LE(int $value): int
    {
        return $this->write(\pack('V', $value));
    }

    /**
     * Writes an unsigned 32-bit big-endian integer.
     */
    public function writeUInt32BE(int $value): int
    {
        return $this->write(\pack('N', $value));
    }

    /**
     * Writes a signed 32-bit little-endian integer.
     */
    public function writeInt32LE(int $value): int
    {
        return $this->writeUInt32LE($value < 0 ? $value + 0x1_0000_0000 : $value);
    }

    /**
     * Writes a signed 32-bit big-endian integer.
     */
    public function writeInt32BE(int $value): int
    {
        return $this->writeUInt32BE($value < 0 ? $value + 0x1_0000_0000 : $value);
    }

    /**
     * Writes $data padded or truncated to exactly $length bytes.
     *
     * Strings shorter than $length are right-padded with $pad_char.
     * Strings longer than $length are truncated to $length bytes.
     *
     * @throws \InvalidArgumentException if $pad_char is not exactly one byte
     */
    public function writePaddedString(string $data, int $length, string $pad_char = "\x00"): int
    {
        if (\strlen($pad_char) !== 1) {
            throw new InvalidArgumentException('pad_char must be exactly one byte');
        }

        return $this->write(\substr(\str_pad($data, $length, $pad_char), 0, $length));
    }
}
