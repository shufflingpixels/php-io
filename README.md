# php-io

A small, focused PHP I/O toolkit for working with streams and binary data.

`php-io` gives you:

- A minimal, Go-inspired interface hierarchy for byte streams
- In-memory and file-backed stream implementations
- `BinaryReader` and `BinaryWriter` for common integer formats (8/16/32-bit, LE/BE)
- Clear exception types for I/O failures

## Requirements

- PHP `>= 8.0`

## Installation

```bash
composer require shufflingpixels/php-io
```

## Quick Start

### Read binary values from an in-memory buffer

```php
<?php

use Shufflingpixels\IO\BinaryReader;
use Shufflingpixels\IO\Buffer;

$reader = new BinaryReader(new Buffer("\x34\x12\x80\xff"));

$a = $reader->readUInt16LE(); // 0x1234 => 4660
$b = $reader->readInt8();     // -128
$c = $reader->readInt8();     // -1
```

### Write binary values to a buffer

```php
<?php

use Shufflingpixels\IO\BinaryWriter;
use Shufflingpixels\IO\Buffer;

$buffer = new Buffer('');
$writer = new BinaryWriter($buffer);

$writer->writeUInt16LE(0x1234);
$writer->writeInt8(-1);

$buffer->seek(0);
$bytes = $buffer->read($buffer->length()); // "\x34\x12\xff"
```

### Seek and write in-place with a Buffer

```php
<?php

use Shufflingpixels\IO\Buffer;

$buffer = new Buffer('abcdef');

$buffer->seek(2);
$buffer->write('XY');     // data becomes: abXYef

$buffer->seek(-2, SEEK_END);
$tail = $buffer->read(2); // "ef"
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

### Limit reads to a byte window

```php
<?php

use Shufflingpixels\IO\BinaryReader;
use Shufflingpixels\IO\Buffer;
use Shufflingpixels\IO\LimitedReader;

$buffer = new Buffer("header\x34\x12rest");

$buffer->seek(6); // skip header
$section = new LimitedReader($buffer, 2);
$reader  = new BinaryReader($section);

$value = $reader->readUInt16LE(); // 0x1234 — cannot read past the 2-byte window
```

## Interfaces

`php-io` uses a minimal, composable interface hierarchy inspired by Go's `io` package.
Each interface adds exactly one capability.

| Interface | Methods |
|---|---|
| `ReaderInterface` | `read(int $length): string\|false` |
| `WriterInterface` | `write(string $data): int` |
| `SeekerInterface` | `seek()`, `tell()`, `eof()`, `length()` |
| `ReadSeekerInterface` | `ReaderInterface` + `SeekerInterface` |
| `WriteSeekerInterface` | `WriterInterface` + `SeekerInterface` |
| `ReadWriterInterface` | `ReaderInterface` + `WriterInterface` |
| `ReadWriteSeekerInterface` | `ReaderInterface` + `WriterInterface` + `SeekerInterface` |

`read()` returns `false` when the stream is at EOF.

## Implementations

| Class | Implements | Description |
|---|---|---|
| `Buffer` | `ReadWriteSeekerInterface` | In-memory stream backed by a PHP string |
| `Resource` | `ReadWriteSeekerInterface` | Base class wrapping a PHP file resource |
| `File` | `ReadWriteSeekerInterface` | File-backed stream opened via `FileMode` |
| `LimitedReader` | `ReaderInterface` | Limits reads to a fixed byte budget |
| `BinaryReader` | — | Typed binary reads over any `ReaderInterface` |
| `BinaryWriter` | — | Typed binary writes over any `WriterInterface` |

## BinaryReader methods

Integer names follow `read{Signedness}{Bits}{Endianness}`:

| Method | Size | Range |
|---|---|---|
| `readUInt8()` | 1 byte | 0–255 |
| `readInt8()` | 1 byte | −128–127 |
| `readUInt16LE()` / `readUInt16BE()` | 2 bytes | 0–65535 |
| `readInt16LE()` / `readInt16BE()` | 2 bytes | −32768–32767 |
| `readUInt32LE()` / `readUInt32BE()` | 4 bytes | 0–4294967295 |
| `readInt32LE()` / `readInt32BE()` | 4 bytes | −2147483648–2147483647 |
| `readPaddedString(int $length, string $pad_chars)` | `$length` bytes | strips trailing `$pad_chars` |

`readExact(int $length)` reads exactly `$length` bytes and throws `RuntimeException` if fewer are available.

## BinaryWriter methods

Integer names follow `write{Signedness}{Bits}{Endianness}`. All methods return bytes written.

| Method | Size |
|---|---|
| `writeUInt8()` / `writeInt8()` | 1 byte |
| `writeUInt16LE()` / `writeUInt16BE()` / `writeInt16LE()` / `writeInt16BE()` | 2 bytes |
| `writeUInt32LE()` / `writeUInt32BE()` / `writeInt32LE()` / `writeInt32BE()` | 4 bytes |
| `writePaddedString(string $data, int $length, string $pad_char)` | `$length` bytes |

## Exceptions

- `Shufflingpixels\IO\Exception\IOException` — generic stream/file I/O failures
- `Shufflingpixels\IO\Exception\EndOfStreamException` — subclass of `IOException`

## Running Tests

```bash
composer test
```

## License

AGPL-3.0
