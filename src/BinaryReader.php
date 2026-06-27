<?php

declare(strict_types=1);


namespace Shufflingpixels\IO;

use InvalidArgumentException;
use RuntimeException;

/**
 * Reads primitive binary types from a reader.
 *
 * Integer methods follow the naming convention read{Signedness}{Bits}{Endianness},
 * e.g. readInt16LE for a signed 16-bit little-endian integer.
 */
class BinaryReader
{
    public function __construct(protected ReaderInterface $r)
    {
    }

    /**
     * Reads exactly $length bytes, throwing if fewer are available.
     *
     * @throws \InvalidArgumentException if $length is negative
     * @throws \RuntimeException if the stream returns fewer bytes than requested
     */
    public function readExact(int $length): string
    {
        if ($length < 0) {
            throw new InvalidArgumentException('Length must be >= 0');
        }

        $data = $this->r->read($length);
        $got = $data === false ? 0 : \strlen($data);
        if ($got !== $length) {
            throw new RuntimeException(
                "Not enough bytes to read {$length} byte(s), {$got} read"
            );
        }

        return $data;
    }

    /**
     * Reads an unsigned 8-bit integer (0–255).
     */
    public function readUInt8(): int
    {
        return \ord($this->readExact(1));
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
        return \unpack('v', $this->readExact(2))[1];
    }

    /**
     * Reads an unsigned 16-bit big-endian integer.
     */
    public function readUInt16BE(): int
    {
        return \unpack('n', $this->readExact(2))[1];
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
        return \unpack('V', $this->readExact(4))[1];
    }

    /**
     * Reads an unsigned 32-bit big-endian integer.
     */
    public function readUInt32BE(): int
    {
        return \unpack('N', $this->readExact(4))[1];
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
        $data = $this->readExact($length);
        return rtrim($data, $pad_chars);
    }
}
