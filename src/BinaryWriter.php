<?php

declare(strict_types=1);


namespace Shufflingpixels\IO;

use InvalidArgumentException;

/**
 * Writes primitive binary types to a writer.
 *
 * Integer methods follow the naming convention write{Signedness}{Bits}{Endianness},
 * e.g. writeInt16LE for a signed 16-bit little-endian integer.
 *
 * All write methods return the number of bytes written, as forwarded from the writer.
 */
class BinaryWriter
{
    public function __construct(protected WriterInterface $w)
    {
    }

    /**
     * Writes an unsigned 8-bit integer (0–255).
     */
    public function writeUInt8(int $value): int
    {
        return $this->w->write(\chr($value & 0xff));
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
        return $this->w->write(\pack('v', $value));
    }

    /**
     * Writes an unsigned 16-bit big-endian integer.
     */
    public function writeUInt16BE(int $value): int
    {
        return $this->w->write(\pack('n', $value));
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
        return $this->w->write(\pack('V', $value));
    }

    /**
     * Writes an unsigned 32-bit big-endian integer.
     */
    public function writeUInt32BE(int $value): int
    {
        return $this->w->write(\pack('N', $value));
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

        return $this->w->write(\substr(\str_pad($data, $length, $pad_char), 0, $length));
    }
}
