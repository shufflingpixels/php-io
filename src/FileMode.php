<?php

declare(strict_types=1);


namespace Shufflingpixels\IO;

enum FileMode : string
{
    case READ = 'r';
    case WRITE = 'w';
    case RW = 'r+';

    public function seekable()
    {
        return true;
    }

    public function readable()
    {
        return $this === self::READ || $this === self::RW;
    }

    public function writeable()
    {
        return $this === self::WRITE || $this === self::RW;
    }
}
