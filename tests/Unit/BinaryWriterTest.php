<?php

use Shufflingpixels\IO\BinaryReader;
use Shufflingpixels\IO\BinaryWriter;
use Shufflingpixels\IO\Buffer;

function writer(): array
{
    $buffer = new Buffer('');
    return [new BinaryWriter($buffer), $buffer];
}

function bufferContents(Buffer $buffer): string
{
    $pos = $buffer->tell();
    $buffer->seek(0);
    $result = $buffer->read($buffer->length());
    $buffer->seek($pos);
    return $result;
}

it('writes 8 bit integers', function () {
    [$writer, $buffer] = writer();

    $writer->writeUInt8(0x7f);
    $writer->writeUInt8(0xff);
    $writer->writeInt8(-1);
    $writer->writeInt8(-128);

    expect(bufferContents($buffer))->toBe("\x7f\xff\xff\x80");
});

it('writes 16 bit integers in little and big endian', function () {
    [$writer, $buffer] = writer();

    $writer->writeUInt16LE(0x1234);
    $writer->writeUInt16BE(0x5678);
    $writer->writeInt16LE(-1);
    $writer->writeInt16BE(-32768);

    expect(bufferContents($buffer))->toBe(
        pack('v', 0x1234) . pack('n', 0x5678) . pack('v', 0xffff) . pack('n', 0x8000)
    );
});

it('writes 32 bit integers in little and big endian', function () {
    [$writer, $buffer] = writer();

    $writer->writeUInt32LE(0x12345678);
    $writer->writeUInt32BE(0x10203040);
    $writer->writeInt32LE(-1);
    $writer->writeInt32BE(-2147483648);

    expect(bufferContents($buffer))->toBe(
        pack('V', 0x12345678) . pack('N', 0x10203040) . pack('V', 0xffffffff) . pack('N', 0x80000000)
    );
});

it('writes a padded string shorter than the field length', function () {
    [$writer, $buffer] = writer();

    $writer->writePaddedString('hi', 5);

    expect(bufferContents($buffer))->toBe("hi\x00\x00\x00");
});

it('writes a padded string exactly matching the field length', function () {
    [$writer, $buffer] = writer();

    $writer->writePaddedString('hello', 5);

    expect(bufferContents($buffer))->toBe('hello');
});

it('truncates a string longer than the field length', function () {
    [$writer, $buffer] = writer();

    $writer->writePaddedString('toolong', 4);

    expect(bufferContents($buffer))->toBe('tool');
});

it('uses a custom pad character', function () {
    [$writer, $buffer] = writer();

    $writer->writePaddedString('hi', 5, ' ');

    expect(bufferContents($buffer))->toBe('hi   ');
});

it('throws for a multi-byte pad character', function () {
    [$writer] = writer();

    expect(fn () => $writer->writePaddedString('x', 4, "\x00\x00"))
        ->toThrow(InvalidArgumentException::class, 'pad_char must be exactly one byte');
});

it('written values round-trip through BinaryReader', function () {
    [$writer, $buffer] = writer();

    $writer->writeUInt8(42);
    $writer->writeInt16LE(-300);
    $writer->writeUInt32BE(0xdeadbeef);

    $buffer->seek(0);
    $reader = new BinaryReader($buffer);

    expect($reader->readUInt8())->toBe(42)
        ->and($reader->readInt16LE())->toBe(-300)
        ->and($reader->readUInt32BE())->toBe(0xdeadbeef);
});
