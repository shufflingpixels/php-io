<?php

declare(strict_types=1);


namespace Shufflingpixels\IO;

use Shufflingpixels\IO\Exception\IOException;

/**
 * A ReadWriteSeekerInterface implementation wrapping a PHP file resource.
 */
class Resource implements ReadWriteSeekerInterface
{
    protected int $size = -1;

    /**
     * @param resource $resource
     */
    protected function __construct(protected mixed $resource)
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
     * Returns the total byte length of the stream.
     *
     * The result is cached after the first call and invalidated by any write.
     */
    public function length(): int
    {
        if ($this->size < 0) {
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
     * @throws IOException if the seek fails
     */
    public function seek(int $position, int $whence = SEEK_SET): void
    {
        if (fseek($this->resource, $position, $whence) < 0) {
            throw new IOException("Unable to seek to the given position");
        }
    }

    /**
     * Reads up to $length bytes from the current cursor position.
     *
     * Returns false when at end of file or on a read error.
     */
    public function read(int $length): string|false
    {
        if ($this->eof()) {
            return false;
        }

        $result = fread($this->resource, $length);

        return ($result === '' || $result === false) ? false : $result;
    }

    /**
     * Writes $string at the current cursor position and returns the bytes written.
     *
     * Invalidates the cached length so the next call to {@see length()} reflects the new size.
     *
     * @throws IOException if the write fails
     */
    public function write(string $string): int
    {
        set_error_handler(static fn () => true);
        
        try {
            $result = fwrite($this->resource, $string);
        } finally {
            restore_error_handler();
        }

        if ($result === false) {
            throw new IOException("Failed to write to stream");
        }

        // invalidate cached length
        $this->size = -1;

        return $result;
    }
}
