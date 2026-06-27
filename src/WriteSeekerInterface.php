<?php

declare(strict_types=1);

namespace Shufflingpixels\IO;

/** A writable, seekable byte stream. */
interface WriteSeekerInterface extends WriterInterface, SeekerInterface
{
}
