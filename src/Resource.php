<?php

namespace Shufflingpixels\IO;

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
        fclose($this->resource);
    }

    public function length() : int
    {
        if (!$this->isSeekable()) {
            throw new IOException("Unable to get length on a non-seekable stream");
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

    public function seek(int $position, SeekMode $mode = SeekMode::SET): void
    {
        if (!$this->isSeekable()) {
            throw new IOException("Unable to seek on a non-seekable stream");
        }

        if (fseek($this->resource, $position, $mode->value) < 0) {
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

    public function isWriteable(): bool
    {
        return $this->writeable;
    }

    public function write(string $data): int
    {
        if (!$this->writeable) {
            throw new IOException("Stream is not writeable");
        }

        $result = fwrite($this->resource, $data);
        if ($result === false) {
            throw new IOException("Failed to write to stream");
        }

        $this->size = null;
        return $result;
    }
}
