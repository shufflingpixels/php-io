<?php

declare(strict_types=1);

namespace Shufflingpixels\IO;

/** A readable, writable, and seekable byte stream. */
interface ReadWriteSeekerInterface extends ReadSeekerInterface, WriteSeekerInterface
{
}
