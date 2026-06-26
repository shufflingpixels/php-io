<?php

declare(strict_types=1);


namespace Shufflingpixels\IO;

use InvalidArgumentException;
use Psr\Http\Message\StreamInterface;
use RuntimeException;

/**
 * Reads primitive binary types from a PSR-7 stream.
 *
 * Integer methods follow the naming convention read{Signedness}{Bits}{Endianness},
 * e.g. readInt16LE for a signed 16-bit little-endian integer.
 */
class BinaryReader
{
    public function __construct(protected StreamInterface $stream)
    {
    }

    /**
     * Returns the total byte length of the stream.
     *
     * @throws \RuntimeException if the stream size is not known
     */
    public function length() : int
    {
        $size = $this->stream->getSize();
        if ($size === null) {
            throw new RuntimeException('Stream size is not known');
        }

        return $size;
    }

    /**
     * Returns the current byte offset of the stream cursor.
     */
    public function tell() : int
    {
        return $this->stream->tell();
    }

    /**
     * Returns true when the cursor is at the end of the stream.
     */
    public function eof() : bool
    {
        return $this->stream->eof();
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
     * Reads exactly $length bytes, throwing if fewer are available.
     *
     * @throws \InvalidArgumentException if $length is negative
     * @throws \RuntimeException if the stream returns fewer bytes than requested
     */
    public function read(int $length): string
    {
        if ($length < 0) {
            throw new InvalidArgumentException('Length must be >= 0');
        }

        $data = $this->stream->read($length);
        if (\strlen($data) !== $length) {
            throw new RuntimeException(
                "Not enough bytes to read {$length} byte(s), " . \strlen($data) . ' read'
            );
        }

        return $data;
    }

    /**
     * Reads an unsigned 8-bit integer (0–255).
     */
    public function readUInt8(): int
    {
        return \ord($this->read(1));
    }

    /**
     * Reads a signed 8-bit integer (-128–127).
     */
    public function readInt8(): int
    {
        $value = $this->readUInt8();

        return $value >= 0x80 ? $value - 0x100 : $value;
    }

    /**
     * Reads an unsigned 16-bit little-endian integer.
     */
    public function readUInt16LE(): int
    {
        return \unpack('v', $this->read(2))[1];
    }

    /**
     * Reads an unsigned 16-bit big-endian integer.
     */
    public function readUInt16BE(): int
    {
        return \unpack('n', $this->read(2))[1];
    }

    /**
     * Reads a signed 16-bit little-endian integer.
     */
    public function readInt16LE(): int
    {
        $value = $this->readUInt16LE();

        return $value >= 0x8000 ? $value - 0x1_0000 : $value;
    }

    /**
     * Reads a signed 16-bit big-endian integer.
     */
    public function readInt16BE(): int
    {
        $value = $this->readUInt16BE();

        return $value >= 0x8000 ? $value - 0x1_0000 : $value;
    }

    /**
     * Reads an unsigned 32-bit little-endian integer.
     */
    public function readUInt32LE(): int
    {
        return \unpack('V', $this->read(4))[1];
    }

    /**
     * Reads an unsigned 32-bit big-endian integer.
     */
    public function readUInt32BE(): int
    {
        return \unpack('N', $this->read(4))[1];
    }

    /**
     * Reads a signed 32-bit little-endian integer.
     */
    public function readInt32LE(): int
    {
        $value = $this->readUInt32LE();

        return $value >= 0x8000_0000 ? $value - 0x1_0000_0000 : $value;
    }

    /**
     * Reads a signed 32-bit big-endian integer.
     */
    public function readInt32BE(): int
    {
        $value = $this->readUInt32BE();

        return $value >= 0x8000_0000 ? $value - 0x1_0000_0000 : $value;
    }

    /**
     * Reads exactly $length bytes and strips trailing $pad_chars from the result.
     *
     * The cursor always advances by $length bytes regardless of how much padding is trimmed.
     */
    public function readPaddedString(int $length, string $pad_chars = "\x00") : string
    {
        $data = $this->read($length);
        return rtrim($data, $pad_chars);
    }
}
