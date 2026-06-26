<?php

declare(strict_types=1);


namespace Shufflingpixels\IO;

use Psr\Http\Message\StreamInterface;
use Shufflingpixels\IO\Exception\IOException;

/**
 * Base PSR-7 stream implementation wrapping a PHP file resource.
 *
 * Concrete subclasses supply the resource and declare which capabilities
 * (seekable, readable, writable) are available for their use-case.
 */
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

    /**
     * Closes the underlying file resource. Subsequent calls are no-ops.
     */
    public function close(): void
    {
        if ($this->resource !== null) {
            fclose($this->resource);
            $this->resource = null;
        }
    }

    /**
     * Detaches and returns the underlying file resource, leaving the stream unusable.
     */
    public function detach(): mixed
    {
        $resource = $this->resource;
        $this->resource = null;

        return $resource;
    }

    /**
     * Returns the byte size of the stream, or null for non-seekable streams.
     *
     * The result is cached after the first call and invalidated by any write.
     */
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

    /**
     * Returns true when the underlying resource is at end-of-file.
     */
    public function eof(): bool
    {
        return feof($this->resource);
    }

    /**
     * Returns whether this stream supports seeking.
     */
    public function isSeekable(): bool
    {
        return $this->seekable;
    }

    /**
     * Returns the current byte offset of the file cursor.
     */
    public function tell() : int
    {
        return ftell($this->resource);
    }

    /**
     * Moves the file cursor to the given position.
     *
     * @param int $whence SEEK_SET, SEEK_CUR, or SEEK_END
     * @throws IOException if the stream is not seekable or the seek fails
     */
    public function seek(int $position, int $whence = SEEK_SET): void
    {
        if (!$this->isSeekable()) {
            throw new IOException("Unable to seek on a non-seekable stream");
        }

        if (fseek($this->resource, $position, $whence) < 0) {
            throw new IOException("Unable to seek to the given position");
        }
    }

    /**
     * Returns whether this stream supports reading.
     */
    public function isReadable(): bool
    {
        return $this->readable;
    }

    /**
     * Reads up to $length bytes from the current cursor position.
     */
    public function read(int $length): string
    {
        return fread($this->resource, $length);
    }

    /**
     * Returns whether this stream supports writing.
     */
    public function isWritable(): bool
    {
        return $this->writeable;
    }

    /**
     * Writes $string at the current cursor position and returns the bytes written.
     *
     * Invalidates the cached size so {@see getSize()} reflects the new length.
     *
     * @throws IOException if the stream is not writable or the write fails
     */
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

    /**
     * Moves the cursor to the start of the stream.
     */
    public function rewind(): void
    {
        $this->seek(0);
    }

    /**
     * Returns all bytes from the current cursor position to the end.
     */
    public function getContents(): string
    {
        return (string) stream_get_contents($this->resource);
    }

    /**
     * Returns stream metadata, or a single key if $key is provided.
     *
     * Returns null for unknown keys.
     */
    public function getMetadata(?string $key = null): mixed
    {
        $data = stream_get_meta_data($this->resource);

        return $key !== null ? $data[$key] ?? null : $data;
    }

    /**
     * Returns all remaining stream contents as a string.
     */
    public function __toString(): string
    {
        return $this->getContents();
    }
}
