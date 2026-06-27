<?php

declare(strict_types=1);


namespace Shufflingpixels\IO;

use Shufflingpixels\IO\Exception\IOException;

/** A file-backed stream opened via {@see FileMode}. */
class File extends Resource
{
    /**
     * Opens a file and returns a stream for it.
     *
     * @throws IOException if the file cannot be opened
     */
    public static function open(string $filename, FileMode $mode = FileMode::RW) : self
    {
        set_error_handler(static fn () => true);

        try {
            $fd = fopen($filename, $mode->value);
        } finally {
            restore_error_handler();
        }

        if ($fd === false) {
            throw new IOException("Unable to open file");
        }

        return new self($fd);
    }
}
