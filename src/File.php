<?php

declare(strict_types=1);


namespace Shufflingpixels\IO;

use Shufflingpixels\IO\Exception\IOException;

class File extends Resource
{
    /**
     * @throws IOException
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

        return new self($fd, $mode->seekable(), $mode->readable(), $mode->writeable());
    }
}
