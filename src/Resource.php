<?php

namespace Shufflingpixels\IO;

use Psr\Http\Message\StreamInterface;
use Shufflingpixels\IO\Exception\IOException;

abstract class Resource implements StreamInterface
{
    protected ?int $size = null;

    /**
     * @param resource $resource
     */
    protected function __construct(
        protected mixed $resource,
        protected bool $seekable,
        protected bool $readable,
        protected bool $writeable)
    {
    }

    public function close(): void
    {
        if ($this->resource !== null) {
            fclose($this->resource);
            $this->resource = null;
        }
    }

    public function detach(): mixed
    {
        $resource = $this->resource;
        $this->resource = null;

        return $resource;
    }

    public function getSize(): ?int
    {
        if (!$this->isSeekable()) {
            return null;
        }

        if ($this->size === null) {
            $pos = $this->tell();
            fseek($this->resource, 0, SEEK_END);

            $this->size = $this->tell();
            fseek($this->resource, $pos, SEEK_SET);
        }

        return $this->size;
    }

    public function eof(): bool
    {
        return feof($this->resource);
    }

    public function isSeekable(): bool
    {
        return $this->seekable;
    }

    public function tell() : int
    {
        return ftell($this->resource);
    }

    public function seek(int $position, int $whence = SEEK_SET): void
    {
        if (!$this->isSeekable()) {
            throw new IOException("Unable to seek on a non-seekable stream");
        }

        if (fseek($this->resource, $position, $whence) < 0) {
            throw new IOException("Unable to seek to the given position");
        }
    }

    public function isReadable(): bool
    {
        return $this->readable;
    }

    public function read(int $length): string
    {
        return fread($this->resource, $length);
    }

    public function isWritable(): bool
    {
        return $this->writeable;
    }

    public function isWriteable(): bool
    {
        return $this->isWritable();
    }

    public function write(string $string): int
    {
        if (!$this->writeable) {
            throw new IOException("Stream is not writeable");
        }

        $result = fwrite($this->resource, $string);
        if ($result === false) {
            throw new IOException("Failed to write to stream");
        }

        $this->size = null;
        return $result;
    }

    public function rewind(): void
    {
        $this->seek(0);
    }

    public function getContents(): string
    {
        return (string) stream_get_contents($this->resource);
    }

    public function getMetadata(?string $key = null): mixed
    {
        $data = stream_get_meta_data($this->resource);

        return $key !== null ? $data[$key] ?? null : $data;
    }

    public function __toString(): string
    {
        return $this->getContents();
    }
}
