# php-io

A small, focused PHP I/O toolkit for working with streams and binary data.

`php-io` gives you:

- A consistent `StreamInterface` abstraction
- In-memory and file-backed stream implementations
- A `BinaryReader` for common integer formats (8/16/32-bit, LE/BE)
- Clear exception types for I/O and end-of-stream conditions

## Requirements

- PHP `>= 8.0`

## Installation

```bash
composer require shufflingpixels/php-io
```

## Quick Start

### Read binary values from a string

```php
<?php

use Shufflingpixels\IO\BinaryReader;

$reader = BinaryReader::string("\x34\x12\x80\xff");

$a = $reader->readUInt16LE(); // 0x1234 => 4660
$b = $reader->readInt8();     // -128
$c = $reader->readInt8();     // -1
```

### Work with an in-memory buffer

```php
<?php

use Shufflingpixels\IO\Buffer;
use Shufflingpixels\IO\SeekMode;

$buffer = new Buffer('abcdef');

$buffer->seek(2);                  // position = 2
$buffer->write('XY');              // data becomes: abXYef
$buffer->seek(-2, SeekMode::END);  // position near end

$tail = $buffer->read(2);          // "ef"
```

### Open and use a file stream

```php
<?php

use Shufflingpixels\IO\File;
use Shufflingpixels\IO\FileMode;

$file = File::open('example.bin', FileMode::RW);

$file->write("ABC");
$file->seek(0);

$bytes = $file->read(3); // "ABC"

$file->close();
```

## Main Types

- `Shufflingpixels\IO\StreamInterface`: common stream contract (`read`, `write`, `seek`, `tell`, `eof`, `length`)
- `Shufflingpixels\IO\Buffer`: in-memory stream implementation
- `Shufflingpixels\IO\File`: file-backed stream implementation
- `Shufflingpixels\IO\BinaryReader`: typed binary reads over any `StreamInterface`
- `Shufflingpixels\IO\SeekMode`: type-safe seek modes (`SET`, `CUR`, `END`)
- `Shufflingpixels\IO\FileMode`: file open modes (`READ`, `WRITE`, `RW`)

## Exceptions

- `Shufflingpixels\IO\Exception\IOException`: generic stream/file I/O failures
- `Shufflingpixels\IO\Exception\EndOfStreamException`: not enough bytes available when reading

## Running Tests

This package uses Pest.

```bash
composer test
```

## License

AGPL-3.0
