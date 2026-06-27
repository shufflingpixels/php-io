<?php

declare(strict_types=1);

namespace Shufflingpixels\IO;

/** A readable, seekable byte stream. */
interface ReadSeekerInterface extends ReaderInterface, SeekerInterface
{
}
