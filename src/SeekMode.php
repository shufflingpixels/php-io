<?php

namespace Shufflingpixels\IO;

/**
 * Basic wrapper around SEEK_* constants for type-safety.
 */
enum SeekMode : int
{
    // Set to start of stream.
    case SET = SEEK_SET;

    // Set to current position in stream.
    case CUR = SEEK_CUR;

    // Set to end of file.
    case END = SEEK_END;
}
