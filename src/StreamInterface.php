<?php

namespace Shufflingpixels\IO;

interface StreamInterface
{
    /**
     * Close the underlying resource.
     */
    public function close() : void;

    /**
     * returns true if stream is at End-of-file
     */
    public function eof() : bool;

    /**
     * Get the length of the stream, 
     * if length can't be returned RuntimeException is thrown.
     *
     * @throws \RuntimeException
     */
    public function length() : int;

    /**
     * Returns true if the stream supports seeking.
     */
    public function isSeekable() : bool;

    /**
     * Seek to a position in the stream.
     * may throw RuntimeException if seeking is not supported.
     *
     * @throws \OutOfBoundsException
     * @throws \RuntimeException
     */
    public function seek(int $position, SeekMode $mode = SeekMode::SET): void;

    /**
     * Get the current position in the stream.
     */
    public function tell() : int;

    /**
     * Returns true if stream can be read from.
     */
    public function isReadable() : bool;

    /**
     * Read from stream, may throw RuntimeException if stream is not readable.
     *
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public function read(int $length): string;

    /**
     * Returns true if stream can be written to.
     */
    public function isWriteable() : bool;

    /**
     * Write to stream.
     *
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public function write(string $data) : int;
}
