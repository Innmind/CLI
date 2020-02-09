<?php
declare(strict_types = 1);

namespace Tests\Innmind\CLI\Stream;

use Innmind\CLI\Stream\ChunkWriteByLine;
use Innmind\Stream\{
    Writable,
    Stream\Position,
    Stream\Position\Mode,
    Stream\Size,
};
use Innmind\Immutable\Str;
use PHPUnit\Framework\TestCase;

class ChunkWriteByLineTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Writable::class,
            new ChunkWriteByLine(
                $this->createMock(Writable::class)
            )
        );
    }

    public function testWrite()
    {
        $stream = new ChunkWriteByLine(
            $inner = $this->createMock(Writable::class)
        );
        $data = Str::of("foo\nbar\nbaz\n");
        $inner
            ->expects($this->at(0))
            ->method('write')
            ->with(Str::of("foo\n"));
        $inner
            ->expects($this->at(1))
            ->method('write')
            ->with(Str::of("bar\n"));
        $inner
            ->expects($this->at(2))
            ->method('write')
            ->with(Str::of("baz\n"));
        $inner
            ->expects($this->at(3))
            ->method('write')
            ->with(Str::of(''));

        $this->assertNull($stream->write($data));
    }

    public function testClose()
    {
        $stream = new ChunkWriteByLine(
            $inner = $this->createMock(Writable::class)
        );
        $inner
            ->expects($this->once())
            ->method('close');

        $this->assertNull($stream->close());
    }

    public function testClosed()
    {
        $stream = new ChunkWriteByLine(
            $inner = $this->createMock(Writable::class)
        );
        $inner
            ->expects($this->exactly(2))
            ->method('closed')
            ->willReturnOnConsecutiveCalls(true, false);

        $this->assertTrue($stream->closed());
        $this->assertFalse($stream->closed());
    }

    public function testPosition()
    {
        $stream = new ChunkWriteByLine(
            $inner = $this->createMock(Writable::class)
        );
        $inner
            ->expects($this->once())
            ->method('position')
            ->willReturn($expected = new Position(42));

        $this->assertSame($expected, $stream->position());
    }

    public function testSeek()
    {
        $stream = new ChunkWriteByLine(
            $inner = $this->createMock(Writable::class)
        );
        $position = new Position(42);
        $mode = Mode::fromStart();
        $inner
            ->expects($this->once())
            ->method('seek')
            ->with($position, $mode);

        $this->assertNull($stream->seek($position, $mode));
    }

    public function testRewind()
    {
        $stream = new ChunkWriteByLine(
            $inner = $this->createMock(Writable::class)
        );
        $inner
            ->expects($this->once())
            ->method('rewind');

        $this->assertNull($stream->rewind());
    }

    public function testEnd()
    {
        $stream = new ChunkWriteByLine(
            $inner = $this->createMock(Writable::class)
        );
        $inner
            ->expects($this->exactly(2))
            ->method('end')
            ->willReturnOnConsecutiveCalls(true, false);

        $this->assertTrue($stream->end());
        $this->assertFalse($stream->end());
    }

    public function testSize()
    {
        $stream = new ChunkWriteByLine(
            $inner = $this->createMock(Writable::class)
        );
        $inner
            ->expects($this->once())
            ->method('size')
            ->willReturn($expected = new Size(42));

        $this->assertSame($expected, $stream->size());
    }

    public function testKnowsSize()
    {
        $stream = new ChunkWriteByLine(
            $inner = $this->createMock(Writable::class)
        );
        $inner
            ->expects($this->exactly(2))
            ->method('knowsSize')
            ->willReturnOnConsecutiveCalls(true, false);

        $this->assertTrue($stream->knowsSize());
        $this->assertFalse($stream->knowsSize());
    }
}
