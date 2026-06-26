<?php

declare(strict_types=1);


namespace Shufflingpixels\IO;

use InvalidArgumentException;
use Psr\Http\Message\StreamInterface;
use RuntimeException;

class BinaryReader
{
    public function __construct(protected StreamInterface $stream)
    {
    }

    public static function stream(StreamInterface $stream)
    {
        return new self($stream);
    }

    public static function string(string $data)
    {
        return self::stream(new Buffer($data));
    }

    public function length() : int
    {
        $size = $this->stream->getSize();
        if ($size === null) {
            throw new RuntimeException('Stream size is not known');
        }

        return $size;
    }

    public function tell() : int
    {
        return $this->stream->tell();
    }

    public function eof() : bool
    {
        return $this->stream->eof();
    }

    public function seek(int $position, int $whence = SEEK_SET): void
    {
        $this->stream->seek($position, $whence);
    }

    /**
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
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

    public function readUInt8(): int
    {
        return \ord($this->read(1));
    }

    public function readInt8(): int
    {
        $value = $this->readUInt8();

        return $value >= 0x80 ? $value - 0x100 : $value;
    }

    public function readUInt16LE(): int
    {
        return \unpack('v', $this->read(2))[1];
    }

    public function readUInt16BE(): int
    {
        return \unpack('n', $this->read(2))[1];
    }

    public function readInt16LE(): int
    {
        $value = $this->readUInt16LE();

        return $value >= 0x8000 ? $value - 0x1_0000 : $value;
    }

    public function readInt16BE(): int
    {
        $value = $this->readUInt16BE();

        return $value >= 0x8000 ? $value - 0x1_0000 : $value;
    }

    public function readUInt32LE(): int
    {
        return \unpack('V', $this->read(4))[1];
    }

    public function readUInt32BE(): int
    {
        return \unpack('N', $this->read(4))[1];
    }

    public function readInt32LE(): int
    {
        $value = $this->readUInt32LE();

        return $value >= 0x8000_0000 ? $value - 0x1_0000_0000 : $value;
    }

    public function readInt32BE(): int
    {
        $value = $this->readUInt32BE();

        return $value >= 0x8000_0000 ? $value - 0x1_0000_0000 : $value;
    }

    /**
     * Reads a string of fixed length, trimming any padding characters from the end.
     */
    public function readPaddedString(int $length, string $pad_chars = "\x00") : string
    {
        $data = $this->read($length);
        return rtrim($data, $pad_chars);
    }
}
