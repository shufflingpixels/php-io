<?php

declare(strict_types=1);


namespace Shufflingpixels\IO\Exception;

/**
 * Thrown when a read is attempted past the end of a stream.
 */
class EndOfStreamException extends IOException
{
}
